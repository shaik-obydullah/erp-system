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
import CategoryPage from "./pages/CategoryPage";
import ProductPage from "./pages/ProductPage";
import StockPage from "./pages/StockPage";
import SalePage from "./pages/SalePage";
import IncomePage from "./pages/IncomePage";
import ExpensePage from "./pages/ExpensePage";
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
            <Route path="/add-customer" element={<Navigate to="/customers" replace />} />
            <Route path="/categories" element={<CategoryPage />} />
            <Route path="/add-category" element={<Navigate to="/categories" replace />} />
            <Route path="/products" element={<ProductPage />} />
            <Route path="/add-product" element={<Navigate to="/products" replace />} />
            <Route path="/stocks" element={<StockPage />} />
            <Route path="/add-stock" element={<Navigate to="/stocks" replace />} />
            <Route path="/sales" element={<SalePage />} />
            <Route path="/incomes" element={<IncomePage />} />
            <Route path="/add-income" element={<Navigate to="/incomes" replace />} />
            <Route path="/expenses" element={<ExpensePage />} />
            <Route path="/add-expense" element={<Navigate to="/expenses" replace />} />
            <Route path="/reports" element={<ReportPage />} />
          </Route>
          <Route path="*" element={<Navigate to="/login" replace />} />
        </Routes>
      </BrowserRouter>
    </ConfigProvider>
  );
}

export default App;
