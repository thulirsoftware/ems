import { ClipboardCheck } from "lucide-react";

export default function ExamInsights() {

  const exams = [
    { name: "React Assessment", avg: 78 },
    { name: "Python Coding", avg: 72 },
    { name: "SQL Evaluation", avg: 83 },
  ];

  return (
    <div className="bg-white border rounded-xl p-6">

      <div className="flex items-center gap-2 mb-5">
        <ClipboardCheck size={20} className="text-indigo-600"/>
        <h3 className="font-semibold">
          Exam Performance Insights
        </h3>
      </div>

      {exams.map((e, i) => (
        <div key={i} className="mb-5">

          <div className="flex justify-between text-sm mb-1">
            <span>{e.name}</span>
            <span className="font-medium">{e.avg}%</span>
          </div>

          <div className="bg-gray-200 h-2 rounded">
            <div
              className="bg-indigo-600 h-2 rounded"
              style={{ width: `${e.avg}%` }}
            />
          </div>

        </div>
      ))}
    </div>
  );
}