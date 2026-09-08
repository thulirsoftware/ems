import { TrendingUp, Award } from "lucide-react";

export default function PerformanceHighlight() {
  return (
    <div className="bg-gradient-to-r from-indigo-600 to-blue-500 text-white rounded-2xl p-6 flex justify-between items-center">

      <div>
        <p className="opacity-80 text-sm">Overall Performance</p>
        <h2 className="text-3xl font-bold mt-1">82%</h2>

        <div className="flex items-center gap-2 mt-2 text-sm">
          <TrendingUp size={16}/>
          Improved by 12% from last month
        </div>
      </div>

      <div className="bg-white/20 p-4 rounded-xl">
        <Award size={36}/>
      </div>

    </div>
  );
}