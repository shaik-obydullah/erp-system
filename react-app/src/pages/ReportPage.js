import { useState, useEffect } from "react";
import { useConfig } from "../contexts/ConfigContext";
import { fetchReports } from "../api/axios";
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, LineElement, PointElement, ArcElement, Title, Tooltip, Legend, Filler } from "chart.js";
import { Line, Bar, Pie } from "react-chartjs-2";
import { FaUsers, FaBoxes, FaShoppingCart, FaChartLine } from "react-icons/fa";

ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, ArcElement, Title, Tooltip, Legend, Filler);

const PIE_COLORS = [
  "#4F46E5", "#10B981", "#F59E0B", "#EF4444", "#8B5CF6",
  "#EC4899", "#06B6D4", "#84CC16", "#F97316", "#6366F1",
];

export default function ReportPage() {
  const config = useConfig();
  const currencySign = config.currencySign || "$";

  const [report, setReport] = useState(null);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState("overview");

  useEffect(() => {
    const load = async () => {
      setLoading(true);
      try {
        const res = await fetchReports({});
        setReport(res.data || res);
      } catch {
        setReport(null);
      } finally {
        setLoading(false);
      }
    };
    load();
  }, []);

  if (loading) {
    return <div className="flex items-center justify-center h-64"><p className="text-gray-500 text-lg">Loading reports...</p></div>;
  }

  if (!report) {
    return <div className="flex items-center justify-center h-64"><p className="text-gray-500 text-lg">Failed to load reports</p></div>;
  }

  const salesTrend = report.sales_trend || [];
  const incomeVsExpense = report.income_vs_expenses || {};
  const profitAnalysis = report.profit_analysis || {};
  const incomePie = report.income_pie || [];
  const expensePie = report.expense_pie || [];
  const annualPerformance = report.annual_performance || {};

  const salesTrendData = {
    labels: salesTrend.map((s) => s.label || s.month || s.date || ""),
    datasets: [{
      label: "Sales",
      data: salesTrend.map((s) => s.value || s.total || 0),
      borderColor: "#4F46E5",
      backgroundColor: "rgba(79,70,229,0.1)",
      fill: true,
      tension: 0.4,
    }],
  };

  const incomeVsExpenseData = {
    labels: incomeVsExpense.labels || [],
    datasets: [
      { label: "Income", data: incomeVsExpense.income || [], backgroundColor: "#10B981" },
      { label: "Expenses", data: incomeVsExpense.expenses || [], backgroundColor: "#EF4444" },
    ],
  };

  const profitData = {
    labels: profitAnalysis.labels || [],
    datasets: [{
      label: "Profit",
      data: profitAnalysis.values || profitAnalysis.profit || [],
      backgroundColor: "#4F46E5",
    }],
  };

  const incomePieData = {
    labels: incomePie.map((p) => p.label || p.month || ""),
    datasets: [{
      data: incomePie.map((p) => p.value || p.total || 0),
      backgroundColor: PIE_COLORS.slice(0, incomePie.length),
    }],
  };

  const expensePieData = {
    labels: expensePie.map((p) => p.label || p.month || ""),
    datasets: [{
      data: expensePie.map((p) => p.value || p.total || 0),
      backgroundColor: PIE_COLORS.slice(0, expensePie.length),
    }],
  };

  const lineOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: "top" } }, scales: { y: { beginAtZero: true } } };
  const barOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: "top" } }, scales: { y: { beginAtZero: true } } };
  const pieOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: "right" } } };

  const tabs = [
    { key: "overview", label: "Overview" },
    { key: "sales", label: "Sales" },
    { key: "financials", label: "Financials" },
  ];

  const summaryCards = [
    { label: "Total Customers", value: report.total_customers || 0, icon: FaUsers, color: "indigo" },
    { label: "Stock Value", value: `${currencySign}${Number(report.stock_value || 0).toFixed(2)}`, icon: FaBoxes, color: "green" },
    { label: "Cart Total", value: `${currencySign}${Number(report.cart_total || 0).toFixed(2)}`, icon: FaShoppingCart, color: "yellow" },
    { label: "Sales Growth", value: `${Number(report.sales_growth || 0).toFixed(1)}%`, icon: FaChartLine, color: "blue" },
  ];

  const colorMap = { indigo: "bg-indigo-100 text-indigo-600", green: "bg-green-100 text-green-600", yellow: "bg-yellow-100 text-yellow-600", blue: "bg-blue-100 text-blue-600" };

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-gray-800">Reports</h1>

      <div className="flex gap-1 border-b border-gray-200">
        {tabs.map((t) => (
          <button
            key={t.key}
            onClick={() => setActiveTab(t.key)}
            className={`px-4 py-2.5 text-sm font-medium border-b-2 transition-colors ${
              activeTab === t.key
                ? "border-indigo-600 text-indigo-600"
                : "border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
            }`}
          >
            {t.label}
          </button>
        ))}
      </div>

      {activeTab === "overview" && (
        <div className="space-y-6">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {summaryCards.map((card) => (
              <div key={card.label} className="bg-white rounded-lg shadow p-4 flex items-center gap-4">
                <div className={`p-3 rounded-full ${colorMap[card.color]}`}><card.icon className="w-5 h-5" /></div>
                <div><p className="text-sm text-gray-500">{card.label}</p><p className="text-xl font-bold text-gray-800">{card.value}</p></div>
              </div>
            ))}
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div className="bg-white rounded-lg shadow p-4">
              <h3 className="text-sm font-semibold text-gray-700 mb-3">Sales Trend</h3>
              <div className="h-64"><Line data={salesTrendData} options={lineOptions} /></div>
            </div>
            <div className="bg-white rounded-lg shadow p-4">
              <h3 className="text-sm font-semibold text-gray-700 mb-3">Income vs Expenses</h3>
              <div className="h-64"><Bar data={incomeVsExpenseData} options={barOptions} /></div>
            </div>
          </div>
        </div>
      )}

      {activeTab === "sales" && (
        <div className="space-y-6">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div className="bg-white rounded-lg shadow p-4">
              <h3 className="text-sm font-semibold text-gray-700 mb-3">Sales Trend</h3>
              <div className="h-80"><Line data={salesTrendData} options={lineOptions} /></div>
            </div>
            <div className="bg-white rounded-lg shadow p-4">
              <h3 className="text-sm font-semibold text-gray-700 mb-3">Profit Analysis</h3>
              <div className="h-80"><Bar data={profitData} options={barOptions} /></div>
            </div>
          </div>
        </div>
      )}

      {activeTab === "financials" && (
        <div className="space-y-6">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div className="bg-white rounded-lg shadow p-4">
              <h3 className="text-sm font-semibold text-gray-700 mb-3">Income vs Expenses</h3>
              <div className="h-80"><Bar data={incomeVsExpenseData} options={barOptions} /></div>
            </div>
            <div className="bg-white rounded-lg shadow p-4">
              <h3 className="text-sm font-semibold text-gray-700 mb-3">Profit Analysis</h3>
              <div className="h-80"><Bar data={profitData} options={barOptions} /></div>
            </div>
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div className="bg-white rounded-lg shadow p-4">
              <h3 className="text-sm font-semibold text-gray-700 mb-3">Income by Month</h3>
              <div className="h-80"><Pie data={incomePieData} options={pieOptions} /></div>
            </div>
            <div className="bg-white rounded-lg shadow p-4">
              <h3 className="text-sm font-semibold text-gray-700 mb-3">Expenses by Month</h3>
              <div className="h-80"><Pie data={expensePieData} options={pieOptions} /></div>
            </div>
          </div>

          {annualPerformance.years && annualPerformance.years.length > 0 && (
            <div className="bg-white rounded-lg shadow p-5">
              <h3 className="text-sm font-semibold text-gray-700 mb-4">Annual Performance Summary</h3>
              <div className="overflow-x-auto">
                <table className="w-full text-sm text-left">
                  <thead className="bg-gray-50 text-gray-600 uppercase text-xs">
                    <tr>
                      <th className="px-4 py-2">Year</th>
                      <th className="px-4 py-2 text-right">Revenue</th>
                      <th className="px-4 py-2 text-right">Expenses</th>
                      <th className="px-4 py-2 text-right">Profit</th>
                      <th className="px-4 py-2 text-right">YoY Change</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-200">
                    {annualPerformance.years.map((y, i) => {
                      const prev = annualPerformance.years[i - 1];
                      const yoy = prev ? (((y.profit || 0) - (prev.profit || 0)) / Math.abs(prev.profit || 1)) * 100 : null;
                      return (
                        <tr key={y.year || i} className="hover:bg-gray-50">
                          <td className="px-4 py-2 font-medium">{y.year}</td>
                          <td className="px-4 py-2 text-right text-green-600">{currencySign}{Number(y.revenue || 0).toFixed(2)}</td>
                          <td className="px-4 py-2 text-right text-red-600">{currencySign}{Number(y.expenses || 0).toFixed(2)}</td>
                          <td className="px-4 py-2 text-right font-medium">{currencySign}{Number(y.profit || 0).toFixed(2)}</td>
                          <td className={`px-4 py-2 text-right font-medium ${yoy !== null && yoy >= 0 ? "text-green-600" : "text-red-600"}`}>
                            {yoy !== null ? `${yoy >= 0 ? "+" : ""}${yoy.toFixed(1)}%` : "-"}
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
