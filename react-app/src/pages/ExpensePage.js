import { useState, useEffect } from "react";
import { useConfig } from "../contexts/ConfigContext";
import { fetchExpenses } from "../api/axios";
import { FaReceipt } from "react-icons/fa";

export default function ExpensePage() {
  const config = useConfig();
  const currencySign = config.currencySign || "$";

  const [expenses, setExpenses] = useState([]);
  const [loading, setLoading] = useState(true);
  const [startDate, setStartDate] = useState("");
  const [endDate, setEndDate] = useState("");

  const loadExpenses = async () => {
    setLoading(true);
    try {
      const res = await fetchExpenses({});
      setExpenses(res.data || res.expenses || []);
    } catch {
      setExpenses([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadExpenses();
  }, []);

  const filtered = expenses.filter((exp) => {
    const d = exp.date || exp.created_at || "";
    if (startDate && d < startDate) return false;
    if (endDate && d > endDate) return false;
    return true;
  });

  const runningTotal = filtered.reduce((sum, exp) => sum + Number(exp.amount || 0), 0);

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 className="text-2xl font-bold text-gray-800">Expenses</h1>
        <div className="flex items-center gap-2 px-4 py-2 bg-red-50 border border-red-200 rounded-lg">
          <FaReceipt className="w-4 h-4 text-red-600" />
          <span className="text-sm font-medium text-red-700">Total Expenses:</span>
          <span className="text-lg font-bold text-red-800">{currencySign}{runningTotal.toFixed(2)}</span>
        </div>
      </div>

      <div className="bg-white rounded-lg shadow p-4">
        <div className="flex flex-col sm:flex-row gap-3 mb-4">
          <div className="flex items-center gap-2">
            <label className="text-sm text-gray-600">From:</label>
            <input
              type="date"
              value={startDate}
              onChange={(e) => setStartDate(e.target.value)}
              className="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
            />
          </div>
          <div className="flex items-center gap-2">
            <label className="text-sm text-gray-600">To:</label>
            <input
              type="date"
              value={endDate}
              onChange={(e) => setEndDate(e.target.value)}
              className="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
            />
          </div>
          {(startDate || endDate) && (
            <button
              onClick={() => { setStartDate(""); setEndDate(""); }}
              className="text-sm text-indigo-600 hover:text-indigo-800 underline"
            >
              Clear Filter
            </button>
          )}
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left">
            <thead className="bg-gray-50 text-gray-600 uppercase text-xs">
              <tr>
                <th className="px-4 py-3">#</th>
                <th className="px-4 py-3">Description</th>
                <th className="px-4 py-3">Date</th>
                <th className="px-4 py-3 text-right">Amount</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {loading ? (
                <tr><td colSpan="4" className="text-center py-8 text-gray-500">Loading...</td></tr>
              ) : filtered.length === 0 ? (
                <tr><td colSpan="4" className="text-center py-8 text-gray-500">No expenses found</td></tr>
              ) : (
                filtered.map((exp, i) => (
                  <tr key={exp.id || i} className="hover:bg-gray-50">
                    <td className="px-4 py-3 text-gray-500">{i + 1}</td>
                    <td className="px-4 py-3 font-medium text-gray-800">{exp.description || "-"}</td>
                    <td className="px-4 py-3 text-gray-600">{exp.date || exp.created_at || "-"}</td>
                    <td className="px-4 py-3 text-right font-medium text-red-600">{currencySign}{Number(exp.amount || 0).toFixed(2)}</td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
