import {
  ClipboardList,
  HelpCircle,
  Users,
  Activity,
  Trophy,
  TrendingUp,
} from "lucide-react";

export default function AssessmentPerformanceTable({ data = [] }) {
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

            {data.map((item) => (

              <tr
                key={item.id}
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
                  {item.average_score}
                </td>

                <td className="text-center text-green-600 font-semibold">
                  <div className="flex items-center justify-center gap-1">
                    <Trophy size={15} />
                    {item.highest_score}
                  </div>
                </td>

                <td className="text-center text-red-600 font-semibold">
                  {item.lowest_score}
                </td>

                <td className="text-center">

                  <span className="px-3 py-1 rounded-full bg-indigo-100 text-indigo-700 text-xs font-semibold">

                    {item.completion_rate}%

                  </span>

                </td>

              </tr>

            ))}

          </tbody>

        </table>

      </div>

    </div>
  );
}