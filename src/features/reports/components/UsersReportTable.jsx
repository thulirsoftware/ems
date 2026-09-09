import { Users } from "lucide-react";
import ReportPagination from "./ReportPagination";

export default function UsersReportTable({ result, onPageChange }) {
  const rows = result?.data || [];

  return (
    <div className="bg-white rounded-2xl shadow-sm border border-gray-100">
      <div className="flex items-center justify-between p-6 border-b">
        <div>
          <h2 className="text-xl font-semibold flex items-center gap-2">
            <Users size={22} />
            Candidate Performance
          </h2>
          <p className="text-sm text-gray-500 mt-1">
            One row per candidate matching the filters
          </p>
        </div>
      </div>

      <div className="overflow-x-auto">
        <table className="w-full">
          <thead className="bg-gray-50">
            <tr>
              <th className="text-left px-6 py-4">Candidate</th>
              <th className="text-left py-4">Email</th>
              <th className="text-center py-4">Assigned</th>
              <th className="text-center py-4">Attempts</th>
              <th className="text-center py-4">Submitted</th>
              <th className="text-center py-4">Avg %</th>
              <th className="text-center py-4">Pass Rate</th>
            </tr>
          </thead>

          <tbody>
            {rows.length === 0 && (
              <tr>
                <td colSpan={7} className="text-center py-8 text-gray-400">
                  No candidates found
                </td>
              </tr>
            )}

            {rows.map((row) => (
              <tr key={row.user_id} className="border-t hover:bg-gray-50 transition">
                <td className="px-6 py-4 font-medium">{row.name || "-"}</td>
                <td className="py-4 text-gray-600">{row.email || "-"}</td>
                <td className="text-center">{row.assigned}</td>
                <td className="text-center">{row.attempts}</td>
                <td className="text-center">{row.submitted}</td>
                <td className="text-center font-semibold text-indigo-600">
                  {row.average_percentage}%
                </td>
                <td className="text-center">
                  <span className="px-3 py-1 rounded-full bg-indigo-100 text-indigo-700 text-xs font-semibold">
                    {row.pass_rate}%
                  </span>
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
