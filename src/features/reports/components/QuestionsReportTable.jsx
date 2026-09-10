function answerSummary(row) {
  if (!row.answer) return "-";
  if (row.type === "descriptive") return row.answer.text || "-";
  if (row.answer.choice_id) return `Choice #${row.answer.choice_id}`;
  return "-";
}

function resultBadge(row) {
  if (!row.graded) {
    return (
      <span className="px-3 py-1 rounded-md text-xs bg-gray-100 text-gray-600">
        Pending
      </span>
    );
  }

  return row.is_correct ? (
    <span className="px-3 py-1 rounded-md text-xs bg-green-100 text-green-700">
      Correct
    </span>
  ) : (
    <span className="px-3 py-1 rounded-md text-xs bg-red-100 text-red-700">
      Wrong
    </span>
  );
}

export default function QuestionsReportTable({
  totals = {},
  rows = [],
  loading,
  hasMore,
  onLoadMore,
  loadingMore,
}) {
  const stats = [
    { label: "Answers", value: totals.answers ?? 0 },
    { label: "Graded", value: totals.graded ?? 0 },
    { label: "Correct", value: totals.correct ?? 0 },
    { label: "Wrong", value: totals.wrong ?? 0 },
    { label: "Accuracy", value: `${totals.accuracy ?? 0}%` },
  ];

  return (
    <div className="bg-white border rounded-xl">

      <div className="p-6 border-b space-y-4">
        <h3 className="font-semibold text-gray-700">
          Question-Level Breakdown
        </h3>

        <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
          {stats.map((s) => (
            <div key={s.label} className="bg-gray-50 rounded-lg p-3 text-center">
              <p className="text-xs text-gray-500">{s.label}</p>
              <p className="text-lg font-bold text-gray-800">{loading ? "-" : s.value}</p>
            </div>
          ))}
        </div>
      </div>

      <table className="w-full text-sm">
        <thead className="bg-gray-50 text-gray-600">
          <tr>
            <th className="text-left p-4">Exam</th>
            <th className="text-left p-4">Question</th>
            <th>Type</th>
            <th className="text-left p-4">Your Answer</th>
            <th>Result</th>
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
                No answers found
              </td>
            </tr>
          ) : (
            rows.map((r) => (
              <tr
                key={`${r.attempt_id}-${r.question_id}`}
                className="border-t hover:bg-gray-50"
              >
                <td className="p-4">{r.assessment_title}</td>
                <td className="p-4 max-w-xs truncate" title={r.question_text}>
                  {r.question_text}
                </td>
                <td align="center" className="capitalize">{r.type}</td>
                <td className="p-4 max-w-xs truncate" title={answerSummary(r)}>
                  {answerSummary(r)}
                </td>
                <td align="center">{resultBadge(r)}</td>
              </tr>
            ))
          )}
        </tbody>
      </table>

      {hasMore && (
        <div className="p-4 border-t">
          <button
            onClick={onLoadMore}
            disabled={loadingMore}
            className="w-full py-2 rounded-lg border border-blue-200 text-blue-600 font-medium hover:bg-blue-50 transition disabled:opacity-60"
          >
            {loadingMore ? "Loading..." : "Load More"}
          </button>
        </div>
      )}

    </div>
  );
}
