<?php

namespace App\Http\Controllers;

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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
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
            return [
                'id' => $stock->id,
                'name' => $stock->product?->name ?? 'Product',
                'sku' => $stock->product?->sku ?? '',
                'sale_price' => $stock->sale_price,
                'stock_quantity' => $stock->quantity,
            ];
        });

        $currencySymbol = Configuration::get('currency_symbol', '$');
        $vatRate = Configuration::get('vat_rate', 0);
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

        $stock = Stock::findOrFail($validated['stock_id']);
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
            'shipping_cost' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
        ]);

        $adminId = auth('admin')->id();
        $cart = Cart::where('fk_admin_id', $adminId)->first();

        if (!$cart || $cart->details()->count() === 0) {
            return response()->json(['message' => 'Cart is empty.'], 422);
        }

        $cart->load('details.stock');

        $netPrice = $cart->details->sum('subtotal');
        $discountAmount = $validated['discount_amount'] ?? 0;
        $shippingCost = $validated['shipping_cost'] ?? 0;
        $grandTotal = $netPrice - $discountAmount + $shippingCost;
        $paidAmount = $validated['paid_amount'];
        $saleDue = max(0, $grandTotal - $paidAmount);

        $buyPrice = $cart->details->sum(function ($detail) {
            return $detail->buy_price * $detail->qty;
        });

        $invoiceId = 'INV-' . strtoupper(uniqid());

        DB::beginTransaction();

        try {
            $sale = Sale::create([
                'table_name' => 'sales',
                'fk_user_id' => null,
                'invoice_id' => $invoiceId,
                'type' => 'POS',
                'net_price' => $netPrice,
                'vat_amount' => 0,
                'tax_amount' => 0,
                'shipping_cost' => $shippingCost,
                'discount_amount' => $discountAmount,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'buy_price' => $buyPrice,
                'sale_due' => $saleDue,
                'status' => 'completed',
                'note' => $validated['note'] ?? null,
                'created_by' => $adminId,
            ]);

            foreach ($cart->details as $detail) {
                SaleDetail::create([
                    'fk_sale_id' => $sale->id,
                    'fk_stock_id' => $detail->fk_stock_id,
                    'stock_name' => $detail->stock_name,
                    'size' => $detail->stock?->product?->size,
                    'color' => $detail->stock?->product?->color,
                    'total_stock' => $detail->total_stock,
                    'sale_stock' => $detail->qty,
                    'subtotal' => $detail->subtotal,
                ]);

                $stock = Stock::find($detail->fk_stock_id);
                if ($stock) {
                    $stock->update([
                        'quantity' => max(0, $stock->quantity - $detail->qty),
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

            // Accounting: record income from sale
            Income::create([
                'table_name' => 'sales',
                'fk_transaction_id' => $transaction->id,
                'description' => "Sale {$invoiceId}",
                'amount' => $grandTotal,
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

            $cart->details()->delete();
            $cart->update([
                'net_total' => 0, 'vat_total' => 0, 'tax_total' => 0,
                'discount_total' => 0, 'grand_total' => 0, 'buy_total' => 0,
            ]);

            DB::commit();

            ActivityLogger::created('Sale', $sale);

            return response()->json([
                'message' => 'Sale completed successfully.',
                'redirect' => route('sales.invoice', $sale),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Checkout failed: ' . $e->getMessage()], 500);
        }
    }

    private function recalculateCart(Cart $cart)
    {
        $cart->load('details');
        $cart->update([
            'net_total' => $cart->details->sum('subtotal'),
            'grand_total' => $cart->details->sum('subtotal'),
            'buy_total' => $cart->details->sum(function ($d) {
                return $d->buy_price * $d->qty;
            }),
        ]);
    }
}
