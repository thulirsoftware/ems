import { Trophy } from "lucide-react";

export default function TopPerformers({ students = [] }) {
  return (
    <div className="bg-white border rounded-xl p-6">

      <div className="flex items-center gap-2 mb-4">
        <Trophy className="text-yellow-500"/>
        <h3 className="font-semibold">Top Performers</h3>
      </div>

      {students.length === 0 && (
        <p className="text-sm text-gray-400 py-3">No evaluated attempts yet</p>
      )}

      {students.map((s, i) => (
        <div
          key={s.user_id ?? i}
          className="flex justify-between py-3 border-b last:border-none"
        >
          <span className="font-medium">
            #{i + 1} {s.name}
          </span>

          <span className="text-indigo-600 font-semibold">
            {s.average_percentage}%
          </span>
        </div>
      ))}
    </div>
  );
}
