import { TrendingUp, TrendingDown, Award } from "lucide-react";

export default function PerformanceHighlight({ summary, trend, loading }) {

  const overall = summary?.performance?.average_percentage ?? 0;
  const hasTrend = typeof trend === "number" && trend !== 0;

  return (
    <div className="bg-gradient-to-r from-indigo-600 to-blue-500 text-white rounded-2xl p-6 flex justify-between items-center">

      <div>
        <p className="opacity-80 text-sm">Overall Performance</p>
        <h2 className="text-3xl font-bold mt-1">{loading ? "-" : `${overall}%`}</h2>

        {!loading && hasTrend && (
          <div className="flex items-center gap-2 mt-2 text-sm">
            {trend > 0 ? <TrendingUp size={16}/> : <TrendingDown size={16}/>}
            {trend > 0 ? "Improved" : "Declined"} by {Math.abs(trend)}% since your first evaluated attempt
          </div>
        )}
      </div>

      <div className="bg-white/20 p-4 rounded-xl">
        <Award size={36}/>
      </div>

    </div>
  );
}
