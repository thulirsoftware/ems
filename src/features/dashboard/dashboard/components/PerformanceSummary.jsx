import { TrendingUp, Target, AlertTriangle } from "lucide-react";

export default function PerformanceSummary() {

  const performance = {
    overall: 82,
    strong: {
      name: "SQL & Database",
      value: 90,
    },
    weak: {
      name: "Problem Solving",
      value: 60,
    },
    growth: 12,
  };

  return (
    <div className="bg-white rounded-2xl shadow-sm p-6">

      {/* HEADER */}
      <h3 className="font-semibold text-lg mb-6">
        Performance Analytics
      </h3>

      {/* OVERALL SCORE */}
      <div className="bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-xl p-4 mb-6">
        <p className="text-sm opacity-80">Overall Performance</p>
        <h2 className="text-3xl font-bold">
          {performance.overall}%
        </h2>
      </div>

      {/* STRONG AREA */}
      <div className="mb-5">
        <div className="flex justify-between mb-1">
          <div className="flex items-center gap-2 text-green-600 font-medium">
            <Target size={18}/>
            Strong Area
          </div>
          <span>{performance.strong.value}%</span>
        </div>

        <div className="w-full bg-gray-200 rounded-full h-2">
          <div
            className="bg-green-500 h-2 rounded-full"
            style={{ width: `${performance.strong.value}%` }}
          />
        </div>

        <p className="text-sm text-gray-500 mt-1">
          {performance.strong.name}
        </p>
      </div>

      {/* WEAK AREA */}
      <div className="mb-5">
        <div className="flex justify-between mb-1">
          <div className="flex items-center gap-2 text-orange-500 font-medium">
            <AlertTriangle size={18}/>
            Needs Improvement
          </div>
          <span>{performance.weak.value}%</span>
        </div>

        <div className="w-full bg-gray-200 rounded-full h-2">
          <div
            className="bg-orange-400 h-2 rounded-full"
            style={{ width: `${performance.weak.value}%` }}
          />
        </div>

        <p className="text-sm text-gray-500 mt-1">
          {performance.weak.name}
        </p>
      </div>

      {/* GROWTH */}
      <div className="flex items-center gap-3 bg-blue-50 text-blue-600 rounded-lg p-3 font-medium">
        <TrendingUp size={18}/>
        Performance improved by {performance.growth}% this month
      </div>

    </div>
  );
}