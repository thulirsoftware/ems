import { CheckCircle, Clock } from "lucide-react";

function formatDate(dateStr) {
  if (!dateStr) return "-";

  // submitted_at is stored as a TIME-only column (no date part), so it
  // can't always be parsed into a calendar date — fall back to the raw value.
  const date = new Date(dateStr);
  if (isNaN(date.getTime())) return dateStr;

  return date.toLocaleDateString(undefined, {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
}

export default function RecentResultsTable({ results = [], loading }) {

  return (
    <div className="bg-white rounded-2xl shadow-sm p-6">

      <h3 className="text-lg font-semibold mb-4">
        Recent Exam Results
      </h3>

      <div className="overflow-x-auto">
        <table className="w-full text-sm">

          <thead className="text-gray-500 border-b">
            <tr>
              <th className="text-left py-3">Exam</th>
              <th className="text-left">Date</th>
              <th className="text-left">Score</th>
              <th className="text-left">Status</th>
            </tr>
          </thead>

          <tbody>
            {loading ? (
              <tr>
                <td colSpan="4" className="text-center py-8 text-gray-400">
                  Loading...
                </td>
              </tr>
            ) : results.length === 0 ? (
              <tr>
                <td colSpan="4" className="text-center py-8 text-gray-400">
                  No submitted attempts yet
                </td>
              </tr>
            ) : (
              results.map((item, i) => (
                <tr
                  key={item.attempt_id ?? i}
                  className="border-b hover:bg-gray-50 transition"
                >
                  <td className="py-3 font-medium">
                    {item.assessment_title}
                  </td>

                  <td>{formatDate(item.submitted_at)}</td>

                  <td>
                    <span className="font-semibold">
                      {item.percentage !== null && item.percentage !== undefined
                        ? `${item.percentage}%`
                        : "-"}
                    </span>
                  </td>

                  <td>
                    {item.status === "evaluated" ? (
                      <span className="flex items-center gap-2 text-green-600">
                        <CheckCircle size={16} /> Evaluated
                      </span>
                    ) : (
                      <span className="flex items-center gap-2 text-orange-500">
                        <Clock size={16} /> Pending Evaluation
                      </span>
                    )}
                  </td>
                </tr>
              ))
            )}
          </tbody>

        </table>
      </div>
    </div>
  );
}
