import {
  ClipboardList,
  HelpCircle,
  Users,
  Activity,
  Trophy,
} from "lucide-react";
import ReportPagination from "./ReportPagination";

export default function AssessmentPerformanceTable({ result, onPageChange }) {
  const data = result?.data || [];
  return (
    <div className="bg-white rounded-2xl shadow-sm border border-gray-100">

      <div className="flex items-center justify-between p-6 border-b">

        <div>

          <h2 className="text-xl font-semibold flex items-center gap-2">
            <ClipboardList size={22} />
            Assessment Performance
          </h2>

          <p className="text-sm text-gray-500 mt-1">
            Overall performance of all assessments
          </p>

        </div>

      </div>

      <div className="overflow-x-auto">

        <table className="w-full">

          <thead className="bg-gray-50">

            <tr>

              <th className="text-left px-6 py-4">Assessment</th>

              <th className="text-center py-4">
                <HelpCircle size={18} className="mx-auto" />
              </th>

              <th className="text-center py-4">
                <Users size={18} className="mx-auto" />
              </th>

              <th className="text-center py-4">
                <Activity size={18} className="mx-auto" />
              </th>

              <th className="text-center py-4">
                Avg
              </th>

              <th className="text-center py-4">
                High
              </th>

              <th className="text-center py-4">
                Low
              </th>

              <th className="text-center py-4">
                Completion
              </th>

            </tr>

          </thead>

          <tbody>

            {data.length === 0 && (
              <tr>
                <td colSpan={8} className="text-center py-8 text-gray-400">
                  No assessments found
                </td>
              </tr>
            )}

            {data.map((item) => {
              const completionRate = item.assigned > 0
                ? Math.round((item.submitted / item.assigned) * 100)
                : 0;

              return (
                <tr
                  key={item.assessment_id}
                  className="border-t hover:bg-gray-50 transition"
                >

                  <td className="px-6 py-4 font-medium">
                    {item.title}
                  </td>

                  <td className="text-center">
                    {item.questions}
                  </td>

                  <td className="text-center">
                    {item.batches}
                  </td>

                  <td className="text-center">
                    {item.attempts}
                  </td>

                  <td className="text-center font-semibold text-indigo-600">
                    {item.average_percentage}%
                  </td>

                  <td className="text-center text-green-600 font-semibold">
                    <div className="flex items-center justify-center gap-1">
                      <Trophy size={15} />
                      {item.highest_percentage}%
                    </div>
                  </td>

                  <td className="text-center text-red-600 font-semibold">
                    {item.lowest_percentage}%
                  </td>

                  <td className="text-center">

                    <span className="px-3 py-1 rounded-full bg-indigo-100 text-indigo-700 text-xs font-semibold">

                      {completionRate}%

                    </span>

                  </td>

                </tr>
              );
            })}

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