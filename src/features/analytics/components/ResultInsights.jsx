import { Trophy, AlertTriangle, CheckCircle } from "lucide-react";

export default function ResultInsights({ assessments = [] }) {
  const evaluated = assessments.filter((a) => a.evaluated > 0);

  const top = evaluated.length
    ? evaluated.reduce((a, b) => (b.average_percentage > a.average_percentage ? b : a))
    : null;

  const worst = evaluated.length
    ? evaluated.reduce((a, b) => (b.average_percentage < a.average_percentage ? b : a))
    : null;

  const balanced = evaluated.length
    ? evaluated.reduce((a, b) =>
        Math.abs(b.pass_rate - 50) < Math.abs(a.pass_rate - 50) ? b : a
      )
    : null;

  const insights = [
    {
      title: "Top Performing Assessment",
      value: top?.title ?? "No data yet",
      icon: Trophy,
      color: "text-green-600 bg-green-50",
      desc: top ? `Average score ${top.average_percentage}%` : "Awaiting evaluated attempts",
    },
    {
      title: "Needs Attention",
      value: worst?.title ?? "No data yet",
      icon: AlertTriangle,
      color: "text-red-600 bg-red-50",
      desc: worst ? `Average score ${worst.average_percentage}%, ${worst.failed} failed` : "Awaiting evaluated attempts",
    },
    {
      title: "Most Balanced Assessment",
      value: balanced?.title ?? "No data yet",
      icon: CheckCircle,
      color: "text-indigo-600 bg-indigo-50",
      desc: balanced ? `${balanced.pass_rate}% pass rate` : "Awaiting evaluated attempts",
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
