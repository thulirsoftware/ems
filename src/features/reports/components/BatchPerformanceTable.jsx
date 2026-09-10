import {
  Layers,
  Users,
  Activity,
  Trophy,
  GraduationCap,
} from "lucide-react";
import ReportPagination from "./ReportPagination";

const statusStyle = {
  upcoming: "bg-blue-100 text-blue-700",
  running: "bg-green-100 text-green-700",
  finished: "bg-gray-100 text-gray-600",
  unscheduled: "bg-amber-100 text-amber-700",
};

export default function BatchPerformanceTable({ result, onPageChange }) {
  const data = result?.data || [];
  return (
    <div className="bg-white rounded-2xl shadow-sm border border-gray-100">

      <div className="flex justify-between items-center p-6 border-b">

        <div>

          <h2 className="text-xl font-semibold flex items-center gap-2">
            <Layers size={22} />
            Batch Performance
          </h2>

          <p className="text-sm text-gray-500 mt-1">
            Compare all batches
          </p>

        </div>

      </div>

      <div className="overflow-x-auto">

        <table className="w-full">

          <thead className="bg-gray-50">

            <tr>

              <th className="text-left px-6 py-4">
                Batch
              </th>

              <th className="text-left">
                Assessment
              </th>

              <th className="text-left">
                Schedule
              </th>

              <th className="text-center">
                Status
              </th>

              <th className="text-center">
                <Users size={18} className="mx-auto" />
              </th>

              <th className="text-center">
                <Activity size={18} className="mx-auto" />
              </th>

              <th className="text-center">
                Completion
              </th>

              <th className="text-center">
                Avg
              </th>

              <th className="text-center">
                Highest
              </th>

              <th className="text-center">
                Lowest
              </th>

              <th className="text-center">
                Pass Rate
              </th>

            </tr>

          </thead>

          <tbody>

            {data.length === 0 && (
              <tr>
                <td colSpan={11} className="text-center py-8 text-gray-400">
                  No batches found
                </td>
              </tr>
            )}

            {data.map((batch) => (

              <tr
                key={batch.batch_id}
                className="border-t hover:bg-gray-50 transition"
              >

                <td className="px-6 py-4">

                  <div className="flex items-center gap-2">

                    <GraduationCap
                      className="text-indigo-500"
                      size={18}
                    />

                    {batch.batch_name}

                  </div>

                </td>

                <td>

                  {batch.assessment_title}

                </td>

                <td className="text-gray-600 text-sm">
                  {batch.publish_date
                    ? `${batch.publish_date} · ${batch.start_time}-${batch.end_time}`
                    : "-"}
                  {batch.capacity && (
                    <span className="block text-xs text-gray-400">
                      Capacity: {batch.capacity}
                    </span>
                  )}
                </td>

                <td className="text-center">
                  <span
                    className={`px-3 py-1 rounded-full text-xs font-semibold capitalize ${
                      statusStyle[batch.status] || "bg-gray-100 text-gray-600"
                    }`}
                  >
                    {batch.status || "-"}
                  </span>
                </td>

                <td className="text-center">

                  {batch.assigned}

                </td>

                <td className="text-center">

                  {batch.attempts}

                </td>

                <td className="text-center">

                  <div className="w-24 mx-auto">

                    <div className="bg-gray-200 rounded-full h-2">

                      <div
                        className="bg-emerald-500 h-2 rounded-full"
                        style={{
                          width: `${batch.participation_rate}%`,
                        }}
                      />

                    </div>

                    <p className="text-xs mt-1 font-semibold">

                      {batch.participation_rate}%

                    </p>

                  </div>

                </td>

                <td className="text-center font-semibold text-indigo-600">

                  {batch.average_percentage}%

                </td>

                <td className="text-center text-green-600 font-semibold">

                  <div className="flex items-center justify-center gap-1">

                    <Trophy size={15} />

                    {batch.highest_percentage}%

                  </div>

                </td>

                <td className="text-center text-red-600 font-semibold">

                  {batch.lowest_percentage}%

                </td>

                <td className="text-center">
                  <span className="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-semibold">
                    {batch.pass_rate}%
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
