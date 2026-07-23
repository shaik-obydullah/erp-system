import { useState, useEffect, useCallback } from "react";
import { useNavigate } from "react-router-dom";
import { FaPlus, FaEdit, FaTrash, FaSearch, FaChevronLeft, FaChevronRight } from "react-icons/fa";
import { toast } from "react-toastify";
import { fetchCustomers, updateCustomer, deleteCustomer } from "../api/axios";
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

export default function CustomerPage() {
  const navigate = useNavigate();
  const { currencySign } = useConfig();
  const [customers, setCustomers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);

  const [editModal, setEditModal] = useState(false);
  const [editData, setEditData] = useState({ id: "", name: "", email: "", status: "" });
  const [editLoading, setEditLoading] = useState(false);

  const [deleteModal, setDeleteModal] = useState(false);
  const [deleteId, setDeleteId] = useState(null);
  const [deleteLoading, setDeleteLoading] = useState(false);

  const loadCustomers = useCallback(async () => {
    setLoading(true);
    setError("");
    try {
      const data = await fetchCustomers({ page, search });
      setCustomers(data.data || data.customers || []);
      setTotalPages(data.last_page || data.totalPages || 1);
    } catch (err) {
      setError(err.message || "Failed to load customers");
    } finally {
      setLoading(false);
    }
  }, [page, search]);

  useEffect(() => {
    loadCustomers();
  }, [loadCustomers]);

  useEffect(() => {
    setPage(1);
  }, [search]);

  const handleEdit = (customer) => {
    setEditData({ id: customer.id, name: customer.name, email: customer.email, status: customer.status });
    setEditModal(true);
  };

  const handleEditSubmit = async (e) => {
    e.preventDefault();
    setEditLoading(true);
    try {
      await updateCustomer(editData);
      toast.success("Customer updated successfully");
      setEditModal(false);
      loadCustomers();
    } catch (err) {
      toast.error(err.message || "Failed to update customer");
    } finally {
      setEditLoading(false);
    }
  };

  const handleDelete = (id) => {
    setDeleteId(id);
    setDeleteModal(true);
  };

  const handleDeleteConfirm = async () => {
    setDeleteLoading(true);
    try {
      await deleteCustomer(deleteId);
      toast.success("Customer deleted successfully");
      setDeleteModal(false);
      setDeleteId(null);
      loadCustomers();
    } catch (err) {
      toast.error(err.message || "Failed to delete customer");
    } finally {
      setDeleteLoading(false);
    }
  };

  return (
    <div className="p-6">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-800">Customers</h1>
        <button onClick={() => navigate("/add-customer")} className="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
          <FaPlus /> Add Customer
        </button>
      </div>

      <div className="bg-white rounded-lg shadow overflow-hidden">
        <div className="p-4 border-b">
          <div className="relative max-w-sm">
            <FaSearch className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              placeholder="Search customers..."
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
                    <th className="px-6 py-3">Name</th>
                    <th className="px-6 py-3">Email</th>
                    <th className="px-6 py-3">Status</th>
                    <th className="px-6 py-3 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200">
                  {customers.length === 0 ? (
                    <tr><td colSpan="4" className="px-6 py-8 text-center text-gray-500">No customers found</td></tr>
                  ) : (
                    customers.map((c) => (
                      <tr key={c.id} className="hover:bg-gray-50">
                        <td className="px-6 py-4 font-medium text-gray-900">{c.name}</td>
                        <td className="px-6 py-4 text-gray-600">{c.email}</td>
                        <td className="px-6 py-4"><StatusBadge status={c.status} /></td>
                        <td className="px-6 py-4 text-right">
                          <button onClick={() => handleEdit(c)} className="text-blue-600 hover:text-blue-800 mr-3"><FaEdit /></button>
                          <button onClick={() => handleDelete(c.id)} className="text-red-600 hover:text-red-800"><FaTrash /></button>
                        </td>
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

      {editModal && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
          <div className="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
            <h2 className="text-lg font-semibold mb-4">Edit Customer</h2>
            <form onSubmit={handleEditSubmit} className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" required value={editData.name} onChange={(e) => setEditData({ ...editData, name: e.target.value })} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" required value={editData.email} onChange={(e) => setEditData({ ...editData, email: e.target.value })} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select value={editData.status} onChange={(e) => setEditData({ ...editData, status: e.target.value })} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                  <option value="Active">Active</option>
                  <option value="Inactive">Inactive</option>
                </select>
              </div>
              <div className="flex justify-end gap-3 pt-2">
                <button type="button" onClick={() => setEditModal(false)} className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                <button type="submit" disabled={editLoading} className="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white rounded-lg transition flex items-center gap-2">
                  {editLoading ? "Saving..." : "Save Changes"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {deleteModal && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
          <div className="bg-white rounded-lg shadow-xl w-full max-w-sm p-6">
            <h2 className="text-lg font-semibold text-gray-800 mb-2">Delete Customer</h2>
            <p className="text-gray-600 mb-6">Are you sure you want to delete this customer? This action cannot be undone.</p>
            <div className="flex justify-end gap-3">
              <button onClick={() => { setDeleteModal(false); setDeleteId(null); }} className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
              <button onClick={handleDeleteConfirm} disabled={deleteLoading} className="px-4 py-2 bg-red-600 hover:bg-red-700 disabled:bg-red-400 text-white rounded-lg transition">
                {deleteLoading ? "Deleting..." : "Delete"}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
