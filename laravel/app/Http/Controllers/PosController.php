<?php

namespace App\Http\Controllers;

use App\Accounting\AccountingPoster;
use App\Models\Cart;
use App\Models\CartDetail;
use App\Models\Cashbook;
use App\Models\Configuration;
use App\Models\Customer;
use App\Models\Income;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Stock;
use App\Models\Transaction;
use App\Services\ActivityLogger;
use App\Services\VatCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function __construct(private readonly VatCalculator $vat) {}

    public function index()
    {
        $adminId = auth('admin')->id();
        $cart = Cart::firstOrCreate(
            ['fk_admin_id' => $adminId],
            [
                'net_total' => 0, 'vat_total' => 0, 'tax_total' => 0,
                'discount_total' => 0, 'grand_total' => 0, 'buy_total' => 0,
                'created_by' => $adminId,
            ]
        );

        $cart->load('details.stock.product');

        $stocks = Stock::where('status', 'active')
            ->where('quantity', '>', 0)
            ->with('product')
            ->get();

        $products = $stocks->map(function ($stock) {
            $treatment = $this->vat->forStock($stock);

            return [
                'id' => $stock->id,
                'name' => $stock->product?->name ?? 'Product',
                'sku' => $stock->product?->sku ?? '',
                'sale_price' => $stock->sale_price,
                'stock_quantity' => $stock->quantity,
                'vat_code' => $treatment['code'],
                'vat_rate' => $treatment['rate'],
            ];
        });

        $currencySymbol = Configuration::get('currency_symbol', '$');
        $vatRate = $this->vat->defaultRate();
        $taxRate = Configuration::get('tax_rate', 0);
        $customers = Customer::where('status', 'active')->orderBy('name')->get();

        return view('pos.index', compact('cart', 'stocks', 'products', 'currencySymbol', 'vatRate', 'taxRate', 'customers'));
    }

    public function addToCart(Request $request)
    {
        $validated = $request->validate([
            'stock_id' => 'required|exists:stocks,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $stock = Stock::with('product')->findOrFail($validated['stock_id']);
        $adminId = auth('admin')->id();

        if ($stock->quantity < $validated['quantity']) {
            return response()->json(['message' => 'Insufficient stock.'], 422);
        }

        $cart = Cart::firstOrCreate(
            ['fk_admin_id' => $adminId],
            [
                'net_total' => 0, 'vat_total' => 0, 'tax_total' => 0,
                'discount_total' => 0, 'grand_total' => 0, 'buy_total' => 0,
                'created_by' => $adminId,
            ]
        );

        $treatment = $this->vat->forStock($stock);

        $existingDetail = CartDetail::where('fk_cart_id', $cart->id)
            ->where('fk_stock_id', $stock->id)
            ->first();

        $qty = $validated['quantity'];
        $subtotal = $stock->sale_price * $qty;

        if ($existingDetail) {
            $newQty = $existingDetail->qty + $qty;
            if ($newQty > $stock->quantity) {
                return response()->json(['message' => 'Insufficient stock for this quantity.'], 422);
            }
            $existingDetail->update([
                'qty' => $newQty,
                'subtotal' => $stock->sale_price * $newQty,
                'vat_code' => $treatment['code'],
                'vat_rate' => $treatment['rate'],
            ]);
        } else {
            CartDetail::create([
                'fk_cart_id' => $cart->id,
                'fk_stock_id' => $stock->id,
                'stock_name' => $stock->product->name ?? 'Product',
                'total_stock' => $stock->quantity,
                'qty' => $qty,
                'unit' => $stock->sale_price,
                'vat' => 0,
                'tax' => 0,
                'discount' => 0,
                'subtotal' => $subtotal,
                'buy_price' => $stock->buy_price,
                'vat_code' => $treatment['code'],
                'vat_rate' => $treatment['rate'],
            ]);
        }

        $this->recalculateCart($cart);

        return response()->json(['message' => 'Item added to cart.', 'cart' => $cart->fresh('details')]);
    }

    public function removeFromCart(Request $request)
    {
        $validated = $request->validate([
            'detail_id' => 'required|exists:cart_details,id',
        ]);

        $detail = CartDetail::findOrFail($validated['detail_id']);
        $cart = $detail->cart;
        $detail->delete();

        $this->recalculateCart($cart);

        return response()->json(['message' => 'Item removed.', 'cart' => $cart->fresh('details')]);
    }

    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'paid_amount' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'shipping' => 'nullable|numeric|min:0',
            'customer_id' => 'nullable|exists:customers,id',
            'note' => 'nullable|string',
            'cart' => 'nullable|array',
            'cart.*.product_id' => 'nullable|integer',
            'cart.*.stock_id' => 'nullable|integer',
            'cart.*.quantity' => 'nullable|integer|min:1',
        ]);

        $adminId = auth('admin')->id();
        $cart = Cart::where('fk_admin_id', $adminId)->first();

        $requestLines = $validated['cart'] ?? [];

        if (empty($requestLines) && (! $cart || $cart->details()->count() === 0)) {
            return response()->json(['message' => 'Cart is empty.'], 422);
        }

        $discountAmount = round((float) ($validated['discount_amount'] ?? $validated['discount'] ?? 0), 2);
        $shippingCost = round((float) ($validated['shipping_cost'] ?? $validated['shipping'] ?? 0), 2);
        $paidAmount = round((float) $validated['paid_amount'], 2);

        $lines = $this->checkoutLines($requestLines, $cart);

        if (empty($lines)) {
            return response()->json(['message' => 'Cart is empty.'], 422);
        }

        $lines = $this->vat->applyDiscount($lines, $discountAmount);

        if ($shippingCost > 0) {
            $lines[] = $this->vat->line($shippingCost, null, null) + [
                'description' => 'Shipping',
                'quantity' => 1,
                'stock_id' => null,
                'stock_name' => 'Shipping',
                'buy_price' => 0.0,
                'total_stock' => 0,
                'is_shipping' => true,
            ];
        }

        $summary = $this->vat->summary($lines);

        $grandTotal = round($summary['gross'], 2);
        $saleDue = round(max(0, $grandTotal - $paidAmount), 2);

        // Walk-in customers are anonymous, so there is no one to chase for a
        // due. Only a registered customer may buy on credit.
        if (empty($validated['customer_id']) && $saleDue > 0) {
            return response()->json([
                'message' => 'Walk-in customers must pay the full amount. Select a customer to allow a due amount.',
            ], 422);
        }

        $buyPrice = round(array_sum(array_map(
            fn (array $line) => (float) ($line['buy_price'] ?? 0) * (int) ($line['quantity'] ?? 0),
            $lines
        )), 2);

        $invoiceId = 'INV-'.strtoupper(uniqid());

        DB::beginTransaction();

        try {
            $sale = Sale::create([
                'table_name' => 'sales',
                'fk_user_id' => $validated['customer_id'] ?? null,
                'invoice_id' => $invoiceId,
                'type' => 'POS',
                'net_price' => $summary['net'],
                'vat_amount' => $summary['vat'],
                'tax_amount' => 0,
                'shipping_cost' => $shippingCost,
                'discount_amount' => $discountAmount,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'buy_price' => $buyPrice,
                'sale_due' => $saleDue,
                'status' => 'completed',
                'note' => $validated['note'] ?? null,
                'vat_breakdown' => $summary['breakdown'],
                'created_by' => $adminId,
            ]);

            foreach ($lines as $line) {
                if (! empty($line['is_shipping']) || empty($line['stock_id'])) {
                    continue;
                }

                SaleDetail::create([
                    'fk_sale_id' => $sale->id,
                    'fk_stock_id' => $line['stock_id'],
                    'stock_name' => $line['stock_name'],
                    'size' => $line['size'] ?? null,
                    'color' => $line['color'] ?? null,
                    'total_stock' => $line['total_stock'] ?? 0,
                    'sale_stock' => $line['quantity'],
                    'subtotal' => $line['gross'],
                    'cost_price' => $line['buy_price'],
                    'vat_code' => $line['code'],
                    'vat_rate' => $line['rate'],
                    'net_amount' => $line['net'],
                    'vat_amount' => $line['vat'],
                    'gross_amount' => $line['gross'],
                ]);

                $stock = Stock::find($line['stock_id']);
                if ($stock) {
                    $stock->update([
                        'quantity' => max(0, $stock->quantity - $line['quantity']),
                        'updated_by' => $adminId,
                    ]);
                }
            }

            // Accounting: record sale transaction
            $transaction = Transaction::create([
                'date' => now()->toDateString(),
                'type' => Transaction::TYPE_SALE_INCOME,
                'fk_reference_id' => $sale->id,
                'amount' => $grandTotal,
                'paid_amount' => $paidAmount,
                'due_amount' => $saleDue,
                'created_by' => $adminId,
            ]);

            // Accounting: record income from sale (gross, VAT split happens in the ledger)
            Income::create([
                'table_name' => 'sales',
                'fk_transaction_id' => $transaction->id,
                'description' => "Sale {$invoiceId}",
                'amount' => $grandTotal,
                'vat_code' => null,
                'vat_rate' => null,
                'net_amount' => $summary['net'],
                'vat_amount' => $summary['vat'],
                'created_by' => $adminId,
            ]);

            // Accounting: cashbook entry for received payment
            if ($paidAmount > 0) {
                Cashbook::create([
                    'table_name' => 'sales',
                    'fk_reference_id' => $sale->id,
                    'description' => "Payment for {$invoiceId}",
                    'in_amount' => $paidAmount,
                    'out_amount' => 0,
                    'amount_payable' => 0,
                    'amount_receivable' => 0,
                    'created_by' => $adminId,
                ]);
            }

            // Accounting: if there's due, record receivable
            if ($saleDue > 0) {
                Cashbook::create([
                    'table_name' => 'sales',
                    'fk_reference_id' => $sale->id,
                    'description' => "Due for {$invoiceId}",
                    'in_amount' => 0,
                    'out_amount' => 0,
                    'amount_payable' => 0,
                    'amount_receivable' => $saleDue,
                    'created_by' => $adminId,
                ]);
            }

            app(AccountingPoster::class)->sale($sale, $paidAmount, $saleDue, $buyPrice, $adminId);

            if ($cart) {
                $cart->details()->delete();
                $cart->update([
                    'net_total' => 0, 'vat_total' => 0, 'tax_total' => 0,
                    'discount_total' => 0, 'grand_total' => 0, 'buy_total' => 0,
                ]);
            }

            DB::commit();

            ActivityLogger::created('Sale', $sale);

            return response()->json([
                'message' => 'Sale completed successfully.',
                'redirect' => route('sales.invoice', $sale),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Checkout failed: '.$e->getMessage()], 500);
        }
    }

    /**
     * Build VAT-inclusive lines from the POS request payload, falling back to
     * the persisted database cart when the client did not send a cart array.
     *
     * @param  array<int, array<string, mixed>>  $requestLines
     * @return array<int, array<string, mixed>>
     */
    private function checkoutLines(array $requestLines, ?Cart $cart): array
    {
        $lines = [];

        if (! empty($requestLines)) {
            foreach ($requestLines as $item) {
                $stockId = (int) ($item['stock_id'] ?? $item['product_id'] ?? 0);
                $quantity = (int) ($item['quantity'] ?? 0);

                if ($stockId <= 0 || $quantity <= 0) {
                    continue;
                }

                $stock = Stock::with('product')->find($stockId);
                if (! $stock) {
                    continue;
                }

                $treatment = $this->vat->forStock($stock);
                $gross = round((float) $stock->sale_price * $quantity, 2);

                $lines[] = $this->vat->line($gross, $treatment['code'], $treatment['rate']) + [
                    'description' => $stock->product->name ?? 'Product',
                    'quantity' => $quantity,
                    'stock_id' => $stock->id,
                    'stock_name' => $stock->product->name ?? 'Product',
                    'size' => $stock->product?->size,
                    'color' => $stock->product?->color,
                    'buy_price' => (float) $stock->buy_price,
                    'total_stock' => (int) $stock->quantity,
                ];
            }

            return $lines;
        }

        if (! $cart) {
            return $lines;
        }

        $cart->load('details.stock.product');

        foreach ($cart->details as $detail) {
            $product = $detail->stock?->product;
            $treatment = $this->vat->forStock($detail->stock);
            $gross = round((float) $detail->subtotal, 2);

            $lines[] = $this->vat->line($gross, $treatment['code'], $treatment['rate']) + [
                'description' => $detail->stock_name,
                'quantity' => (int) $detail->qty,
                'stock_id' => $detail->fk_stock_id,
                'stock_name' => $detail->stock_name,
                'size' => $product?->size,
                'color' => $product?->color,
                'buy_price' => (float) $detail->buy_price,
                'total_stock' => (int) $detail->total_stock,
            ];
        }

        return $lines;
    }

    private function recalculateCart(Cart $cart)
    {
        $cart->load('details.stock.product');

        $lines = $cart->details->map(function ($detail) {
            $treatment = $this->vat->forStock($detail->stock);

            return $this->vat->line((float) $detail->subtotal, $treatment['code'], $treatment['rate']);
        })->all();

        $summary = $this->vat->summary($lines);

        $cart->update([
            'net_total' => $summary['net'],
            'vat_total' => $summary['vat'],
            'tax_total' => 0,
            'grand_total' => $summary['gross'],
            'buy_total' => $cart->details->sum(function ($d) {
                return $d->buy_price * $d->qty;
            }),
        ]);
    }
}
