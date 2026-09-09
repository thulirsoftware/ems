import { useState } from "react";

const emptyFilters = { status: "", from: "", to: "" };

export default function ReportFilters({ filters = emptyFilters, onApply }) {
  const [status, setStatus] = useState(filters.status || "");
  const [from, setFrom] = useState(filters.from || "");
  const [to, setTo] = useState(filters.to || "");

  const handleApply = () => {
    onApply?.({ status, from, to });
  };

  return (
    <div className="bg-white border rounded-xl p-4 flex flex-wrap gap-4">

      <select
        className="border rounded-md px-3 py-2 text-sm"
        value={status}
        onChange={(e) => setStatus(e.target.value)}
      >
        <option value="">All Attempts</option>
        <option value="in_progress">In Progress</option>
        <option value="pending_evaluation">Pending Evaluation</option>
        <option value="evaluated">Evaluated</option>
      </select>

      <input
        type="date"
        className="border rounded-md px-3 py-2 text-sm"
        value={from}
        onChange={(e) => setFrom(e.target.value)}
      />

      <input
        type="date"
        className="border rounded-md px-3 py-2 text-sm"
        value={to}
        onChange={(e) => setTo(e.target.value)}
      />

      <button
        onClick={handleApply}
        className="ml-auto bg-blue-600 text-white px-5 py-2 rounded-md text-sm"
      >
        Apply Filters
      </button>
    </div>
  );
}
