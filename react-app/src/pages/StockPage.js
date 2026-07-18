import { useState, useEffect, useCallback } from "react";
import { FaSearch, FaChevronLeft, FaChevronRight } from "react-icons/fa";
import { fetchStocks } from "../api/axios";
import { useConfig } from "../contexts/ConfigContext";

function LoadingIndicator() {
  return (
    <div className="flex items-center justify-center py-12">
      <svg className="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
      </svg>
    </div>
  );
}

function StatusBadge({ status }) {
  const isActive = String(status).toLowerCase() === "active";
  return (
    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${isActive ? "bg-green-100 text-green-800" : "bg-red-100 text-red-800"}`}>
      {status}
    </span>
  );
}

function Pagination({ currentPage, totalPages, onPageChange }) {
  if (totalPages <= 1) return null;
  return (
    <div className="flex items-center justify-between px-6 py-3 bg-white border-t border-gray-200">
      <span className="text-sm text-gray-600">
        Page {currentPage} of {totalPages}
      </span>
      <div className="flex gap-1">
        <button
          onClick={() => onPageChange(currentPage - 1)}
          disabled={currentPage <= 1}
          className="px-3 py-1 text-sm border rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
        >
          <FaChevronLeft size={12} />
        </button>
        <button
          onClick={() => onPageChange(currentPage + 1)}
          disabled={currentPage >= totalPages}
          className="px-3 py-1 text-sm border rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
        >
          <FaChevronRight size={12} />
        </button>
      </div>
    </div>
  );
}

export default function StockPage() {
  const { currencySign } = useConfig();
  const [stocks, setStocks] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);

  const loadStocks = useCallback(async () => {
    setLoading(true);
    setError("");
    try {
      const data = await fetchStocks({ page, per_page: 10, search });
      setStocks(data.data || data.stocks || []);
      setTotalPages(data.last_page || data.totalPages || 1);
    } catch (err) {
      setError(err.message || "Failed to load stocks");
    } finally {
      setLoading(false);
    }
  }, [page, search]);

  useEffect(() => {
    loadStocks();
  }, [loadStocks]);

  useEffect(() => {
    setPage(1);
  }, [search]);

  return (
    <div className="p-6">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-800">Stocks</h1>
      </div>

      <div className="bg-white rounded-lg shadow overflow-hidden">
        <div className="p-4 border-b">
          <div className="relative max-w-sm">
            <FaSearch className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              placeholder="Search stocks..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
            />
          </div>
        </div>

        {error && <div className="p-4 bg-red-50 text-red-600 text-sm">{error}</div>}

        {loading ? (
          <LoadingIndicator />
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="w-full text-sm text-left">
                <thead className="bg-gray-50 text-gray-600 uppercase text-xs">
                  <tr>
                    <th className="px-6 py-3">Product Name</th>
                    <th className="px-6 py-3">Batch</th>
                    <th className="px-6 py-3">Lot</th>
                    <th className="px-6 py-3 text-right">Qty</th>
                    <th className="px-6 py-3 text-right">Buy Price</th>
                    <th className="px-6 py-3 text-right">Sale Price</th>
                    <th className="px-6 py-3">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200">
                  {stocks.length === 0 ? (
                    <tr><td colSpan="7" className="px-6 py-8 text-center text-gray-500">No stocks found</td></tr>
                  ) : (
                    stocks.map((s) => (
                      <tr key={s.id} className="hover:bg-gray-50">
                        <td className="px-6 py-4 font-medium text-gray-900">{s.product?.name || s.product_name || "-"}</td>
                        <td className="px-6 py-4 text-gray-600">{s.batch || "-"}</td>
                        <td className="px-6 py-4 text-gray-600">{s.lot || "-"}</td>
                        <td className="px-6 py-4 text-gray-600 text-right">{s.qty || s.quantity || 0}</td>
                        <td className="px-6 py-4 text-gray-600 text-right">{currencySign}{s.buy_price || s.buyPrice || 0}</td>
                        <td className="px-6 py-4 text-gray-600 text-right">{currencySign}{s.sale_price || s.salePrice || 0}</td>
                        <td className="px-6 py-4"><StatusBadge status={s.status} /></td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
            <Pagination currentPage={page} totalPages={totalPages} onPageChange={setPage} />
          </>
        )}
      </div>
    </div>
  );
}
