import { useState, useEffect } from "react";
import { useConfig } from "../contexts/ConfigContext";
import { fetchIncomes } from "../api/axios";
import { FaMoneyBillWave } from "react-icons/fa";

export default function IncomePage() {
  const config = useConfig();
  const currencySign = config.currencySign || "$";

  const [incomes, setIncomes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [startDate, setStartDate] = useState("");
  const [endDate, setEndDate] = useState("");

  const loadIncomes = async () => {
    setLoading(true);
    try {
      const res = await fetchIncomes({});
      setIncomes(res.data || res.incomes || []);
    } catch {
      setIncomes([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadIncomes();
  }, []);

  const filtered = incomes.filter((inc) => {
    const d = inc.date || inc.created_at || "";
    if (startDate && d < startDate) return false;
    if (endDate && d > endDate) return false;
    return true;
  });

  const runningTotal = filtered.reduce((sum, inc) => sum + Number(inc.amount || 0), 0);

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 className="text-2xl font-bold text-gray-800">Incomes</h1>
        <div className="flex items-center gap-2 px-4 py-2 bg-green-50 border border-green-200 rounded-lg">
          <FaMoneyBillWave className="w-4 h-4 text-green-600" />
          <span className="text-sm font-medium text-green-700">Total Income:</span>
          <span className="text-lg font-bold text-green-800">{currencySign}{runningTotal.toFixed(2)}</span>
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
                <tr><td colSpan="4" className="text-center py-8 text-gray-500">No incomes found</td></tr>
              ) : (
                filtered.map((inc, i) => (
                  <tr key={inc.id || i} className="hover:bg-gray-50">
                    <td className="px-4 py-3 text-gray-500">{i + 1}</td>
                    <td className="px-4 py-3 font-medium text-gray-800">{inc.description || "-"}</td>
                    <td className="px-4 py-3 text-gray-600">{inc.date || inc.created_at || "-"}</td>
                    <td className="px-4 py-3 text-right font-medium text-green-600">{currencySign}{Number(inc.amount || 0).toFixed(2)}</td>
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
