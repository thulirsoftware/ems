import { Trophy } from "lucide-react";

export default function TopPerformers() {

  const students = [
    { name: "Kiran", score: 96 },
    { name: "Arun", score: 92 },
    { name: "Meena", score: 90 },
  ];

  return (
    <div className="bg-white border rounded-xl p-6">

      <div className="flex items-center gap-2 mb-4">
        <Trophy className="text-yellow-500"/>
        <h3 className="font-semibold">Top Performers</h3>
      </div>

      {students.map((s, i) => (
        <div
          key={i}
          className="flex justify-between py-3 border-b last:border-none"
        >
          <span className="font-medium">
            #{i + 1} {s.name}
          </span>

          <span className="text-indigo-600 font-semibold">
            {s.score}%
          </span>
        </div>
      ))}
    </div>
  );
}