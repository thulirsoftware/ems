import { Activity } from "lucide-react";
import ReportPagination from "./ReportPagination";

const statusStyle = {
  in_progress: "bg-blue-100 text-blue-700",
  pending_evaluation: "bg-amber-100 text-amber-700",
  evaluated: "bg-green-100 text-green-700",
};

export default function AttemptsReportTable({ result, onPageChange }) {
  const rows = result?.data || [];

  return (
    <div className="bg-white rounded-2xl shadow-sm border border-gray-100">
      <div className="flex items-center justify-between p-6 border-b">
        <div>
          <h2 className="text-xl font-semibold flex items-center gap-2">
            <Activity size={22} />
            Attempt Log
          </h2>
          <p className="text-sm text-gray-500 mt-1">
            Every attempt matching the filters
          </p>
        </div>
      </div>

      <div className="overflow-x-auto">
        <table className="w-full">
          <thead className="bg-gray-50">
            <tr>
              <th className="text-left px-6 py-4">Candidate</th>
              <th className="text-left py-4">Assessment</th>
              <th className="text-left py-4">Batch</th>
              <th className="text-left py-4">Date</th>
              <th className="text-center py-4">Status</th>
              <th className="text-center py-4">Score</th>
              <th className="text-center py-4">Passed</th>
            </tr>
          </thead>

          <tbody>
            {rows.length === 0 && (
              <tr>
                <td colSpan={7} className="text-center py-8 text-gray-400">
                  No attempts found
                </td>
              </tr>
            )}

            {rows.map((row) => (
              <tr key={row.attempt_id} className="border-t hover:bg-gray-50 transition">
                <td className="px-6 py-4">
                  <p className="font-medium">{row.user_name || "-"}</p>
                  <p className="text-xs text-gray-500">{row.user_email}</p>
                </td>
                <td className="py-4 text-gray-600">{row.assessment_title || "-"}</td>
                <td className="py-4 text-gray-600">{row.batch_name || "-"}</td>
                <td className="py-4 text-gray-600">{row.attempt_date || "-"}</td>
                <td className="text-center">
                  <span
                    className={`px-3 py-1 rounded-full text-xs font-semibold ${
                      statusStyle[row.status] || "bg-gray-100 text-gray-600"
                    }`}
                  >
                    {row.status.replace("_", " ")}
                  </span>
                </td>
                <td className="text-center font-semibold text-indigo-600">
                  {row.score !== null
                    ? `${row.score}/${row.total_marks} (${row.percentage}%)`
                    : "-"}
                </td>
                <td className="text-center">
                  {row.passed === null ? (
                    "-"
                  ) : row.passed ? (
                    <span className="text-green-600 font-semibold">Yes</span>
                  ) : (
                    <span className="text-red-600 font-semibold">No</span>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <ReportPagination
        currentPage={result?.current_page}
        totalPages={result?.total_pages}
        total={result?.total}
        onPageChange={onPageChange}
      />
    </div>
  );
}
