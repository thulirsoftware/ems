import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  Tooltip,
  ResponsiveContainer,
  CartesianGrid,
  Area,
  AreaChart,
  Legend,
} from "recharts";

function formatDate(dateStr) {
  if (!dateStr) return "";

  return new Date(dateStr).toLocaleDateString(undefined, {
    day: "2-digit",
    month: "short",
  });
}

const CustomTooltip = ({ active, payload }) => {
  if (active && payload && payload.length) {
    return (
      <div className="bg-white shadow-lg rounded-xl px-4 py-2 border">
        <p className="text-sm font-medium">
          {payload[0].payload.exam}
        </p>
        <p className="text-blue-600 font-semibold">
          Score: {payload[0].payload.score}%
        </p>
        <p className="text-gray-500 text-sm">
          Running avg: {payload[0].payload.average}%
        </p>
      </div>
    );
  }
  return null;
};

export default function ReportChart({ data = [], loading }) {

  const chartData = data.map((row) => ({
    exam: `${row.assessment_title} (${formatDate(row.attempt_date)})`,
    score: row.percentage,
    average: row.running_average,
  }));

  return (
    <div className="bg-white border rounded-xl p-6 h-[360px]">

      {/* HEADER */}
      <div className="mb-4">
        <h3 className="font-semibold text-lg">
          Performance Trend
        </h3>
        <p className="text-sm text-gray-500">
          Track your score improvement across exams
        </p>
      </div>

      {loading ? (
        <p className="text-gray-400 text-sm">Loading...</p>
      ) : chartData.length === 0 ? (
        <p className="text-gray-400 text-sm">No evaluated attempts yet</p>
      ) : (
        <ResponsiveContainer width="100%" height="85%">
          <AreaChart data={chartData}>

            <defs>
              <linearGradient id="colorScore" x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%" stopColor="#2563eb" stopOpacity={0.4}/>
                <stop offset="95%" stopColor="#2563eb" stopOpacity={0}/>
              </linearGradient>
            </defs>

            <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" />

            <XAxis
              dataKey="exam"
              tick={{ fontSize: 12 }}
              axisLine={false}
              tickLine={false}
            />

            <YAxis
              axisLine={false}
              tickLine={false}
              domain={[0, 100]}
            />

            <Tooltip content={<CustomTooltip />} />
            <Legend />

            <Area
              type="monotone"
              dataKey="score"
              stroke="none"
              fill="url(#colorScore)"
              legendType="none"
            />

            <Line
              type="monotone"
              dataKey="score"
              name="Score"
              stroke="#2563eb"
              strokeWidth={3}
              dot={{ r: 5 }}
              activeDot={{ r: 8 }}
              animationDuration={1200}
            />

            <Line
              type="monotone"
              dataKey="average"
              name="Running Average"
              stroke="#94a3b8"
              strokeDasharray="4 4"
              strokeWidth={2}
              dot={false}
            />

          </AreaChart>
        </ResponsiveContainer>
      )}

    </div>
  );
}
