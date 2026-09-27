import { BrowserRouter, Routes, Route, Navigate, Outlet } from "react-router-dom";
import { ToastContainer } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";
import { ConfigProvider } from "./contexts/ConfigContext";
import PrivateRoute from "./components/PrivateRoute";
import Layout from "./layout/Layout";
import LoginPage from "./pages/LoginPage";
import Logout from "./pages/Logout";
import DashboardPage from "./pages/DashboardPage";
import CustomerPage from "./pages/CustomerPage";
import AddCustomer from "./pages/AddCustomer";
import CategoryPage from "./pages/CategoryPage";
import AddCategory from "./pages/AddCategory";
import ProductPage from "./pages/ProductPage";
import AddProduct from "./pages/AddProduct";
import StockPage from "./pages/StockPage";
import AddStock from "./pages/AddStock";
import SalePage from "./pages/SalePage";
import IncomePage from "./pages/IncomePage";
import AddIncome from "./pages/AddIncome";
import ExpensePage from "./pages/ExpensePage";
import AddExpense from "./pages/AddExpense";
import ReportPage from "./pages/ReportPage";

function App() {
  return (
    <ConfigProvider>
      <BrowserRouter>
        <ToastContainer position="top-right" autoClose={3000} hideProgressBar={false} closeOnClick pauseOnHover theme="colored" />
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route path="/logout" element={<Logout />} />
          <Route element={<PrivateRoute><Layout /></PrivateRoute>}>
            <Route path="/dashboard" element={<DashboardPage />} />
            <Route path="/customers" element={<CustomerPage />} />
            <Route path="/add-customer" element={<AddCustomer />} />
            <Route path="/categories" element={<CategoryPage />} />
            <Route path="/add-category" element={<AddCategory />} />
            <Route path="/products" element={<ProductPage />} />
            <Route path="/add-product" element={<AddProduct />} />
            <Route path="/stocks" element={<StockPage />} />
            <Route path="/add-stock" element={<AddStock />} />
            <Route path="/sales" element={<SalePage />} />
            <Route path="/incomes" element={<IncomePage />} />
            <Route path="/add-income" element={<AddIncome />} />
            <Route path="/expenses" element={<ExpensePage />} />
            <Route path="/add-expense" element={<AddExpense />} />
            <Route path="/reports" element={<ReportPage />} />
          </Route>
          <Route path="*" element={<Navigate to="/login" replace />} />
        </Routes>
      </BrowserRouter>
    </ConfigProvider>
  );
}

export default App;
