import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { FaArrowLeft, FaSave, FaImage } from "react-icons/fa";
import { toast } from "react-toastify";
import { saveProduct, fetchCategories } from "../api/axios";
import { useConfig } from "../contexts/ConfigContext";

export default function AddProduct() {
  const navigate = useNavigate();
  const { currencySign } = useConfig();
  const [categories, setCategories] = useState([]);
  const [form, setForm] = useState({
    name: "",
    category_id: "",
    sku: "",
    barcode: "",
    status: "Active",
    description: "",
    specifications: "",
  });
  const [image, setImage] = useState(null);
  const [imagePreview, setImagePreview] = useState("");
  const [loading, setLoading] = useState(false);
  const [catLoading, setCatLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    const loadCategories = async () => {
      try {
        const data = await fetchCategories();
        setCategories(data.data || data.categories || data || []);
      } catch (err) {
        console.error("Failed to load categories:", err);
      } finally {
        setCatLoading(false);
      }
    };
    loadCategories();
  }, []);

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value });
  };

  const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      setImage(file);
      setImagePreview(URL.createObjectURL(file));
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError("");
    setLoading(true);
    try {
      const formData = new FormData();
      formData.append("name", form.name);
      formData.append("fk_category_id", form.category_id);
      formData.append("sku", form.sku);
      formData.append("barcode", form.barcode);
      formData.append("status", form.status);
      formData.append("description", form.description);
      formData.append("specifications", form.specifications);
      if (image) {
        formData.append("image", image);
      }
      await saveProduct(formData);
      toast.success("Product added successfully");
      navigate("/products", { replace: true });
    } catch (err) {
      toast.error(err.message || "Failed to save product");
      setError(err.message || "Failed to save product");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="p-6 max-w-4xl">
      <div className="flex items-center gap-3 mb-6">
        <button onClick={() => navigate("/products")} className="p-2 hover:bg-gray-100 rounded-lg transition">
          <FaArrowLeft className="text-gray-600" />
        </button>
        <h1 className="text-2xl font-bold text-gray-800">Add Product</h1>
      </div>

      <div className="bg-white rounded-lg shadow p-6">
        {error && (
          <div className="mb-4 p-3 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm">{error}</div>
        )}

        <form onSubmit={handleSubmit}>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="space-y-5">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Product Name</label>
                <input
                  type="text"
                  name="name"
                  required
                  value={form.name}
                  onChange={handleChange}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                  placeholder="Enter product name"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select
                  name="category_id"
                  required
                  value={form.category_id}
                  onChange={handleChange}
                  disabled={catLoading}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none disabled:bg-gray-100"
                >
                  <option value="">{catLoading ? "Loading categories..." : "Select category"}</option>
                  {categories.map((cat) => (
                    <option key={cat.id} value={cat.id}>{cat.name}</option>
                  ))}
                </select>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                  <input
                    type="text"
                    name="sku"
                    value={form.sku}
                    onChange={handleChange}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                    placeholder="SKU"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Barcode</label>
                  <input
                    type="text"
                    name="barcode"
                    value={form.barcode}
                    onChange={handleChange}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                    placeholder="Barcode"
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
            </div>

            <div className="space-y-5">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Product Image</label>
                <div className="flex flex-col items-center gap-3">
                  {imagePreview ? (
                    <img src={imagePreview} alt="Preview" className="h-40 w-40 object-cover rounded-lg border" />
                  ) : (
                    <div className="h-40 w-40 bg-gray-100 rounded-lg border-2 border-dashed flex flex-col items-center justify-center">
                      <FaImage className="text-gray-400 text-3xl mb-2" />
                      <span className="text-xs text-gray-400">No image selected</span>
                    </div>
                  )}
                  <label className="cursor-pointer px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm flex items-center gap-2">
                    <FaImage /> Choose Image
                    <input type="file" accept="image/*" onChange={handleImageChange} className="hidden" />
                  </label>
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea
                  name="description"
                  rows={3}
                  value={form.description}
                  onChange={handleChange}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none resize-none"
                  placeholder="Product description"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Specifications</label>
                <textarea
                  name="specifications"
                  rows={3}
                  value={form.specifications}
                  onChange={handleChange}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none resize-none"
                  placeholder="Product specifications"
                />
              </div>
            </div>
          </div>

          <div className="flex justify-end gap-3 pt-6 mt-6 border-t">
            <button type="button" onClick={() => navigate("/products")} className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
              Cancel
            </button>
            <button type="submit" disabled={loading} className="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white rounded-lg transition flex items-center gap-2">
              <FaSave />
              {loading ? "Saving..." : "Save Product"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
