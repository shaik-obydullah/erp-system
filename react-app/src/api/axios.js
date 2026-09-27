import axios from "axios";

const API_BASE_URL =
  import.meta.env.VITE_API_URL || "https://erp.obydullah.com/api";

const api = axios.create({
  baseURL: API_BASE_URL,
  withCredentials: true,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
});

api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem("authToken");
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Auth
export const login = async (data) => {
  try {
    const response = await api.post("/login", data);
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Login failed" };
  }
};

export const logout = async (token) => {
  try {
    const response = await api.post("/logout", {}, {
      headers: { Authorization: `Bearer ${token}` },
    });
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Logout failed" };
  }
};

// Configuration
export const fetchConfiguration = async () => {
  try {
    const response = await api.post("/configuration");
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to fetch configuration" };
  }
};

// Dashboard
export const fetchDashboardCategories = async (data) => {
  try {
    const response = await api.post("/dashboard", data);
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to fetch dashboard" };
  }
};

export const fetchProductsByCategory = async (categoryId) => {
  try {
    const response = await api.post(`/category-product/${categoryId}`);
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to fetch products" };
  }
};

// Customers
export const fetchCustomers = async (data) => {
  try {
    const response = await api.post("/customer", data);
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to fetch customers" };
  }
};

export const saveCustomer = async (data) => {
  try {
    const response = await api.post("/save-customer", data);
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to save customer" };
  }
};

export const updateCustomer = async (customer) => {
  try {
    const response = await api.put(`/update-customer/${customer.id}`, customer);
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to update customer" };
  }
};

export const deleteCustomer = async (id) => {
  try {
    const response = await api.delete(`/delete-customer/${id}`);
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to delete customer" };
  }
};

// Categories
export const fetchCategories = async (data) => {
  try {
    const response = await api.post("/category", data);
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to fetch categories" };
  }
};

export const saveCategory = async (data) => {
  try {
    const response = await api.post("/save-category", data);
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to save category" };
  }
};

export const updateCategory = async (category) => {
  try {
    const response = await api.put(`/update-category/${category.id}`, category);
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to update category" };
  }
};

export const deleteCategory = async (id) => {
  try {
    const response = await api.delete(`/delete-category/${id}`);
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to delete category" };
  }
};

// Products
export const fetchProducts = async ({ page = 1, per_page = 10, search = "" } = {}) => {
  try {
    const response = await api.get("/product", { params: { page, per_page, search } });
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to fetch products" };
  }
};

export const saveProduct = async (data) => {
  try {
    const response = await api.post("/save-product", data);
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to save product" };
  }
};

export const updateProduct = async (id, data) => {
  try {
    const response = await api.post(`/update-product/${id}`, data, {
      headers: { "Content-Type": "multipart/form-data" },
    });
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to update product" };
  }
};

export const deleteProduct = async (id) => {
  try {
    const response = await api.delete(`/delete-product/${id}`);
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to delete product" };
  }
};

// Stocks
export const fetchStocks = async ({ page = 1, per_page = 10, search = "" } = {}) => {
  try {
    const response = await api.get("/stock", { params: { page, per_page, search } });
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to fetch stocks" };
  }
};

export const saveStock = async (data) => {
  try {
    const response = await api.post("/save-stock", data);
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to save stock" };
  }
};

// Sales
export const fetchSale = async ({ page = 1, per_page = 10, search = "" } = {}) => {
  try {
    const response = await api.get("/sale", { params: { page, per_page, search } });
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to fetch sales" };
  }
};

export const fetchSaleDetails = async (saleId) => {
  try {
    const response = await api.post(`/select-sale/${saleId}`);
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to fetch sale details" };
  }
};

export const deleteSale = async (saleId) => {
  try {
    const response = await api.delete(`/delete-sale/${saleId}`);
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to delete sale" };
  }
};

export const checkout = async (saleData) => {
  try {
    const response = await api.post("/save-sale", saleData);
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Checkout failed" };
  }
};

// Incomes
export const fetchIncomes = async (data) => {
  try {
    const response = await api.post("/income", data);
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to fetch incomes" };
  }
};

export const saveIncome = async (data) => {
  try {
    const response = await api.post("/save-income", data);
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to save income" };
  }
};

// Expenses
export const fetchExpenses = async (data) => {
  try {
    const response = await api.post("/expense", data);
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to fetch expenses" };
  }
};

export const saveExpense = async (data) => {
  try {
    const response = await api.post("/save-expense", data);
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to save expense" };
  }
};

// Reports
export const fetchReports = async (data) => {
  try {
    const response = await api.post("/report", data);
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to fetch reports" };
  }
};

// AI Features (Ollama)
export const fetchAiStatus = async () => {
  try {
    const response = await api.get("/ai/status");
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to check AI status" };
  }
};

export const generateProductDescription = async (data) => {
  try {
    const response = await api.post("/ai/product-description", data);
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to generate description" };
  }
};

export const aiProductSearch = async (data) => {
  try {
    const response = await api.post("/ai/product-search", data);
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "AI search failed" };
  }
};

export const fetchInventoryInsights = async () => {
  try {
    const response = await api.post("/ai/inventory-insights");
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to get insights" };
  }
};

export const fetchSalesForecast = async () => {
  try {
    const response = await api.post("/ai/sales-forecast");
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to get forecast" };
  }
};

export const aiCustomerSupport = async (data) => {
  try {
    const response = await api.post("/ai/customer-support", data);
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "AI support failed" };
  }
};

export const fetchPriceSuggestion = async (data) => {
  try {
    const response = await api.post("/ai/price-suggestion", data);
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to get price suggestion" };
  }
};

export default api;
