import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  Tooltip,
  CartesianGrid,
  ResponsiveContainer,
} from "recharts";

export default function ScoreChart() {

  const data = [
    { exam: "Test 1", score: 65 },
    { exam: "Test 2", score: 72 },
    { exam: "Test 3", score: 78 },
    { exam: "Test 4", score: 82 },
    { exam: "Test 5", score: 76 },
  ];

  return (
    <div className="bg-white rounded-2xl shadow-sm p-6 h-[350px]">

      <h3 className="font-semibold mb-4 text-lg">
        Score Progress
      </h3>

      <ResponsiveContainer width="100%" height="100%">
        <LineChart data={data}>
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis dataKey="exam" />
          <YAxis />
          <Tooltip />
          <Line
            type="monotone"
            dataKey="score"
            stroke="#ef4444"
            strokeWidth={3}
          />
        </LineChart>
      </ResponsiveContainer>

    </div>
  );
}