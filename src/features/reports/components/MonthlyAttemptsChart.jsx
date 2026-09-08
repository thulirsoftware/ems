import {
  ResponsiveContainer,
  AreaChart,
  Area,
  CartesianGrid,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts";
import { TrendingUp } from "lucide-react";

const months = [
  "",
  "Jan",
  "Feb",
  "Mar",
  "Apr",
  "May",
  "Jun",
  "Jul",
  "Aug",
  "Sep",
  "Oct",
  "Nov",
  "Dec",
];

export default function MonthlyAttemptsChart({ data = [] }) {
  const chartData = data.map((item) => ({
    month: months[item.month],
    attempts: Number(item.attempts),
    average: Number(item.average_score),
  }));

  return (
    <div className="rounded-2xl bg-white shadow-sm border border-gray-100 p-6">

      <div className="flex items-center justify-between mb-6">

        <div>
          <h2 className="text-xl font-semibold">
            Monthly Attempts
          </h2>

          <p className="text-gray-500 text-sm">
            Assessment attempts throughout the year
          </p>
        </div>

        <div className="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center">
          <TrendingUp className="text-indigo-600" />
        </div>

      </div>

      <ResponsiveContainer width="100%" height={350}>

        <AreaChart data={chartData}>

          <defs>

            <linearGradient
              id="attemptGradient"
              x1="0"
              y1="0"
              x2="0"
              y2="1"
            >

              <stop
                offset="0%"
                stopColor="#6366F1"
                stopOpacity={0.8}
              />

              <stop
                offset="100%"
                stopColor="#6366F1"
                stopOpacity={0}
              />

            </linearGradient>

          </defs>

          <CartesianGrid
            strokeDasharray="3 3"
            vertical={false}
          />

          <XAxis dataKey="month" />

          <YAxis />

          <Tooltip />

          <Area
            dataKey="attempts"
            stroke="#6366F1"
            fill="url(#attemptGradient)"
            strokeWidth={3}
          />

        </AreaChart>

      </ResponsiveContainer>

    </div>
  );
}