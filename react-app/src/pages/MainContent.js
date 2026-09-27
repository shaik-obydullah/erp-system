import { useState, useEffect, useCallback, useRef } from "react";
import {
  FaSearch,
  FaShoppingCart,
  FaPlus,
  FaMinus,
  FaTrash,
  FaCheck,
  FaSpinner,
  FaTimes,
  FaUser,
  FaTag,
} from "react-icons/fa";
import { toast } from "react-toastify";
import { debounce } from "lodash";
import {
  fetchDashboardCategories,
  fetchProductsByCategory,
  fetchCustomers,
  checkout,
} from "../api/axios";
import { useConfig } from "../contexts/ConfigContext";

const API_BASE = (import.meta.env.VITE_API_URL || "https://erp.obydullah.com").replace(/\/api\/v1$/, "");
const getImageUrl = (image) => {
  if (!image) return null;
  if (image.startsWith("http")) return image;
  return `${API_BASE}/uploads/products/${image.replace("products/", "")}`;
};

const CART_KEY = "pos_cart";

const loadCart = () => {
  try {
    const raw = localStorage.getItem(CART_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch {
    return [];
  }
};

const saveCart = (cart) => {
  localStorage.setItem(CART_KEY, JSON.stringify(cart));
};

export default function MainContent({ onCartUpdate }) {
  const { currencySign, vatPercentage, taxPercentage } = useConfig();

  const [categories, setCategories] = useState([]);
  const [activeCategoryId, setActiveCategoryId] = useState(null);
  const [products, setProducts] = useState([]);
  const [filteredProducts, setFilteredProducts] = useState([]);
  const [search, setSearch] = useState("");
  const [loadingCategories, setLoadingCategories] = useState(false);
  const [loadingProducts, setLoadingProducts] = useState(false);

  const [cart, setCart] = useState(loadCart);
  const [customers, setCustomers] = useState([]);
  const [selectedCustomer, setSelectedCustomer] = useState(null);
  const [customerSearch, setCustomerSearch] = useState("");
  const [showCustomerDropdown, setShowCustomerDropdown] = useState(false);
  const [discount, setDiscount] = useState("");
  const [checkingOut, setCheckingOut] = useState(false);

  const categoryScrollRef = useRef(null);
  const customerDropdownRef = useRef(null);

  // ── persist cart ──
  useEffect(() => {
    saveCart(cart);
    const total = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
    onCartUpdate?.(total);
  }, [cart, onCartUpdate]);

  // ── fetch categories ──
  useEffect(() => {
    const load = async () => {
      setLoadingCategories(true);
      try {
        const data = await fetchDashboardCategories();
        const list = Array.isArray(data) ? data : data?.categories || data?.data || [];
        setCategories(list);
        if (list.length > 0) {
          setActiveCategoryId(list[0].id);
        }
      } catch (err) {
        console.error("Failed to load categories:", err);
      } finally {
        setLoadingCategories(false);
      }
    };
    load();
  }, []);

  // ── fetch products (stocks) by category ──
  useEffect(() => {
    if (!activeCategoryId) return;
    const load = async () => {
      setLoadingProducts(true);
      try {
        const data = await fetchProductsByCategory(activeCategoryId);
        const list = Array.isArray(data) ? data : data?.stocks || data?.data || [];
        setProducts(list);
        setFilteredProducts(list);
        setSearch("");
      } catch (err) {
        console.error("Failed to load products:", err);
      } finally {
        setLoadingProducts(false);
      }
    };
    load();
  }, [activeCategoryId]);

  // ── client-side search filter ──
  const applySearch = useCallback(
    debounce((term, items) => {
      if (!term.trim()) {
        setFilteredProducts(items);
        return;
      }
      const lower = term.toLowerCase();
      setFilteredProducts(
        items.filter(
          (s) =>
            (s.product?.name || "").toLowerCase().includes(lower) ||
            (s.batch || "").toLowerCase().includes(lower) ||
            (s.lot || "").toLowerCase().includes(lower) ||
            (s.product?.sku || "").toLowerCase().includes(lower) ||
            (s.product?.barcode || "").toLowerCase().includes(lower)
        )
      );
    }, 300),
    []
  );

  useEffect(() => {
    applySearch(search, products);
  }, [search, products, applySearch]);

  // ── fetch customers ──
  useEffect(() => {
    const load = async () => {
      try {
        const data = await fetchCustomers({ search: customerSearch || undefined });
        const list = Array.isArray(data) ? data : data?.customers || data?.data || [];
        setCustomers(list);
      } catch (err) {
        console.error("Failed to load customers:", err);
      }
    };
    load();
  }, [customerSearch]);

  // close customer dropdown on outside click
  useEffect(() => {
    const handler = (e) => {
      if (customerDropdownRef.current && !customerDropdownRef.current.contains(e.target)) {
        setShowCustomerDropdown(false);
      }
    };
    document.addEventListener("mousedown", handler);
    return () => document.removeEventListener("mousedown", handler);
  }, []);

  // ── cart actions ──
  const addToCart = (stock) => {
    setCart((prev) => {
      const existing = prev.find((i) => i.id === stock.id);
      if (existing) {
        return prev.map((i) =>
          i.id === stock.id ? { ...i, qty: Math.min(i.qty + 1, stock.quantity) } : i
        );
      }
      return [
        ...prev,
        {
          id: stock.id,
          stock_id: stock.id,
          name: stock.product?.name || "Unknown",
          batch: stock.batch || "",
          image: stock.product?.image || "",
          price: parseFloat(stock.sale_price || 0),
          qty: 1,
          maxQty: stock.quantity,
        },
      ];
    });
  };

  const updateQty = (id, delta) => {
    setCart((prev) =>
      prev
        .map((i) => {
          if (i.id !== id) return i;
          const newQty = i.qty + delta;
          if (newQty <= 0) return null;
          if (newQty > i.maxQty) return { ...i, qty: i.maxQty };
          return { ...i, qty: newQty };
        })
        .filter(Boolean)
    );
  };

  const removeItem = (id) => {
    setCart((prev) => prev.filter((i) => i.id !== id));
  };

  const clearCart = () => {
    setCart([]);
    setSelectedCustomer(null);
    setDiscount("");
  };

  // ── price calculations ──
  const subtotal = cart.reduce((sum, i) => sum + i.price * i.qty, 0);
  const discountAmount = subtotal * (parseFloat(discount || 0) / 100);
  const afterDiscount = subtotal - discountAmount;
  const vatAmount = afterDiscount * (vatPercentage / 100);
  const taxAmount = afterDiscount * (taxPercentage / 100);
  const grandTotal = afterDiscount + vatAmount + taxAmount;

  // ── checkout ──
  const handleCheckout = async () => {
    if (cart.length === 0) return;
    setCheckingOut(true);
    try {
      const payload = {
        customer_id: selectedCustomer?.id || null,
        subtotal: subtotal,
        vat_amount: vatAmount,
        tax_amount: taxAmount,
        discount_amount: discountAmount,
        total: grandTotal,
        items: cart.map((i) => ({
          fk_stock_id: i.stock_id,
          sale_stock: i.qty,
          subtotal: i.price * i.qty,
        })),
      };
      await checkout(payload);
      toast.success("Sale completed successfully!");
      clearCart();
    } catch (err) {
      toast.error(err.message || "Checkout failed");
    } finally {
      setCheckingOut(false);
    }
  };

  // ── filtered customers for dropdown ──
  const filteredCustomers = customers.filter((c) => {
    if (!customerSearch) return true;
    const q = customerSearch.toLowerCase();
    return (
      (c.name || "").toLowerCase().includes(q) ||
      (c.phone || "").toLowerCase().includes(q) ||
      (c.email || "").toLowerCase().includes(q)
    );
  });

  return (
    <div className="flex h-[calc(100vh-4rem)] bg-gray-50">
      {/* ── LEFT: Categories + Products ── */}
      <div className="flex-1 flex flex-col min-w-0 overflow-hidden">
        {/* Category Tabs */}
        <div className="bg-white border-b border-gray-200 px-4 py-3">
          <div
            ref={categoryScrollRef}
            className="flex gap-2 overflow-x-auto scrollbar-hide pb-1"
          >
            {loadingCategories ? (
              <span className="text-gray-400 text-sm py-1">Loading categories...</span>
            ) : (
              categories.map((cat) => (
                <button
                  key={cat.id}
                  onClick={() => setActiveCategoryId(cat.id)}
                  className={`flex-shrink-0 px-4 py-2 rounded-full text-sm font-medium transition whitespace-nowrap ${
                    activeCategoryId === cat.id
                      ? "bg-blue-600 text-white shadow"
                      : "bg-gray-100 text-gray-700 hover:bg-gray-200"
                  }`}
                >
                  {cat.name || cat.category_name}
                </button>
              ))
            )}
          </div>
        </div>

        {/* Search Bar */}
        <div className="px-4 py-3 bg-white border-b border-gray-200">
          <div className="relative">
            <FaSearch className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              placeholder="Search stocks by name, batch, lot, SKU..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition text-sm"
            />
            {search && (
              <button
                onClick={() => setSearch("")}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
              >
                <FaTimes size={14} />
              </button>
            )}
          </div>
        </div>

        {/* Product Grid */}
        <div className="flex-1 overflow-y-auto p-4">
          {loadingProducts ? (
            <div className="flex items-center justify-center h-full text-gray-400">
              <FaSpinner className="animate-spin mr-2" /> Loading products...
            </div>
          ) : filteredProducts.length === 0 ? (
            <div className="flex flex-col items-center justify-center h-full text-gray-400">
              <FaShoppingCart size={48} className="mb-3 opacity-40" />
              <p className="text-lg font-medium">No stocks found</p>
              <p className="text-sm">Try another category or search term</p>
            </div>
          ) : (
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
              {filteredProducts.map((stock) => {
                const imgUrl = getImageUrl(stock.product?.image);
                return (
                  <button
                    key={stock.id}
                    onClick={() => addToCart(stock)}
                    className="bg-white rounded-xl border border-gray-200 hover:border-blue-400 hover:shadow-md transition text-left overflow-hidden group"
                  >
                    <div className="aspect-square bg-gray-100 flex items-center justify-center overflow-hidden">
                      {imgUrl ? (
                        <img
                          src={imgUrl}
                          alt={stock.product?.name}
                          className="w-full h-full object-cover group-hover:scale-105 transition duration-200"
                          onError={(e) => {
                            e.target.style.display = "none";
                            e.target.nextSibling.style.display = "flex";
                          }}
                        />
                      ) : null}
                      <div
                        className={`${
                          imgUrl ? "hidden" : "flex"
                        } items-center justify-center w-full h-full text-gray-300`}
                      >
                        <FaShoppingCart size={32} />
                      </div>
                    </div>
                    <div className="p-3">
                      <p className="text-sm font-medium text-gray-800 truncate">
                        {stock.product?.name || "Unknown"}
                      </p>
                      {stock.batch && (
                        <p className="text-xs text-gray-400 mt-0.5">Batch: {stock.batch}</p>
                      )}
                      <div className="flex items-center justify-between mt-1">
                        <p className="text-blue-600 font-bold">
                          {currencySign}
                          {parseFloat(stock.sale_price || 0).toFixed(2)}
                        </p>
                        <span className="text-xs text-gray-400">Qty: {stock.quantity}</span>
                      </div>
                    </div>
                  </button>
                );
              })}
            </div>
          )}
        </div>
      </div>

      {/* ── RIGHT: Shopping Cart Sidebar ── */}
      <div className="w-[380px] bg-white border-l border-gray-200 flex flex-col h-full sticky top-0">
        {/* Cart Header */}
        <div className="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
          <div className="flex items-center gap-2">
            <FaShoppingCart className="text-blue-600" />
            <h2 className="font-bold text-gray-800">Cart</h2>
            <span className="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">
              {currencySign}{grandTotal.toFixed(2)}
            </span>
          </div>
          {cart.length > 0 && (
            <button
              onClick={clearCart}
              className="text-red-500 hover:text-red-700 text-xs font-medium"
            >
              Clear All
            </button>
          )}
        </div>

        {/* Cart Items */}
        <div className="flex-1 overflow-y-auto px-4 py-2">
          {cart.length === 0 ? (
            <div className="flex flex-col items-center justify-center h-full text-gray-300">
              <FaShoppingCart size={40} className="mb-2" />
              <p className="text-sm">Cart is empty</p>
            </div>
          ) : (
            <div className="space-y-3">
              {cart.map((item) => (
                <div
                  key={item.id}
                  className="flex items-center gap-3 bg-gray-50 rounded-lg p-2.5"
                >
                  {/* Item Image */}
                  <div className="w-12 h-12 rounded-lg overflow-hidden bg-gray-200 flex-shrink-0">
                    {item.image ? (
                      <img
                        src={getImageUrl(item.image)}
                        alt={item.name}
                        className="w-full h-full object-cover"
                        onError={(e) => {
                          e.target.style.display = "none";
                        }}
                      />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center text-gray-400">
                        <FaShoppingCart size={16} />
                      </div>
                    )}
                  </div>

                  {/* Item Details */}
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium text-gray-800 truncate">{item.name}</p>
                    {item.batch && <p className="text-xs text-gray-400">Batch: {item.batch}</p>}
                    <p className="text-xs text-blue-600 font-semibold">
                      {currencySign}
                      {item.price.toFixed(2)}
                    </p>
                  </div>

                  {/* Qty Controls */}
                  <div className="flex items-center gap-1">
                    <button
                      onClick={() => updateQty(item.id, -1)}
                      className="w-7 h-7 rounded-md bg-gray-200 hover:bg-gray-300 flex items-center justify-center transition"
                    >
                      <FaMinus size={10} />
                    </button>
                    <span className="w-7 text-center text-sm font-medium">{item.qty}</span>
                    <button
                      onClick={() => updateQty(item.id, 1)}
                      className="w-7 h-7 rounded-md bg-blue-100 hover:bg-blue-200 text-blue-700 flex items-center justify-center transition"
                    >
                      <FaPlus size={10} />
                    </button>
                  </div>

                  {/* Delete */}
                  <button
                    onClick={() => removeItem(item.id)}
                    className="text-red-400 hover:text-red-600 transition ml-1"
                  >
                    <FaTrash size={14} />
                  </button>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Cart Footer: Customer, Discount, Totals, Checkout */}
        {cart.length > 0 && (
          <div className="border-t border-gray-200 px-4 py-3 space-y-3">
            {/* Customer Assignment */}
            <div className="relative" ref={customerDropdownRef}>
              <label className="text-xs font-medium text-gray-500 mb-1 flex items-center gap-1">
                <FaUser size={10} /> Customer
              </label>
              <button
                onClick={() => setShowCustomerDropdown(!showCustomerDropdown)}
                className="w-full flex items-center justify-between px-3 py-2 border border-gray-300 rounded-lg text-sm text-left hover:border-gray-400 transition"
              >
                <span className={selectedCustomer ? "text-gray-800" : "text-gray-400"}>
                  {selectedCustomer ? selectedCustomer.name : "Walk-in Customer"}
                </span>
                <FaTimes
                  size={12}
                  className={`text-gray-400 ${selectedCustomer ? "visible" : "invisible"}`}
                  onClick={(e) => {
                    e.stopPropagation();
                    setSelectedCustomer(null);
                  }}
                />
              </button>

              {showCustomerDropdown && (
                <div className="absolute z-50 bottom-full left-0 right-0 mb-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-hidden">
                  <div className="p-2 border-b border-gray-100">
                    <input
                      type="text"
                      placeholder="Search customers..."
                      value={customerSearch}
                      onChange={(e) => setCustomerSearch(e.target.value)}
                      className="w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm outline-none focus:ring-1 focus:ring-blue-500"
                      autoFocus
                    />
                  </div>
                  <div className="overflow-y-auto max-h-44">
                    {filteredCustomers.length === 0 ? (
                      <p className="p-3 text-sm text-gray-400 text-center">No customers found</p>
                    ) : (
                      filteredCustomers.map((c) => (
                        <button
                          key={c.id}
                          onClick={() => {
                            setSelectedCustomer(c);
                            setShowCustomerDropdown(false);
                            setCustomerSearch("");
                          }}
                          className={`w-full px-3 py-2 text-left text-sm hover:bg-blue-50 transition ${
                            selectedCustomer?.id === c.id ? "bg-blue-50 text-blue-700" : ""
                          }`}
                        >
                          <p className="font-medium text-gray-800">{c.name}</p>
                          <p className="text-xs text-gray-500">
                            {c.phone || c.email || ""}
                          </p>
                        </button>
                      ))
                    )}
                  </div>
                </div>
              )}
            </div>

            {/* Discount */}
            <div>
              <label className="text-xs font-medium text-gray-500 mb-1 flex items-center gap-1">
                <FaTag size={10} /> Discount %
              </label>
              <div className="relative">
                <input
                  type="number"
                  min="0"
                  max="100"
                  value={discount}
                  onChange={(e) => setDiscount(e.target.value)}
                  placeholder="0"
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <span className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">
                  %
                </span>
              </div>
            </div>

            {/* Price Breakdown */}
            <div className="space-y-1.5 text-sm">
              <div className="flex justify-between text-gray-600">
                <span>Subtotal</span>
                <span>
                  {currencySign}
                  {subtotal.toFixed(2)}
                </span>
              </div>
              {parseFloat(discount || 0) > 0 && (
                <div className="flex justify-between text-green-600">
                  <span>Discount ({discount}%)</span>
                  <span>
                    -{currencySign}
                    {discountAmount.toFixed(2)}
                  </span>
                </div>
              )}
              {vatPercentage > 0 && (
                <div className="flex justify-between text-gray-600">
                  <span>VAT ({vatPercentage}%)</span>
                  <span>
                    {currencySign}
                    {vatAmount.toFixed(2)}
                  </span>
                </div>
              )}
              {taxPercentage > 0 && (
                <div className="flex justify-between text-gray-600">
                  <span>Tax ({taxPercentage}%)</span>
                  <span>
                    {currencySign}
                    {taxAmount.toFixed(2)}
                  </span>
                </div>
              )}
              <div className="flex justify-between font-bold text-lg text-gray-800 pt-1.5 border-t border-gray-200">
                <span>Total</span>
                <span>
                  {currencySign}
                  {grandTotal.toFixed(2)}
                </span>
              </div>
            </div>

            {/* Sale Button */}
            <button
              onClick={handleCheckout}
              disabled={checkingOut || cart.length === 0}
              className="w-full py-3 bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white font-bold rounded-lg transition flex items-center justify-center gap-2 text-base"
            >
              {checkingOut ? (
                <>
                  <FaSpinner className="animate-spin" /> Processing...
                </>
              ) : (
                <>
                  <FaCheck /> Sale
                </>
              )}
            </button>
          </div>
        )}
      </div>
    </div>
  );
}
