import { Users, ClipboardList, TrendingUp, Target } from "lucide-react";

export default function ReportSummary({ summary }) {
  const stats = [
    { label: "Total Students", value: summary?.totals?.candidates ?? 0, icon: Users },
    { label: "Total Attempts", value: summary?.attempts?.total ?? 0, icon: ClipboardList },
    { label: "Average Score", value: `${summary?.performance?.average_percentage ?? 0}%`, icon: TrendingUp },
    { label: "Pass Rate", value: `${summary?.performance?.pass_rate ?? 0}%`, icon: Target },
  ];

  return (
    <div className="grid md:grid-cols-4 gap-6">

      {stats.map((s, i) => {
        const Icon = s.icon;
        return (
          <div key={i} className="bg-white border rounded-xl p-5 flex items-center gap-4">
            <div className="bg-indigo-50 text-indigo-600 p-3 rounded-lg">
              <Icon size={20}/>
            </div>

            <div>
              <p className="text-xs text-gray-500">{s.label}</p>
              <h3 className="font-semibold text-lg">{s.value}</h3>
            </div>
          </div>
        );
      })}
    </div>
  );
}
