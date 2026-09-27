import { useState, useEffect, useCallback } from "react";
import { useNavigate } from "react-router-dom";
import { FaPlus, FaEdit, FaTrash, FaSearch, FaChevronLeft, FaChevronRight, FaImage } from "react-icons/fa";
import { toast } from "react-toastify";
import { fetchProducts, updateProduct, deleteProduct, fetchCategories } from "../api/axios";
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

export default function ProductPage() {
  const navigate = useNavigate();
  const { currencySign } = useConfig();
  const [products, setProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);

  const [editModal, setEditModal] = useState(false);
  const [editData, setEditData] = useState({ id: "", name: "", fk_category_id: "", sku: "", status: "" });
  const [editImage, setEditImage] = useState(null);
  const [editImagePreview, setEditImagePreview] = useState("");
  const [editLoading, setEditLoading] = useState(false);

  const [deleteModal, setDeleteModal] = useState(false);
  const [deleteId, setDeleteId] = useState(null);
  const [deleteLoading, setDeleteLoading] = useState(false);

  const loadProducts = useCallback(async () => {
    setLoading(true);
    setError("");
    try {
      const data = await fetchProducts({ page, per_page: 10, search });
      setProducts(data.data || data.products || []);
      setTotalPages(data.last_page || data.totalPages || 1);
    } catch (err) {
      setError(err.message || "Failed to load products");
    } finally {
      setLoading(false);
    }
  }, [page, search]);

  useEffect(() => {
    loadProducts();
  }, [loadProducts]);

  useEffect(() => {
    const loadCategories = async () => {
      try {
        const data = await fetchCategories();
        setCategories(data.data || data.categories || data || []);
      } catch (err) {
        console.error("Failed to load categories:", err);
      }
    };
    loadCategories();
  }, []);

  useEffect(() => {
    setPage(1);
  }, [search]);

  const handleEdit = (product) => {
    setEditData({ id: product.id, name: product.name, fk_category_id: product.fk_category_id || product.category?.id || "", sku: product.sku, status: product.status });
    setEditImage(null);
    setEditImagePreview(product.image ? (product.image.startsWith("http") ? product.image : `${(import.meta.env.VITE_API_URL || "https://erp.obydullah.com").replace(/\/api\/v1$/, "")}/uploads/products/${product.image.replace("products/", "")}`) : "");
    setEditModal(true);
  };

  const handleEditImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      setEditImage(file);
      setEditImagePreview(URL.createObjectURL(file));
    }
  };

  const handleEditSubmit = async (e) => {
    e.preventDefault();
    setEditLoading(true);
    try {
      const formData = new FormData();
      formData.append("name", editData.name);
      formData.append("fk_category_id", editData.fk_category_id);
      formData.append("sku", editData.sku);
      formData.append("status", editData.status);
      if (editImage) {
        formData.append("image", editImage);
      }
      await updateProduct(editData.id, formData);
      toast.success("Product updated successfully");
      setEditModal(false);
      loadProducts();
    } catch (err) {
      toast.error(err.message || "Failed to update product");
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
      await deleteProduct(deleteId);
      toast.success("Product deleted successfully");
      setDeleteModal(false);
      setDeleteId(null);
      loadProducts();
    } catch (err) {
      toast.error(err.message || "Failed to delete product");
    } finally {
      setDeleteLoading(false);
    }
  };

  return (
    <div className="p-6">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-800">Products</h1>
        <button onClick={() => navigate("/add-product")} className="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
          <FaPlus /> Add Product
        </button>
      </div>

      <div className="bg-white rounded-lg shadow overflow-hidden">
        <div className="p-4 border-b">
          <div className="relative max-w-sm">
            <FaSearch className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              placeholder="Search products..."
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
                    <th className="px-6 py-3">SKU</th>
                    <th className="px-6 py-3">Category</th>
                    <th className="px-6 py-3">Status</th>
                    <th className="px-6 py-3 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200">
                  {products.length === 0 ? (
                    <tr><td colSpan="5" className="px-6 py-8 text-center text-gray-500">No products found</td></tr>
                  ) : (
                    products.map((p) => (
                      <tr key={p.id} className="hover:bg-gray-50">
                        <td className="px-6 py-4 font-medium text-gray-900">{p.name}</td>
                        <td className="px-6 py-4 text-gray-600 font-mono text-xs">{p.sku}</td>
                        <td className="px-6 py-4 text-gray-600">{p.category?.name || p.category_name || "-"}</td>
                        <td className="px-6 py-4"><StatusBadge status={p.status} /></td>
                        <td className="px-6 py-4 text-right">
                          <button onClick={() => handleEdit(p)} className="text-blue-600 hover:text-blue-800 mr-3"><FaEdit /></button>
                          <button onClick={() => handleDelete(p.id)} className="text-red-600 hover:text-red-800"><FaTrash /></button>
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
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center overflow-y-auto">
          <div className="bg-white rounded-lg shadow-xl w-full max-w-lg p-6 my-8">
            <h2 className="text-lg font-semibold mb-4">Edit Product</h2>
            <form onSubmit={handleEditSubmit} className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" required value={editData.name} onChange={(e) => setEditData({ ...editData, name: e.target.value })} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select required value={editData.fk_category_id} onChange={(e) => setEditData({ ...editData, fk_category_id: e.target.value })} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                  <option value="">Select category</option>
                  {categories.map((cat) => (
                    <option key={cat.id} value={cat.id}>{cat.name}</option>
                  ))}
                </select>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                  <input type="text" value={editData.sku} onChange={(e) => setEditData({ ...editData, sku: e.target.value })} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                  <select value={editData.status} onChange={(e) => setEditData({ ...editData, status: e.target.value })} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                  </select>
                </div>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Image</label>
                <div className="flex items-center gap-4">
                  {editImagePreview ? (
                    <img src={editImagePreview} alt="Preview" className="h-20 w-20 object-cover rounded-lg border" />
                  ) : (
                    <div className="h-20 w-20 bg-gray-100 rounded-lg border flex items-center justify-center">
                      <FaImage className="text-gray-400" />
                    </div>
                  )}
                  <label className="cursor-pointer px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm flex items-center gap-2">
                    <FaImage /> Choose Image
                    <input type="file" accept="image/*" onChange={handleEditImageChange} className="hidden" />
                  </label>
                </div>
              </div>
              <div className="flex justify-end gap-3 pt-2">
                <button type="button" onClick={() => setEditModal(false)} className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                <button type="submit" disabled={editLoading} className="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white rounded-lg transition">
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
            <h2 className="text-lg font-semibold text-gray-800 mb-2">Delete Product</h2>
            <p className="text-gray-600 mb-6">Are you sure you want to delete this product? This action cannot be undone.</p>
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
