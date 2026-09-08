import {
  Users,
  ClipboardList,
  Activity,
  TrendingUp,
  Target
} from "lucide-react";

export default function OverviewStrip() {

  const stats = [
    { label: "Students", value: 248, icon: Users },
    { label: "Active Exams", value: 14, icon: ClipboardList },
    { label: "Attempts Today", value: 96, icon: Activity },
    { label: "Completion Rate", value: "82%", icon: Target },
    { label: "Avg Score", value: "78%", icon: TrendingUp },
  ];

  return (
    <div className="bg-white border rounded-xl p-5 grid grid-cols-2 md:grid-cols-5 gap-6">

      {stats.map((s, i) => {
        const Icon = s.icon;
        return (
          <div key={i} className="flex items-center gap-3">

            <div className="bg-indigo-50 text-indigo-600 p-3 rounded-lg">
              <Icon size={20}/>
            </div>

            <div>
              <p className="text-xs text-gray-500">{s.label}</p>
              <h3 className="text-lg font-semibold">{s.value}</h3>
            </div>

          </div>
        );
      })}
    </div>
  );
}