import { useState, useEffect } from "react";
import { useConfig } from "../contexts/ConfigContext";
import { fetchSale, fetchSaleDetails, deleteSale } from "../api/axios";
import { FaSearch, FaEye, FaTrash, FaFileInvoice, FaDollarSign, FaShoppingCart, FaCalendarDay } from "react-icons/fa";
import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";

export default function SalePage() {
  const config = useConfig();
  const currencySign = config.currencySign || "$";

  const [sales, setSales] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(10);
  const [totalPages, setTotalPages] = useState(1);
  const [totalItems, setTotalItems] = useState(0);

  const [viewModal, setViewModal] = useState(false);
  const [saleDetail, setSaleDetail] = useState(null);
  const [detailLoading, setDetailLoading] = useState(false);

  const [deleteModal, setDeleteModal] = useState(false);
  const [saleToDelete, setSaleToDelete] = useState(null);
  const [deleting, setDeleting] = useState(false);

  const loadSales = async () => {
    setLoading(true);
    try {
      const res = await fetchSale({ page, per_page: perPage, search });
      setSales(res.data || res.sales || []);
      setTotalPages(res.last_page || res.totalPages || 1);
      setTotalItems(res.total || 0);
    } catch {
      setSales([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadSales();
  }, [page, perPage]);

  const handleSearch = (e) => {
    e.preventDefault();
    setPage(1);
    loadSales();
  };

  const handleView = async (sale) => {
    setDetailLoading(true);
    setViewModal(true);
    try {
      const res = await fetchSaleDetails(sale.id);
      setSaleDetail(res.data || res);
    } catch {
      setSaleDetail(null);
    } finally {
      setDetailLoading(false);
    }
  };

  const handleDeleteClick = (sale) => {
    setSaleToDelete(sale);
    setDeleteModal(true);
  };

  const handleDeleteConfirm = async () => {
    if (!saleToDelete) return;
    setDeleting(true);
    try {
      await deleteSale(saleToDelete.id);
      setDeleteModal(false);
      setSaleToDelete(null);
      loadSales();
    } catch {
    } finally {
      setDeleting(false);
    }
  };

  const downloadInvoice = () => {
    if (!saleDetail) return;
    const doc = new jsPDF();
    const s = saleDetail;

    doc.setFontSize(20);
    doc.text("Invoice", 14, 22);
    doc.setFontSize(10);
    doc.text(`Invoice ID: ${s.invoice_id || s.id}`, 14, 32);
    doc.text(`Date: ${s.transaction?.date || ""}`, 14, 38);
    doc.text(`Customer: ${s.customer?.name || ""}`, 14, 44);
    doc.text(`Phone: ${s.customer?.phone || ""}`, 14, 50);
    doc.text(`Address: ${s.customer?.address || ""}`, 14, 56);

    const items = s.details || s.items || [];
    const tableBody = items.map((item, i) => [
      i + 1,
      item.stock_name || item.product_name || "",
      item.sale_stock || item.quantity || 0,
      `${currencySign}${Number(item.subtotal / item.sale_stock || 0).toFixed(2)}`,
      `${currencySign}${Number(item.subtotal || 0).toFixed(2)}`,
    ]);

    autoTable(doc, {
      startY: 64,
      head: [["#", "Product", "Qty", "Price", "Total"]],
      body: tableBody,
      theme: "grid",
      headStyles: { fillColor: [79, 70, 229] },
    });

    const finalY = doc.lastAutoTable.finalY + 10;
    doc.setFontSize(11);
    doc.text(`Subtotal: ${currencySign}${Number(s.net_price || 0).toFixed(2)}`, 130, finalY);
    doc.text(`VAT: ${currencySign}${Number(s.vat_amount || 0).toFixed(2)}`, 130, finalY + 7);
    doc.text(`Tax: ${currencySign}${Number(s.tax_amount || 0).toFixed(2)}`, 130, finalY + 14);
    doc.text(`Discount: ${currencySign}${Number(s.discount_amount || 0).toFixed(2)}`, 130, finalY + 21);
    doc.setFontSize(13);
    doc.text(`Total: ${currencySign}${Number(s.grand_total || 0).toFixed(2)}`, 130, finalY + 31);

    doc.save(`invoice_${s.invoice_id || s.id}.pdf`);
  };

  const totalSales = totalItems;
  const totalRevenue = sales.reduce((sum, s) => sum + Number(s.grand_total || 0), 0);
  const avgSale = sales.length > 0 ? totalRevenue / sales.length : 0;
  const todayStr = new Date().toISOString().slice(0, 10);
  const todaySales = sales.filter((s) => (s.transaction?.date || "").startsWith(todayStr)).length;

  const pageNumbers = [];
  for (let i = 1; i <= totalPages; i++) {
    if (i === 1 || i === totalPages || (i >= page - 2 && i <= page + 2)) {
      pageNumbers.push(i);
    } else if (pageNumbers[pageNumbers.length - 1] !== "...") {
      pageNumbers.push("...");
    }
  }

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-gray-800">Sales</h1>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-white rounded-lg shadow p-4 flex items-center gap-4">
          <div className="p-3 bg-indigo-100 rounded-full"><FaFileInvoice className="w-5 h-5 text-indigo-600" /></div>
          <div><p className="text-sm text-gray-500">Total Sales</p><p className="text-xl font-bold text-gray-800">{totalSales}</p></div>
        </div>
        <div className="bg-white rounded-lg shadow p-4 flex items-center gap-4">
          <div className="p-3 bg-green-100 rounded-full"><FaDollarSign className="w-5 h-5 text-green-600" /></div>
          <div><p className="text-sm text-gray-500">Total Revenue</p><p className="text-xl font-bold text-gray-800">{currencySign}{totalRevenue.toFixed(2)}</p></div>
        </div>
        <div className="bg-white rounded-lg shadow p-4 flex items-center gap-4">
          <div className="p-3 bg-yellow-100 rounded-full"><FaShoppingCart className="w-5 h-5 text-yellow-600" /></div>
          <div><p className="text-sm text-gray-500">Average Sale</p><p className="text-xl font-bold text-gray-800">{currencySign}{avgSale.toFixed(2)}</p></div>
        </div>
        <div className="bg-white rounded-lg shadow p-4 flex items-center gap-4">
          <div className="p-3 bg-blue-100 rounded-full"><FaCalendarDay className="w-5 h-5 text-blue-600" /></div>
          <div><p className="text-sm text-gray-500">Today's Sales</p><p className="text-xl font-bold text-gray-800">{todaySales}</p></div>
        </div>
      </div>

      <div className="bg-white rounded-lg shadow p-4">
        <form onSubmit={handleSearch} className="flex gap-3 mb-4">
          <div className="relative flex-1">
            <FaSearch className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Search by invoice ID or customer..."
              className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none"
            />
          </div>
          <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">Search</button>
        </form>

        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left">
            <thead className="bg-gray-50 text-gray-600 uppercase text-xs">
              <tr>
                <th className="px-4 py-3">Invoice ID</th>
                <th className="px-4 py-3">Customer</th>
                <th className="px-4 py-3">Date</th>
                <th className="px-4 py-3">Total</th>
                <th className="px-4 py-3">Status</th>
                <th className="px-4 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {loading ? (
                <tr><td colSpan="6" className="text-center py-8 text-gray-500">Loading...</td></tr>
              ) : sales.length === 0 ? (
                <tr><td colSpan="6" className="text-center py-8 text-gray-500">No sales found</td></tr>
              ) : (
                sales.map((sale) => (
                  <tr key={sale.id} className="hover:bg-gray-50">
                    <td className="px-4 py-3 font-medium text-gray-800">{sale.invoice_id || sale.id}</td>
                    <td className="px-4 py-3 text-gray-600">{sale.customer?.name || "-"}</td>
                    <td className="px-4 py-3 text-gray-600">{sale.transaction?.date || "-"}</td>
                    <td className="px-4 py-3 text-gray-600">{currencySign}{Number(sale.grand_total || 0).toFixed(2)}</td>
                    <td className="px-4 py-3">
                      <span className={`px-2 py-1 text-xs rounded-full font-medium ${
                        sale.status === "completed" || sale.status === "paid"
                          ? "bg-green-100 text-green-700"
                          : sale.status === "pending"
                          ? "bg-yellow-100 text-yellow-700"
                          : "bg-gray-100 text-gray-600"
                      }`}>
                        {sale.status || "N/A"}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-right">
                      <div className="flex items-center justify-end gap-2">
                        <button onClick={() => handleView(sale)} className="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors">
                          <FaEye className="w-4 h-4" />
                        </button>
                        <button onClick={() => handleDeleteClick(sale)} className="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                          <FaTrash className="w-4 h-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        <div className="flex flex-col sm:flex-row items-center justify-between gap-4 mt-4 pt-4 border-t border-gray-200">
          <div className="flex items-center gap-2 text-sm text-gray-600">
            <span>Show</span>
            <select
              value={perPage}
              onChange={(e) => { setPerPage(Number(e.target.value)); setPage(1); }}
              className="border border-gray-300 rounded-lg px-2 py-1 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
            >
              {[5, 10, 15, 20, 25].map((n) => (
                <option key={n} value={n}>{n}</option>
              ))}
            </select>
            <span>entries</span>
            <span className="ml-2 text-gray-400">({totalItems} total)</span>
          </div>
          <div className="flex items-center gap-1">
            <button
              onClick={() => setPage(Math.max(1, page - 1))}
              disabled={page === 1}
              className="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Prev
            </button>
            {pageNumbers.map((num, i) =>
              num === "..." ? (
                <span key={`e${i}`} className="px-2 py-1 text-sm text-gray-400">...</span>
              ) : (
                <button
                  key={num}
                  onClick={() => setPage(num)}
                  className={`px-3 py-1 text-sm border rounded-lg ${
                    num === page
                      ? "bg-indigo-600 text-white border-indigo-600"
                      : "hover:bg-gray-50"
                  }`}
                >
                  {num}
                </button>
              )
            )}
            <button
              onClick={() => setPage(Math.min(totalPages, page + 1))}
              disabled={page === totalPages}
              className="px-3 py-1 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Next
            </button>
          </div>
        </div>
      </div>

      {viewModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between p-5 border-b border-gray-200">
              <h2 className="text-lg font-bold text-gray-800">Invoice Detail</h2>
              <div className="flex items-center gap-2">
                <button onClick={downloadInvoice} className="px-3 py-1.5 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-1">
                  <FaFileInvoice className="w-3.5 h-3.5" /> Download PDF
                </button>
                <button onClick={() => { setViewModal(false); setSaleDetail(null); }} className="p-1.5 text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100 transition-colors">
                  &times;
                </button>
              </div>
            </div>
            <div className="p-5">
              {detailLoading ? (
                <p className="text-center py-8 text-gray-500">Loading...</p>
              ) : saleDetail ? (
                <div className="space-y-4">
                  <div className="grid grid-cols-2 gap-4 text-sm">
                    <div><span className="text-gray-500">Invoice ID:</span> <span className="font-medium">{saleDetail.invoice_id || saleDetail.id}</span></div>
                    <div><span className="text-gray-500">Date:</span> <span className="font-medium">{saleDetail.transaction?.date || ""}</span></div>
                    <div><span className="text-gray-500">Customer:</span> <span className="font-medium">{saleDetail.customer?.name || ""}</span></div>
                    <div><span className="text-gray-500">Phone:</span> <span className="font-medium">{saleDetail.customer?.phone || ""}</span></div>
                    <div className="col-span-2"><span className="text-gray-500">Address:</span> <span className="font-medium">{saleDetail.customer?.address || ""}</span></div>
                  </div>

                  <table className="w-full text-sm text-left border border-gray-200 rounded-lg overflow-hidden">
                    <thead className="bg-gray-50 text-gray-600 uppercase text-xs">
                      <tr>
                        <th className="px-4 py-2">#</th>
                        <th className="px-4 py-2">Product</th>
                        <th className="px-4 py-2">Qty</th>
                        <th className="px-4 py-2">Price</th>
                        <th className="px-4 py-2">Total</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200">
                      {(saleDetail.details || saleDetail.items || []).map((item, i) => (
                        <tr key={i}>
                          <td className="px-4 py-2">{i + 1}</td>
                          <td className="px-4 py-2">{item.stock_name || item.product_name || ""}</td>
                          <td className="px-4 py-2">{item.sale_stock || item.quantity || 0}</td>
                          <td className="px-4 py-2">{currencySign}{Number(item.subtotal / item.sale_stock || 0).toFixed(2)}</td>
                          <td className="px-4 py-2">{currencySign}{Number(item.subtotal || 0).toFixed(2)}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>

                  <div className="flex justify-end">
                    <div className="w-64 space-y-1 text-sm">
                      <div className="flex justify-between"><span className="text-gray-500">Subtotal:</span><span>{currencySign}{Number(saleDetail.net_price || 0).toFixed(2)}</span></div>
                      <div className="flex justify-between"><span className="text-gray-500">VAT:</span><span>{currencySign}{Number(saleDetail.vat_amount || 0).toFixed(2)}</span></div>
                      <div className="flex justify-between"><span className="text-gray-500">Tax:</span><span>{currencySign}{Number(saleDetail.tax_amount || 0).toFixed(2)}</span></div>
                      <div className="flex justify-between"><span className="text-gray-500">Discount:</span><span>{currencySign}{Number(saleDetail.discount_amount || 0).toFixed(2)}</span></div>
                      <div className="flex justify-between font-bold border-t border-gray-200 pt-1"><span>Total:</span><span>{currencySign}{Number(saleDetail.grand_total || 0).toFixed(2)}</span></div>
                    </div>
                  </div>
                </div>
              ) : (
                <p className="text-center py-8 text-gray-500">Failed to load details</p>
              )}
            </div>
          </div>
        </div>
      )}

      {deleteModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <h2 className="text-lg font-bold text-gray-800 mb-2">Delete Sale</h2>
            <p className="text-gray-600 mb-6">
              Are you sure you want to delete invoice <strong>{saleToDelete?.invoice_id || saleToDelete?.id}</strong>? This action cannot be undone.
            </p>
            <div className="flex justify-end gap-3">
              <button
                onClick={() => { setDeleteModal(false); setSaleToDelete(null); }}
                className="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
              >
                Cancel
              </button>
              <button
                onClick={handleDeleteConfirm}
                disabled={deleting}
                className="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:bg-red-400 transition-colors"
              >
                {deleting ? "Deleting..." : "Delete"}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
