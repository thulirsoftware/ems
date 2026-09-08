import { Trophy, BarChart3, CheckCircle } from "lucide-react";

export default function ReportStats() {

  const stats = [
    { title: "Total Exams", value: 24, icon: BarChart3 },
    { title: "Average Score", value: "81%", icon: Trophy },
    { title: "Pass Rate", value: "87%", icon: CheckCircle },
  ];

  return (
    <div className="grid md:grid-cols-3 gap-6">

      {stats.map((s, i) => {
        const Icon = s.icon;
        return (
          <div key={i} className="bg-white p-5 rounded-2xl shadow-sm flex justify-between items-center">
            <div>
              <p className="text-gray-500 text-sm">{s.title}</p>
              <h2 className="text-2xl font-bold">{s.value}</h2>
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