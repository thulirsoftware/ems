import { HelpCircle } from "lucide-react";
import ReportPagination from "./ReportPagination";

const difficultyStyle = {
  easy: "bg-green-100 text-green-700",
  medium: "bg-amber-100 text-amber-700",
  hard: "bg-red-100 text-red-700",
  not_attempted: "bg-gray-100 text-gray-500",
};

export default function QuestionsReportTable({ result, onPageChange }) {
  const rows = result?.data || [];

  return (
    <div className="bg-white rounded-2xl shadow-sm border border-gray-100">
      <div className="flex items-center justify-between p-6 border-b">
        <div>
          <h2 className="text-xl font-semibold flex items-center gap-2">
            <HelpCircle size={22} />
            Question Difficulty
          </h2>
          <p className="text-sm text-gray-500 mt-1">
            Which questions candidates are getting wrong
          </p>
        </div>
      </div>

      <div className="overflow-x-auto">
        <table className="w-full">
          <thead className="bg-gray-50">
            <tr>
              <th className="text-left px-6 py-4">Question</th>
              <th className="text-left py-4">Assessment</th>
              <th className="text-center py-4">Answered</th>
              <th className="text-center py-4">Correct</th>
              <th className="text-center py-4">Wrong</th>
              <th className="text-center py-4">Accuracy</th>
              <th className="text-center py-4">Difficulty</th>
            </tr>
          </thead>

          <tbody>
            {rows.length === 0 && (
              <tr>
                <td colSpan={7} className="text-center py-8 text-gray-400">
                  No questions found
                </td>
              </tr>
            )}

            {rows.map((row) => (
              <tr key={row.question_id} className="border-t hover:bg-gray-50 transition">
                <td className="px-6 py-4 font-medium max-w-xs truncate" title={row.question_text}>
                  {row.question_text}
                </td>
                <td className="py-4 text-gray-600">{row.assessment_title || "-"}</td>
                <td className="text-center">{row.answered}</td>
                <td className="text-center text-green-600 font-semibold">{row.correct}</td>
                <td className="text-center text-red-600 font-semibold">{row.wrong}</td>
                <td className="text-center">
                  {row.accuracy === null ? "-" : `${row.accuracy}%`}
                </td>
                <td className="text-center">
                  <span
                    className={`px-3 py-1 rounded-full text-xs font-semibold ${
                      difficultyStyle[row.difficulty] || difficultyStyle.not_attempted
                    }`}
                  >
                    {row.difficulty.replace("_", " ")}
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
