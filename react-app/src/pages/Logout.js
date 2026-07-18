import { useEffect } from "react";
import { Navigate } from "react-router-dom";
import { logout } from "../api/axios";

export default function Logout() {
  useEffect(() => {
    const token = localStorage.getItem("authToken");
    if (token) {
      logout(token).catch(() => {});
    }
    localStorage.removeItem("authToken");
    localStorage.removeItem("userName");
  }, []);

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-100">
      <div className="text-center">
        <svg
          className="animate-spin h-8 w-8 text-blue-600 mx-auto mb-4"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle
            className="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            strokeWidth="4"
          />
          <path
            className="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
          />
        </svg>
        <p className="text-gray-600 text-lg">Logging out...</p>
      </div>
      <Navigate to="/login" replace />
    </div>
  );
}
