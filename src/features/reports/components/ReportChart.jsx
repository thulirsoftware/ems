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
} from "recharts";

export default function ReportChart() {

  const data = [
    { exam: "React", score: 75 },
    { exam: "Python", score: 82 },
    { exam: "SQL", score: 88 },
    { exam: "Aptitude", score: 70 },
    { exam: "System Design", score: 84 },
  ];

  // Custom Tooltip
  const CustomTooltip = ({ active, payload }) => {
    if (active && payload && payload.length) {
      return (
        <div className="bg-white shadow-lg rounded-xl px-4 py-2 border">
          <p className="text-sm font-medium">
            {payload[0].payload.exam}
          </p>
          <p className="text-blue-600 font-semibold">
            Score: {payload[0].value}%
          </p>
        </div>
      );
    }
    return null;
  };

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

      <ResponsiveContainer width="100%" height="85%">
        <AreaChart data={data}>

          {/* Gradient Fill */}
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
            domain={[60, 100]}
          />

          <Tooltip content={<CustomTooltip />} />

          {/* Area Glow */}
          <Area
            type="monotone"
            dataKey="score"
            stroke="none"
            fill="url(#colorScore)"
          />

          {/* Main Line */}
          <Line
            type="monotone"
            dataKey="score"
            stroke="#2563eb"
            strokeWidth={3}
            dot={{ r: 5 }}
            activeDot={{ r: 8 }}
            animationDuration={1200}
          />

        </AreaChart>
      </ResponsiveContainer>

    </div>
  );
}