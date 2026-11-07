import { useState } from "react";
import { saveExpense } from "../api/axios";
import { FaReceipt } from "react-icons/fa";
import { toast } from "react-toastify";

export default function AddExpense({ onClose, onSaved }) {
  const [description, setDescription] = useState("");
  const [amount, setAmount] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError("");
    setLoading(true);
    try {
      await saveExpense({ description, amount: Number(amount) });
      toast.success("Expense recorded successfully");
      onSaved?.();
    } catch (err) {
      setError(err.message || "Failed to save expense");
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-5">
      <div className="flex items-center gap-3">
        <div className="p-3 bg-red-100 rounded-full"><FaReceipt className="w-5 h-5 text-red-600" /></div>
        <p className="text-sm text-gray-500">Record an expense paid outside the purchasing flow.</p>
      </div>

      {error && (
        <div className="p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm">{error}</div>
      )}

      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
        <input
          type="text"
          required
          value={description}
          onChange={(e) => setDescription(e.target.value)}
          className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none transition"
          placeholder="Enter expense description"
        />
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">Amount</label>
        <input
          type="number"
          required
          min="0"
          step="0.01"
          value={amount}
          onChange={(e) => setAmount(e.target.value)}
          className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none transition"
          placeholder="0.00"
        />
      </div>

      <div className="flex gap-3">
        <button
          type="submit"
          disabled={loading}
          className="flex-1 py-2.5 bg-red-600 hover:bg-red-700 disabled:bg-red-400 text-white font-semibold rounded-lg transition"
        >
          {loading ? "Saving..." : "Save Expense"}
        </button>
        <button
          type="button"
          onClick={onClose}
          className="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition"
        >
          Cancel
        </button>
      </div>
    </form>
  );
}
