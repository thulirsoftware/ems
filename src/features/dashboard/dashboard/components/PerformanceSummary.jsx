import { Target, AlertTriangle, Clock } from "lucide-react";

export default function PerformanceSummary({ stats = {}, loading }) {

  const overall = stats.average_percentage ?? 0;
  const best = stats.best_percentage ?? 0;
  const lowest = stats.lowest_percentage ?? 0;
  const pendingEvaluation = stats.pending_evaluation ?? 0;

  return (
    <div className="bg-white rounded-2xl shadow-sm p-6">

      {/* HEADER */}
      <h3 className="font-semibold text-lg mb-6">
        Performance Analytics
      </h3>

      {loading ? (
        <p className="text-gray-400 text-sm">Loading...</p>
      ) : (
        <>
          {/* OVERALL SCORE */}
          <div className="bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-xl p-4 mb-6">
            <p className="text-sm opacity-80">Overall Performance</p>
            <h2 className="text-3xl font-bold">
              {overall}%
            </h2>
          </div>

          {/* BEST SCORE */}
          <div className="mb-5">
            <div className="flex justify-between mb-1">
              <div className="flex items-center gap-2 text-green-600 font-medium">
                <Target size={18}/>
                Best Score
              </div>
              <span>{best}%</span>
            </div>

            <div className="w-full bg-gray-200 rounded-full h-2">
              <div
                className="bg-green-500 h-2 rounded-full"
                style={{ width: `${best}%` }}
              />
            </div>
          </div>

          {/* LOWEST SCORE */}
          <div className="mb-5">
            <div className="flex justify-between mb-1">
              <div className="flex items-center gap-2 text-orange-500 font-medium">
                <AlertTriangle size={18}/>
                Lowest Score
              </div>
              <span>{lowest}%</span>
            </div>

            <div className="w-full bg-gray-200 rounded-full h-2">
              <div
                className="bg-orange-400 h-2 rounded-full"
                style={{ width: `${lowest}%` }}
              />
            </div>
          </div>

          {pendingEvaluation > 0 && (
            <div className="flex items-center gap-3 bg-blue-50 text-blue-600 rounded-lg p-3 font-medium">
              <Clock size={18}/>
              {pendingEvaluation} attempt{pendingEvaluation === 1 ? "" : "s"} awaiting evaluation
            </div>
          )}
        </>
      )}

    </div>
  );
}
