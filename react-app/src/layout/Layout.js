import { useState } from "react";
import { Link, NavLink, Outlet, useNavigate } from "react-router-dom";
import { useConfig } from "../contexts/ConfigContext";
import { logout } from "../api/axios";
import {
  FaHome,
  FaUsers,
  FaTags,
  FaBoxes,
  FaWarehouse,
  FaCashRegister,
  FaMoneyBillWave,
  FaReceipt,
  FaChartBar,
  FaSignOutAlt,
  FaShoppingCart,
  FaBars,
  FaTimes,
} from "react-icons/fa";

const navItems = [
  { to: "/dashboard", label: "Dashboard", icon: FaHome },
  { to: "/customers", label: "Customers", icon: FaUsers },
  { to: "/categories", label: "Categories", icon: FaTags },
  { to: "/products", label: "Products", icon: FaBoxes },
  { to: "/stocks", label: "Stocks", icon: FaWarehouse },
  { to: "/sales", label: "Sales", icon: FaCashRegister },
  { to: "/incomes", label: "Incomes", icon: FaMoneyBillWave },
  { to: "/expenses", label: "Expenses", icon: FaReceipt },
  { to: "/reports", label: "Reports", icon: FaChartBar },
];

const Layout = ({ cartTotal = 0 }) => {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const config = useConfig();
  const navigate = useNavigate();

  const userName = localStorage.getItem("userName") || "Admin";
  const currencySign = config.currencySign || "$";

  const handleLogout = async () => {
    const token = localStorage.getItem("authToken");
    try {
      await logout(token);
    } catch {
      // proceed with local logout even if API call fails
    } finally {
      localStorage.clear();
      navigate("/login");
    }
  };

  const sidebarContent = (
    <>
      <div className="flex items-center justify-center h-16 text-xl font-bold tracking-wide text-white border-b border-indigo-600">
        {config.projectName || "ERP System"}
      </div>
      <nav className="flex-1 px-2 py-4 space-y-1 overflow-y-auto">
        {navItems.map(({ to, label, icon: Icon }) => (
          <NavLink
            key={to}
            to={to}
            onClick={() => setSidebarOpen(false)}
            className={({ isActive }) =>
              `flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors ${
                isActive
                  ? "bg-indigo-800 text-white"
                  : "text-indigo-100 hover:bg-indigo-600 hover:text-white"
              }`
            }
          >
            <Icon className="w-4 h-4 shrink-0" />
            {label}
          </NavLink>
        ))}
      </nav>
    </>
  );

  return (
    <div className="flex h-screen overflow-hidden bg-gray-100">
      {/* Mobile sidebar overlay */}
      {sidebarOpen && (
        <div
          className="fixed inset-0 z-30 bg-black/50 lg:hidden"
          onClick={() => setSidebarOpen(false)}
        />
      )}

      {/* Sidebar */}
      <aside
        className={`fixed inset-y-0 left-0 z-40 flex flex-col w-64 bg-indigo-700 transform transition-transform duration-200 ease-in-out lg:static lg:translate-x-0 ${
          sidebarOpen ? "translate-x-0" : "-translate-x-full"
        }`}
      >
        <div className="relative flex items-center justify-center h-16 text-xl font-bold tracking-wide text-white border-b border-indigo-600">
          {config.projectName || "ERP System"}
          <button
            className="absolute right-2 text-indigo-200 hover:text-white lg:hidden"
            onClick={() => setSidebarOpen(false)}
          >
            <FaTimes className="w-5 h-5" />
          </button>
        </div>
        <nav className="flex-1 px-2 py-4 space-y-1 overflow-y-auto">
          {navItems.map(({ to, label, icon: Icon }) => (
            <NavLink
              key={to}
              to={to}
              onClick={() => setSidebarOpen(false)}
              className={({ isActive }) =>
                `flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium transition-colors ${
                  isActive
                    ? "bg-indigo-800 text-white"
                    : "text-indigo-100 hover:bg-indigo-600 hover:text-white"
                }`
              }
            >
              <Icon className="w-4 h-4 shrink-0" />
              {label}
            </NavLink>
          ))}
        </nav>
      </aside>

      {/* Main area */}
      <div className="flex flex-col flex-1 min-w-0">
        {/* Header */}
        <header className="sticky top-0 z-20 flex items-center justify-between h-16 px-4 bg-white border-b border-gray-200 shadow-sm sm:px-6">
          <div className="flex items-center gap-3">
            <button
              className="text-gray-500 hover:text-gray-700 lg:hidden"
              onClick={() => setSidebarOpen(true)}
            >
              <FaBars className="w-5 h-5" />
            </button>
            <h2 className="hidden text-lg font-semibold text-gray-700 sm:block">
              {config.projectName || "ERP System"}
            </h2>
          </div>

          <div className="flex items-center gap-4">
            <div className="flex items-center gap-2 text-sm font-medium text-gray-600">
              <FaShoppingCart className="w-4 h-4 text-indigo-600" />
              <span>
                {currencySign}
                {Number(cartTotal).toFixed(2)}
              </span>
            </div>

            <span className="hidden text-sm font-medium text-gray-700 sm:inline">
              {userName}
            </span>

            <button
              onClick={handleLogout}
              className="flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 transition-colors"
            >
              <FaSignOutAlt className="w-3.5 h-3.5" />
              <span className="hidden sm:inline">Sign Out</span>
            </button>
          </div>
        </header>

        {/* Page content */}
        <main className="flex-1 overflow-y-auto p-4 sm:p-6">
          <Outlet />
        </main>
      </div>
    </div>
  );
};

export default Layout;
