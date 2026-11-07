<?php

namespace App\Http\Controllers\Api;

use App\Accounting\AccountingPoster;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Cashbook;
use App\Models\Category;
use App\Models\Configuration;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Stock;
use App\Models\Transaction;
use App\Services\ActivityLogger;
use App\Services\VatCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $admin = Admin::where('email', $request->email)->first();

        if (! $admin || ! Hash::check($request->password, $admin->password)) {
            ActivityLogger::failedLogin($request->email, 'Invalid credentials');

            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $token = $admin->createToken('auth-token')->plainTextToken;

        ActivityLogger::login($request->email);

        return response()->json([
            'token' => $token,
            'admin' => ['first_name' => $admin->first_name],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        ActivityLogger::logout();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function configuration(Request $request)
    {
        $config = Configuration::getMany([
            'currency_sign',
            'currency_symbol',
            'vat_rate',
            'vat_percentage',
            'tax_rate',
            'tax_percentage',
            'date_format',
            'time_format',
            'timezone',
            'project_name',
            'company_name',
            'company_address',
            'company_vat_number',
            'company_country',
            'company_email',
            'company_phone',
        ]);

        $vatRate = $config['vat_rate'] !== ''
            ? (float) $config['vat_rate']
            : ($config['vat_percentage'] !== ''
                ? (float) $config['vat_percentage']
                : app(VatCalculator::class)->defaultRate());

        $taxRate = $config['tax_rate'] !== ''
            ? (float) $config['tax_rate']
            : (float) ($config['tax_percentage'] !== '' ? $config['tax_percentage'] : 0);

        // Keep both the new and legacy key names in sync so existing React
        // clients keep working while VAT becomes the single source of truth.
        $config['vat_rate'] = $vatRate;
        $config['vat_percentage'] = $vatRate;
        $config['tax_rate'] = $taxRate;
        $config['tax_percentage'] = $taxRate;
        $config['currency_sign'] = $config['currency_sign'] ?: ($config['currency_symbol'] ?: '$');

        return response()->json($config);
    }

    public function dashboard(Request $request)
    {
        $categories = Category::with('children')->whereNull('fk_category_id')->get();

        return response()->json($categories);
    }

    public function categoryProduct(Request $request, $categoryId)
    {
        $categoryIds = [$categoryId];
        $children = Category::where('fk_category_id', $categoryId)->pluck('id')->toArray();
        $categoryIds = array_merge($categoryIds, $children);

        $stocks = Stock::where('status', 'active')
            ->where('quantity', '>', 0)
            ->whereHas('product', function ($q) use ($categoryIds) {
                $q->whereIn('fk_category_id', $categoryIds);
            })
            ->with('product')
            ->get();

        if ($stocks->isEmpty()) {
            $stocks = Stock::where('status', 'active')
                ->where('quantity', '>', 0)
                ->with('product')
                ->get();
        }

        return response()->json($stocks);
    }

    public function customer(Request $request)
    {
        $query = Customer::query();

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $customers = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($customers);
    }

    public function saveCustomer(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'vat_number' => 'nullable|string|max:30',
            'country' => 'nullable|string|size:2',
            'vat_treatment' => 'nullable|in:standard,reduced,zero,exempt,outside_scope',
        ]);

        $customer = Customer::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->email),
            'phone' => $request->input('phone', ''),
            'address' => $request->input('address', ''),
            'vat_number' => $request->input('vat_number'),
            'country' => $request->filled('country') ? strtoupper((string) $request->input('country')) : null,
            'vat_treatment' => $request->input('vat_treatment'),
            'status' => $request->input('status', 'active'),
            'balance' => 0,
            'created_by' => $request->user()?->id,
        ]);

        ActivityLogger::created('Customer', $customer);

        return response()->json(['message' => 'Customer created successfully', 'customer' => $customer]);
    }

    public function updateCustomer(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email,'.$id,
            'vat_number' => 'nullable|string|max:30',
            'country' => 'nullable|string|size:2',
            'vat_treatment' => 'nullable|in:standard,reduced,zero,exempt,outside_scope',
        ]);

        $customer->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->input('phone', $customer->phone),
            'address' => $request->input('address', $customer->address),
            'vat_number' => $request->input('vat_number', $customer->vat_number),
            'country' => $request->filled('country') ? strtoupper((string) $request->input('country')) : $customer->country,
            'vat_treatment' => $request->input('vat_treatment', $customer->vat_treatment),
            'status' => $request->input('status', $customer->status),
            'updated_by' => $request->user()?->id,
        ]);

        ActivityLogger::updated('Customer', $customer, $customer->getChanges());

        return response()->json(['message' => 'Customer updated successfully', 'customer' => $customer]);
    }

    public function category(Request $request)
    {
        $query = Category::query();

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $perPage = $request->input('per_page', 15);
        $categories = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($categories);
    }

    public function saveCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category = Category::create([
            'name' => $request->name,
            'fk_category_id' => $request->input('fk_category_id'),
            'serial' => $request->input('serial', 0),
            'status' => $request->input('status', 'active'),
            'created_by' => $request->user()?->id,
        ]);

        ActivityLogger::created('Category', $category);

        return response()->json(['message' => 'Category created successfully', 'category' => $category]);
    }

    public function updateCategory(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category->update([
            'name' => $request->name,
            'fk_category_id' => $request->input('fk_category_id', $category->fk_category_id),
            'serial' => $request->input('serial', $category->serial),
            'status' => $request->input('status', $category->status),
            'updated_by' => $request->user()?->id,
        ]);

        ActivityLogger::updated('Category', $category, $category->getChanges());

        return response()->json(['message' => 'Category updated successfully', 'category' => $category]);
    }

    public function product(Request $request)
    {
        $query = Product::with('stocks');

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $products = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($products);
    }

    public function saveProduct(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'fk_category_id' => 'required|exists:categories,id',
        ]);

        $product = Product::create([
            'name' => $request->name,
            'fk_category_id' => $request->fk_category_id,
            'fk_brand_id' => $request->input('fk_brand_id'),
            'fk_supplier_id' => $request->input('fk_supplier_id'),
            'fk_unit_id' => $request->input('fk_unit_id'),
            'sku' => $request->input('sku', ''),
            'barcode' => $request->input('barcode', ''),
            'description' => $request->input('description', ''),
            'specification' => $request->input('specification', ''),
            'status' => $request->input('status', 'active'),
            'image' => $request->hasFile('image') ? $request->file('image')->store('products', 'public') : null,
            'created_by' => $request->user()?->id,
        ]);

        ActivityLogger::created('Product', $product);

        return response()->json(['message' => 'Product created successfully', 'product' => $product]);
    }

    public function updateProduct(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'fk_category_id' => 'required|exists:categories,id',
        ]);

        $data = [
            'name' => $request->name,
            'fk_category_id' => $request->fk_category_id,
            'fk_brand_id' => $request->input('fk_brand_id', $product->fk_brand_id),
            'fk_supplier_id' => $request->input('fk_supplier_id', $product->fk_supplier_id),
            'fk_unit_id' => $request->input('fk_unit_id', $product->fk_unit_id),
            'sku' => $request->input('sku', $product->sku),
            'barcode' => $request->input('barcode', $product->barcode),
            'description' => $request->input('description', $product->description),
            'specification' => $request->input('specification', $product->specification),
            'status' => $request->input('status', $product->status),
            'updated_by' => $request->user()?->id,
        ];

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        ActivityLogger::updated('Product', $product, $product->getChanges());

        return response()->json(['message' => 'Product updated successfully', 'product' => $product]);
    }

    public function stock(Request $request)
    {
        $query = Stock::with('product');

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $stocks = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($stocks);
    }

    public function saveStock(Request $request)
    {
        $request->merge(['status' => strtolower((string) $request->input('status', 'active'))]);

        $validated = $request->validate([
            'fk_product_id' => 'required|exists:products,id',
            'fk_warehouses_id' => 'nullable|exists:warehouses,id',
            'batch' => 'nullable|string|max:100',
            'lot' => 'nullable|string|max:100',
            'quantity' => 'required|integer|min:0',
            'buy_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'vat_code' => 'nullable|in:standard,reduced,zero,exempt,outside_scope',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive,archive',
        ]);

        $adminId = $request->user()?->id;

        $stock = DB::transaction(function () use ($request, $validated, $adminId) {
            $stock = Stock::create([
                'fk_product_id' => $validated['fk_product_id'],
                'fk_warehouses_id' => $validated['fk_warehouses_id'] ?? null,
                'batch' => $validated['batch'] ?? null,
                'lot' => $validated['lot'] ?? null,
                'quantity' => $validated['quantity'],
                'buy_price' => $validated['buy_price'],
                'sale_price' => $validated['sale_price'],
                'vat_code' => $validated['vat_code'] ?? null,
                'vat_rate' => $validated['vat_rate'] ?? null,
                'status' => $validated['status'],
                'accounting_version' => 1,
                'created_by' => $adminId,
            ]);

            app(AccountingPoster::class)->stockIn($stock, $adminId);

            return $stock;
        });

        ActivityLogger::created('Stock', $stock);

        return response()->json(['message' => 'Stock created successfully', 'stock' => $stock->fresh()]);
    }

    public function sale(Request $request)
    {
        $query = Sale::with(['customer', 'transaction']);

        if ($request->has('search') && $request->search !== '') {
            $query->where('invoice_id', 'like', "%{$request->search}%");
        }

        $perPage = $request->input('per_page', 15);
        $sales = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($sales);
    }

    public function selectSale(Request $request, $id)
    {
        $sale = Sale::with(['customer', 'details.stock.product', 'transaction'])->findOrFail($id);

        return response()->json($sale);
    }

    public function saveSale(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.fk_stock_id' => 'required|exists:stocks,id',
            'items.*.sale_stock' => 'required|integer|min:1',
            'items.*.subtotal' => 'nullable|numeric|min:0',
            'subtotal' => 'nullable|numeric|min:0',
            'vat_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'total' => 'nullable|numeric|min:0',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        DB::beginTransaction();

        try {
            $invoiceId = 'INV-'.strtoupper(Str::random(8)).'-'.time();
            $adminId = $request->user()?->id;
            $today = now()->toDateString();
            $vat = app(VatCalculator::class);
            $discount = round((float) $request->input('discount_amount', 0), 2);

            // Lock the stock rows and reject the sale up front if any line
            // (including duplicate lines for the same batch) is short.
            $requestedQuantities = [];

            foreach ($request->items as $item) {
                $stockId = (int) $item['fk_stock_id'];
                $requestedQuantities[$stockId] = ($requestedQuantities[$stockId] ?? 0)
                    + (int) $item['sale_stock'];
            }

            $stocks = Stock::with('product')
                ->whereIn('id', array_keys($requestedQuantities))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($requestedQuantities as $stockId => $quantity) {
                $stock = $stocks->get($stockId);

                if (! $stock || (int) $stock->quantity < $quantity) {
                    DB::rollBack();

                    return response()->json([
                        'error' => 'Insufficient stock for '.($stock?->product?->name ?? 'item').'.',
                    ], 422);
                }
            }

            // The server is the source of truth: rebuild every line from the
            // stock price and the product's own VAT treatment.
            $lines = [];

            foreach ($request->items as $item) {
                $stock = $stocks->get((int) $item['fk_stock_id']);

                if (! $stock) {
                    continue;
                }

                $quantity = (int) $item['sale_stock'];
                $treatment = $vat->forStock($stock);
                $gross = round((float) $stock->sale_price * $quantity, 2);

                $lines[] = $vat->line($gross, $treatment['code'], $treatment['rate']) + [
                    'quantity' => $quantity,
                    'stock_id' => $stock->id,
                    'stock_name' => $stock->product->name ?? '',
                    'cost_price' => (float) $stock->buy_price,
                    'total_stock' => (int) $stock->quantity,
                ];
            }

            if (empty($lines)) {
                DB::rollBack();

                return response()->json(['error' => 'No valid items in sale'], 422);
            }

            $lines = $vat->applyDiscount($lines, $discount);
            $summary = $vat->summary($lines);
            $grandTotal = round($summary['gross'], 2);
            $paidAmount = round(min(
                max(0, (float) $request->input('paid_amount', $grandTotal)),
                $grandTotal
            ), 2);
            $saleDue = round(max(0, $grandTotal - $paidAmount), 2);
            $buyPrice = round(array_sum(array_map(
                fn (array $line) => (float) $line['cost_price'] * (int) $line['quantity'],
                $lines
            )), 2);

            $sale = Sale::create([
                'table_name' => 'sales',
                'fk_user_id' => $request->input('customer_id'),
                'invoice_id' => $invoiceId,
                'type' => 'POS',
                'net_price' => $summary['net'],
                'vat_amount' => $summary['vat'],
                'tax_amount' => 0,
                'shipping_cost' => 0,
                'discount_amount' => $discount,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'buy_price' => $buyPrice,
                'sale_due' => $saleDue,
                'status' => 'completed',
                'note' => $request->input('note', ''),
                'vat_breakdown' => $summary['breakdown'],
                'created_by' => $adminId,
            ]);

            foreach ($lines as $line) {
                SaleDetail::create([
                    'fk_sale_id' => $sale->id,
                    'fk_stock_id' => $line['stock_id'],
                    'stock_name' => $line['stock_name'],
                    'total_stock' => $line['total_stock'],
                    'sale_stock' => $line['quantity'],
                    'subtotal' => $line['gross'],
                    'cost_price' => $line['cost_price'],
                    'vat_code' => $line['code'],
                    'vat_rate' => $line['rate'],
                    'net_amount' => $line['net'],
                    'vat_amount' => $line['vat'],
                    'gross_amount' => $line['gross'],
                ]);

                Stock::where('id', $line['stock_id'])
                    ->decrement('quantity', $line['quantity']);
            }

            $transaction = Transaction::create([
                'date' => $today,
                'type' => Transaction::TYPE_SALE_INCOME,
                'fk_reference_id' => $sale->id,
                'amount' => $grandTotal,
                'paid_amount' => $paidAmount,
                'due_amount' => $saleDue,
                'created_by' => $adminId,
            ]);

            Income::create([
                'table_name' => 'sales',
                'fk_transaction_id' => $transaction->id,
                'description' => "Sale #{$invoiceId}",
                'amount' => $grandTotal,
                'net_amount' => $summary['net'],
                'vat_amount' => $summary['vat'],
                'created_by' => $adminId,
            ]);

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

            ActivityLogger::created('Sale', $sale);

            DB::commit();

            return response()->json([
                'message' => 'Sale completed successfully',
                'sale' => $sale,
                'invoice_id' => $invoiceId,
                'net_amount' => $summary['net'],
                'vat_amount' => $summary['vat'],
                'paid_amount' => $paidAmount,
                'due_amount' => $saleDue,
                'total' => $grandTotal,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['error' => 'Failed to process sale'], 500);
        }
    }

    public function income(Request $request)
    {
        $query = Income::with('transaction:id,date');

        if ($request->has('search') && $request->search !== '') {
            $query->where('description', 'like', "%{$request->search}%");
        }

        $perPage = $request->input('per_page', 15);
        $incomes = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($incomes);
    }

    public function saveIncome(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'vat_code' => 'nullable|in:standard,reduced,zero,exempt,outside_scope',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();

        try {
            $adminId = $request->user()?->id;
            $today = now()->toDateString();
            $vat = app(VatCalculator::class);
            $calculated = $vat->extract(
                (float) $validated['amount'],
                $validated['vat_code'] ?? null,
                isset($validated['vat_rate']) ? (float) $validated['vat_rate'] : null
            );

            $transaction = Transaction::create([
                'date' => $today,
                'type' => Transaction::TYPE_INCOME,
                'fk_reference_id' => 0,
                'amount' => $validated['amount'],
                'paid_amount' => $validated['amount'],
                'due_amount' => 0,
                'created_by' => $adminId,
            ]);

            $income = Income::create([
                'table_name' => 'incomes',
                'fk_transaction_id' => $transaction->id,
                'description' => $validated['description'],
                'amount' => $validated['amount'],
                'vat_code' => $calculated['code'],
                'vat_rate' => $calculated['rate'],
                'net_amount' => $calculated['net'],
                'vat_amount' => $calculated['vat'],
                'created_by' => $adminId,
            ]);

            $transaction->update(['fk_reference_id' => $income->id]);

            Cashbook::create([
                'table_name' => 'incomes',
                'fk_reference_id' => $income->id,
                'description' => $request->description,
                'in_amount' => $request->amount,
                'out_amount' => 0,
                'amount_payable' => 0,
                'amount_receivable' => 0,
                'created_by' => $adminId,
            ]);

            app(AccountingPoster::class)->income($income, $adminId);

            ActivityLogger::created('Income', $income);

            DB::commit();

            return response()->json(['message' => 'Income saved successfully', 'income' => $income]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'Failed to save income'], 500);
        }
    }

    public function expense(Request $request)
    {
        $query = Expense::with('transaction:id,date');

        if ($request->has('search') && $request->search !== '') {
            $query->where('description', 'like', "%{$request->search}%");
        }

        $perPage = $request->input('per_page', 15);
        $expenses = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($expenses);
    }

    public function saveExpense(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'vat_code' => 'nullable|in:standard,reduced,zero,exempt,outside_scope',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();

        try {
            $adminId = $request->user()?->id;
            $today = now()->toDateString();
            $vat = app(VatCalculator::class);
            $calculated = $vat->extract(
                (float) $validated['amount'],
                $validated['vat_code'] ?? null,
                isset($validated['vat_rate']) ? (float) $validated['vat_rate'] : null
            );

            $transaction = Transaction::create([
                'date' => $today,
                'type' => Transaction::TYPE_EXPENSE,
                'fk_reference_id' => 0,
                'amount' => $validated['amount'],
                'paid_amount' => $validated['amount'],
                'due_amount' => 0,
                'created_by' => $adminId,
            ]);

            $expense = Expense::create([
                'table_name' => 'expenses',
                'fk_transaction_id' => $transaction->id,
                'description' => $validated['description'],
                'amount' => $validated['amount'],
                'vat_code' => $calculated['code'],
                'vat_rate' => $calculated['rate'],
                'net_amount' => $calculated['net'],
                'vat_amount' => $calculated['vat'],
                'created_by' => $adminId,
            ]);

            $transaction->update(['fk_reference_id' => $expense->id]);

            Cashbook::create([
                'table_name' => 'expenses',
                'fk_reference_id' => $expense->id,
                'description' => $request->description,
                'in_amount' => 0,
                'out_amount' => $request->amount,
                'amount_payable' => 0,
                'amount_receivable' => 0,
                'created_by' => $adminId,
            ]);

            app(AccountingPoster::class)->expense($expense, $adminId);

            ActivityLogger::created('Expense', $expense);

            DB::commit();

            return response()->json(['message' => 'Expense saved successfully', 'expense' => $expense]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'Failed to save expense'], 500);
        }
    }

    public function report(Request $request)
    {
        $currentYear = now()->year;
        $lastYear = $currentYear - 1;

        $customerCount = Customer::count();

        $stockValue = Stock::where('status', 'active')
            ->selectRaw('SUM(quantity * buy_price) as total')
            ->value('total') ?? 0;

        $monthlySales = Sale::whereHas('transaction', function ($q) use ($currentYear) {
            $q->whereYear('date', $currentYear);
        })
            ->join('transactions', 'transactions.fk_reference_id', '=', 'sales.id')
            ->where('transactions.type', Transaction::TYPE_SALE_INCOME)
            ->selectRaw('MONTH(transactions.date) as month, SUM(sales.grand_total) as total')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $monthlyIncome = Transaction::where('type', Transaction::TYPE_SALE_INCOME)
            ->whereYear('date', $currentYear)
            ->selectRaw('MONTH(date) as month, SUM(amount) as total')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $monthlyExpense = Transaction::where('type', Transaction::TYPE_EXPENSE)
            ->whereYear('date', $currentYear)
            ->selectRaw('MONTH(date) as month, SUM(amount) as total')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $lastYearMonthlySales = Sale::whereHas('transaction', function ($q) use ($lastYear) {
            $q->whereYear('date', $lastYear);
        })
            ->join('transactions', 'transactions.fk_reference_id', '=', 'sales.id')
            ->where('transactions.type', Transaction::TYPE_SALE_INCOME)
            ->selectRaw('MONTH(transactions.date) as month, SUM(sales.grand_total) as total')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $lastYearMonthlyIncome = Transaction::where('type', Transaction::TYPE_SALE_INCOME)
            ->whereYear('date', $lastYear)
            ->selectRaw('MONTH(date) as month, SUM(amount) as total')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $lastYearMonthlyExpense = Transaction::where('type', Transaction::TYPE_EXPENSE)
            ->whereYear('date', $lastYear)
            ->selectRaw('MONTH(date) as month, SUM(amount) as total')
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        return response()->json([
            'customer_count' => $customerCount,
            'stock_value' => $stockValue,
            'current_year' => [
                'sales' => $monthlySales,
                'income' => $monthlyIncome,
                'expense' => $monthlyExpense,
            ],
            'last_year' => [
                'sales' => $lastYearMonthlySales,
                'income' => $lastYearMonthlyIncome,
                'expense' => $lastYearMonthlyExpense,
            ],
        ]);
    }
}
