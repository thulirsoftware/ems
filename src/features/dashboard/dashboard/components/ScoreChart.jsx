import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  Tooltip,
  CartesianGrid,
  ResponsiveContainer,
} from "recharts";

export default function ScoreChart({ data = [], loading }) {

  const chartData = data.map((row) => ({
    exam: row.assessment_title,
    score: row.percentage,
  }));

  return (
    <div className="bg-white rounded-2xl shadow-sm p-6 h-[350px]">

      <h3 className="font-semibold mb-4 text-lg">
        Score Progress
      </h3>

      {loading ? (
        <p className="text-gray-400 text-sm">Loading...</p>
      ) : chartData.length === 0 ? (
        <p className="text-gray-400 text-sm">No evaluated attempts yet</p>
      ) : (
        <ResponsiveContainer width="100%" height="85%">
          <LineChart data={chartData}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="exam" />
            <YAxis domain={[0, 100]} />
            <Tooltip />
            <Line
              type="monotone"
              dataKey="score"
              stroke="#ef4444"
              strokeWidth={3}
            />
          </LineChart>
        </ResponsiveContainer>
      )}

    </div>
  );
}
