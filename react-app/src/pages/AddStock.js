import { useState, useEffect, useCallback, useRef } from "react";
import { useNavigate } from "react-router-dom";
import { FaArrowLeft, FaSave, FaSpinner, FaSearch } from "react-icons/fa";
import { saveStock, fetchProducts } from "../api/axios";
import { useConfig } from "../contexts/ConfigContext";
import { debounce } from "lodash";

export default function AddStock() {
  const navigate = useNavigate();
  const { currencySign } = useConfig();
  const [form, setForm] = useState({
    fk_product_id: "",
    batch: "",
    lot: "",
    quantity: "",
    buy_price: "",
    sale_price: "",
    status: "Active",
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const [productSearch, setProductSearch] = useState("");
  const [productOptions, setProductOptions] = useState([]);
  const [productLoading, setProductLoading] = useState(false);
  const [showDropdown, setShowDropdown] = useState(false);
  const [selectedProduct, setSelectedProduct] = useState(null);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);
  const dropdownRef = useRef(null);
  const listRef = useRef(null);

  const searchProducts = useCallback(
    debounce(async (query, pageNum = 1) => {
      setProductLoading(true);
      try {
        const data = await fetchProducts({ page: pageNum, per_page: 20, search: query });
        const items = data.data || data.products || [];
        if (pageNum === 1) {
          setProductOptions(items);
        } else {
          setProductOptions((prev) => [...prev, ...items]);
        }
        setHasMore(pageNum < (data.last_page || data.totalPages || 1));
      } catch (err) {
        console.error("Failed to search products:", err);
      } finally {
        setProductLoading(false);
      }
    }, 400),
    []
  );

  useEffect(() => {
    searchProducts(productSearch, 1);
    setPage(1);
  }, [productSearch, searchProducts]);

  useEffect(() => {
    const handleClickOutside = (e) => {
      if (dropdownRef.current && !dropdownRef.current.contains(e.target)) {
        setShowDropdown(false);
      }
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  const handleDropdownScroll = () => {
    if (!listRef.current || !hasMore || productLoading) return;
    const { scrollTop, scrollHeight, clientHeight } = listRef.current;
    if (scrollTop + clientHeight >= scrollHeight - 10) {
      const nextPage = page + 1;
      setPage(nextPage);
      searchProducts(productSearch, nextPage);
    }
  };

  const handleProductSelect = (product) => {
    setSelectedProduct(product);
    setForm({ ...form, fk_product_id: product.id });
    setProductSearch(product.name);
    setShowDropdown(false);
  };

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError("");
    setLoading(true);
    try {
      await saveStock(form);
      navigate("/stocks", { replace: true });
    } catch (err) {
      setError(err.message || "Failed to save stock");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="p-6 max-w-xl">
      <div className="flex items-center gap-3 mb-6">
        <button onClick={() => navigate("/stocks")} className="p-2 hover:bg-gray-100 rounded-lg transition">
          <FaArrowLeft className="text-gray-600" />
        </button>
        <h1 className="text-2xl font-bold text-gray-800">Add Stock</h1>
      </div>

      <div className="bg-white rounded-lg shadow p-6">
        {error && (
          <div className="mb-4 p-3 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm">{error}</div>
        )}

        <form onSubmit={handleSubmit} className="space-y-5">
          <div className="relative" ref={dropdownRef}>
            <label className="block text-sm font-medium text-gray-700 mb-1">Product</label>
            <div className="relative">
              <FaSearch className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
              <input
                type="text"
                required
                value={productSearch}
                onChange={(e) => {
                  setProductSearch(e.target.value);
                  setShowDropdown(true);
                  if (!e.target.value) {
                    setSelectedProduct(null);
                    setForm({ ...form, fk_product_id: "" });
                  }
                }}
                onFocus={() => setShowDropdown(true)}
                className="w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                placeholder="Search product..."
              />
              {productLoading && (
                <FaSpinner className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 animate-spin" />
              )}
            </div>
            {showDropdown && productOptions.length > 0 && (
              <div ref={listRef} onScroll={handleDropdownScroll} className="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                {productOptions.map((p) => (
                  <button
                    key={p.id}
                    type="button"
                    onClick={() => handleProductSelect(p)}
                    className={`w-full text-left px-4 py-2 hover:bg-blue-50 transition text-sm ${selectedProduct?.id === p.id ? "bg-blue-100" : ""}`}
                  >
                    <span className="font-medium text-gray-900">{p.name}</span>
                    {p.sku && <span className="ml-2 text-gray-500 text-xs">{p.sku}</span>}
                  </button>
                ))}
                {productLoading && (
                  <div className="px-4 py-2 text-sm text-gray-500 text-center">Loading more...</div>
                )}
              </div>
            )}
            {showDropdown && !productLoading && productOptions.length === 0 && productSearch && (
              <div className="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg p-4 text-sm text-gray-500 text-center">
                No products found
              </div>
            )}
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Batch</label>
              <input
                type="text"
                name="batch"
                value={form.batch}
                onChange={handleChange}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                placeholder="Batch number"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Lot</label>
              <input
                type="text"
                name="lot"
                value={form.lot}
                onChange={handleChange}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                placeholder="Lot number"
              />
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
            <input
              type="number"
              name="quantity"
              required
              min="0"
              value={form.qty}
              onChange={handleChange}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
              placeholder="0"
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Buy Price ({currencySign})</label>
              <input
                type="number"
                name="buy_price"
                required
                min="0"
                step="0.01"
                value={form.buy_price}
                onChange={handleChange}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                placeholder="0.00"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Sale Price ({currencySign})</label>
              <input
                type="number"
                name="sale_price"
                required
                min="0"
                step="0.01"
                value={form.sale_price}
                onChange={handleChange}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                placeholder="0.00"
              />
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select
              name="status"
              value={form.status}
              onChange={handleChange}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
            >
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>

          <div className="flex justify-end gap-3 pt-2">
            <button type="button" onClick={() => navigate("/stocks")} className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
              Cancel
            </button>
            <button type="submit" disabled={loading} className="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white rounded-lg transition flex items-center gap-2">
              <FaSave />
              {loading ? "Saving..." : "Save Stock"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
