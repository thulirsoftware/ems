import { AlertTriangle } from "lucide-react";

export default function StudentRiskPanel() {

  const students = [
    { name: "Arun", score: 45 },
    { name: "Priya", score: 52 },
    { name: "Kiran", score: 48 },
  ];

  return (
    <div className="bg-white border rounded-xl p-6">

      <div className="flex items-center gap-2 mb-4">
        <AlertTriangle size={20} className="text-red-500"/>
        <h3 className="font-semibold">
          Students Needing Attention
        </h3>
      </div>

      {students.map((s, i) => (
        <div key={i} className="flex justify-between py-3 border-b last:border-none">
          <span>{s.name}</span>
          <span className="text-red-500 font-semibold">
            {s.score}%
          </span>
        </div>
      ))}
    </div>
  );
}