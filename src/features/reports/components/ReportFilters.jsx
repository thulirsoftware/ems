export default function ReportFilters() {
  return (
    <div className="bg-white border rounded-xl p-4 flex flex-wrap gap-4">

      <select className="border rounded-md px-3 py-2 text-sm">
        <option>All Exams</option>
        <option>React Assessment</option>
        <option>Python Test</option>
      </select>

      <select className="border rounded-md px-3 py-2 text-sm">
        <option>All Attempts</option>
        <option>Passed</option>
        <option>Failed</option>
      </select>

      <input
        type="date"
        className="border rounded-md px-3 py-2 text-sm"
      />

      <button className="ml-auto bg-blue-600 text-white px-5 py-2 rounded-md text-sm">
        Apply Filters
      </button>
    </div>
  );
}