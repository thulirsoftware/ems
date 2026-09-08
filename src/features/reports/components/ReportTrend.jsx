import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  Tooltip,
  CartesianGrid,
  ResponsiveContainer,
} from "recharts";

export default function ReportTrend() {

  const data = [
    { exam: "React", score: 70 },
    { exam: "Python", score: 75 },
    { exam: "SQL", score: 88 },
    { exam: "Aptitude", score: 72 },
    { exam: "System Design", score: 84 },
  ];

  return (
    <div className="bg-white border rounded-xl p-6 h-[360px]">

      <h3 className="font-semibold text-gray-700 mb-4">
        Score Trend Analysis
      </h3>

      <ResponsiveContainer width="100%" height="100%">
        <LineChart data={data}>
          <CartesianGrid stroke="#eee" />
          <XAxis dataKey="exam"/>
          <YAxis />
          <Tooltip />
          <Line
            type="monotone"
            dataKey="score"
            stroke="#111827"
            strokeWidth={2}
          />
        </LineChart>
      </ResponsiveContainer>

    </div>
  );
}