import { Trophy, BarChart3, CheckCircle } from "lucide-react";

export default function ReportStats({ summary, loading }) {

  const totals = summary?.totals || {};
  const performance = summary?.performance || {};

  const stats = [
    { title: "Total Exams", value: totals.assessments ?? 0, icon: BarChart3 },
    { title: "Average Score", value: `${performance.average_percentage ?? 0}%`, icon: Trophy },
    { title: "Pass Rate", value: `${performance.pass_rate ?? 0}%`, icon: CheckCircle },
  ];

  return (
    <div className="grid md:grid-cols-3 gap-6">

      {stats.map((s, i) => {
        const Icon = s.icon;
        return (
          <div key={i} className="bg-white p-5 rounded-2xl shadow-sm flex justify-between items-center">
            <div>
              <p className="text-gray-500 text-sm">{s.title}</p>
              <h2 className="text-2xl font-bold">{loading ? "-" : s.value}</h2>
            </div>

            <div className="bg-blue-100 p-3 rounded-xl text-blue-600">
              <Icon size={22}/>
            </div>
          </div>
        );
      })}

    </div>
  );
}
