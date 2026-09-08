import {
  LineChart, Line, XAxis, YAxis,
  Tooltip, ResponsiveContainer, CartesianGrid
} from "recharts";

export default function ScoreAnalytics() {

  const data = [
    { month: "Jan", score: 68 },
    { month: "Feb", score: 72 },
    { month: "Mar", score: 78 },
    { month: "Apr", score: 81 },
    { month: "May", score: 76 },
  ];

  return (
    <div className="bg-white border rounded-xl p-6 h-[360px]">

      <h3 className="font-semibold mb-4">
        Average Score Trend
      </h3>

      <ResponsiveContainer width="100%" height="100%">
        <LineChart data={data}>
          <CartesianGrid stroke="#eee"/>
          <XAxis dataKey="month"/>
          <YAxis/>
          <Tooltip/>
          <Line
            dataKey="score"
            stroke="#4f46e5"
            strokeWidth={3}
          />
        </LineChart>
      </ResponsiveContainer>

    </div>
  );
}