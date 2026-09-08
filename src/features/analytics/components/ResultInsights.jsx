import { Trophy, AlertTriangle, CheckCircle } from "lucide-react";

export default function ResultInsights() {

  const insights = [
    {
      title: "Top Performing Exam",
      value: "SQL Evaluation",
      icon: Trophy,
      color: "text-green-600 bg-green-50",
      desc: "Average score 88%",
    },
    {
      title: "Needs Attention",
      value: "System Design",
      icon: AlertTriangle,
      color: "text-red-600 bg-red-50",
      desc: "High failure rate detected",
    },
    {
      title: "Balanced Assessment",
      value: "React Basics",
      icon: CheckCircle,
      color: "text-indigo-600 bg-indigo-50",
      desc: "Healthy pass/fail ratio",
    },
  ];

  return (
    <div className="bg-white border rounded-2xl p-6">

      <h3 className="font-semibold mb-6">
        Result Insights
      </h3>

      <div className="space-y-5">

        {insights.map((item, i) => {
          const Icon = item.icon;

          return (
            <div
              key={i}
              className="flex items-center gap-4 p-4 rounded-xl border hover:shadow-sm transition"
            >
              <div className={`p-3 rounded-lg ${item.color}`}>
                <Icon size={20}/>
              </div>

              <div>
                <p className="text-sm text-gray-500">
                  {item.title}
                </p>
                <h4 className="font-semibold">
                  {item.value}
                </h4>
                <p className="text-xs text-gray-400">
                  {item.desc}
                </p>
              </div>
            </div>
          );
        })}

      </div>
    </div>
  );
}