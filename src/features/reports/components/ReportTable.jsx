function statusLabel(row) {
  if (!row.evaluated) return "Not Evaluated";
  if (row.pass_rate === 100) return "Passed";
  if (row.pass_rate === 0) return "Failed";
  return "Mixed";
}

function statusClasses(row) {
  const label = statusLabel(row);

  if (label === "Passed") return "bg-green-100 text-green-700";
  if (label === "Failed") return "bg-red-100 text-red-700";
  if (label === "Mixed") return "bg-yellow-100 text-yellow-700";
  return "bg-gray-100 text-gray-600";
}

export default function ReportTable({ rows = [], loading }) {

  return (
    <div className="bg-white border rounded-xl">

      <div className="p-6 border-b">
        <h3 className="font-semibold text-gray-700">
          Detailed Exam Reports
        </h3>
      </div>

      <table className="w-full text-sm">
        <thead className="bg-gray-50 text-gray-600">
          <tr>
            <th className="text-left p-4">Exam</th>
            <th>Attempts</th>
            <th>Highest</th>
            <th>Average</th>
            <th>Status</th>
          </tr>
        </thead>

        <tbody>
          {loading ? (
            <tr>
              <td colSpan="5" className="text-center py-8 text-gray-400">
                Loading...
              </td>
            </tr>
          ) : rows.length === 0 ? (
            <tr>
              <td colSpan="5" className="text-center py-8 text-gray-400">
                No assessments found
              </td>
            </tr>
          ) : (
            rows.map((r) => (
              <tr key={r.assessment_id} className="border-t hover:bg-gray-50">
                <td className="p-4 font-medium">{r.assessment_title}</td>
                <td align="center">{r.attempts}</td>
                <td align="center">{r.highest_percentage ?? "-"}{r.highest_percentage !== null && r.highest_percentage !== undefined ? "%" : ""}</td>
                <td align="center">{r.average_percentage ?? "-"}{r.average_percentage !== null && r.average_percentage !== undefined ? "%" : ""}</td>
                <td align="center">
                  <span className={`px-3 py-1 rounded-md text-xs ${statusClasses(r)}`}>
                    {statusLabel(r)}
                  </span>
                </td>
              </tr>
            ))
          )}
        </tbody>
      </table>

    </div>
  );
}
