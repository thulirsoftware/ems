import {
  PieChart,
  Pie,
  Cell,
  Tooltip,
  ResponsiveContainer,
} from "recharts";

const COLORS = { Passed: "#22c55e", Failed: "#ef4444" };

export default function PerformanceBreakdown({ summary, loading }) {

  const totals = summary?.totals || {};
  const performance = summary?.performance || {};

  // Recharts' paddingAngle produces a degenerate (invisible) arc when one
  // slice is exactly 0 — e.g. a 100% pass rate — so zero-value slices are
  // dropped rather than passed through.
  const data = [
    { name: "Passed", value: performance.passed ?? 0 },
    { name: "Failed", value: performance.failed ?? 0 },
  ].filter((entry) => entry.value > 0);

  const hasData = !loading && data.length > 0;

  return (
    <div className="bg-white rounded-2xl border p-6 grid md:grid-cols-2 gap-6">

      {/* DONUT */}
      <div className="h-[250px]">
        <h3 className="font-semibold mb-3">
          Exam Outcome
        </h3>

        {hasData ? (
          <ResponsiveContainer>
            <PieChart>
              <Pie
                data={data}
                innerRadius={70}
                outerRadius={100}
                paddingAngle={5}
                dataKey="value"
              >
                {data.map((entry) => (
                  <Cell key={entry.name} fill={COLORS[entry.name]} />
                ))}
              </Pie>
              <Tooltip />
            </PieChart>
          </ResponsiveContainer>
        ) : (
          <p className="text-gray-400 text-sm">
            {loading ? "Loading..." : "No evaluated attempts yet"}
          </p>
        )}
      </div>

      {/* SIDE STATS */}
      <div className="flex flex-col justify-center space-y-4">
        <div>
          <p className="text-gray-500 text-sm">Total Exams</p>
          <h3 className="text-2xl font-semibold">{loading ? "-" : totals.assessments ?? 0}</h3>
        </div>

        <div>
          <p className="text-gray-500 text-sm">Best Score</p>
          <h3 className="text-2xl font-semibold text-green-600">
            {loading ? "-" : `${performance.highest_percentage ?? 0}%`}
          </h3>
        </div>

        <div>
          <p className="text-gray-500 text-sm">Average Score</p>
          <h3 className="text-2xl font-semibold">
            {loading ? "-" : `${performance.average_percentage ?? 0}%`}
          </h3>
        </div>
      </div>

    </div>
  );
}
