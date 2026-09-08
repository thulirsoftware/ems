import {
  AreaChart,
  Area,
  Line,
  XAxis,
  YAxis,
  Tooltip,
  ResponsiveContainer,
  CartesianGrid,
  ReferenceDot,
} from "recharts";
import { Activity } from "lucide-react";

export default function PlatformAnalytics() {

  const data = [
    { day: "Mon", users: 120 },
    { day: "Tue", users: 210 },
    { day: "Wed", users: 180 },
    { day: "Thu", users: 260 },
    { day: "Fri", users: 320 },
    { day: "Sat", users: 240 },
    { day: "Sun", users: 290 },
  ];

  // find highest point
  const peak = data.reduce((max, d) =>
    d.users > max.users ? d : max
  );

  // custom tooltip
  const CustomTooltip = ({ active, payload }) => {
    if (active && payload?.length) {
      return (
        <div className="bg-white border shadow-lg rounded-xl px-4 py-2">
          <p className="text-sm text-gray-500">
            {payload[0].payload.day}
          </p>
          <p className="font-semibold text-indigo-600">
            {payload[0].value} Active Students
          </p>
        </div>
      );
    }
    return null;
  };

  return (
    <div className="bg-white rounded-2xl border p-6 h-[400px]">

      {/* HEADER */}
      <div className="flex items-center justify-between mb-4">
        <div className="flex items-center gap-2">
          <Activity className="text-indigo-600" size={20}/>
          <h3 className="font-semibold text-gray-800">
            Platform Student Activity
          </h3>
        </div>

        <span className="text-sm text-gray-500">
          Last 7 Days
        </span>
      </div>

      <ResponsiveContainer width="100%" height="90%">
        <AreaChart data={data}>

          {/* Gradient */}
          <defs>
            <linearGradient id="activityFill" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stopColor="#6366f1" stopOpacity={0.35}/>
              <stop offset="100%" stopColor="#6366f1" stopOpacity={0}/>
            </linearGradient>
          </defs>

          <CartesianGrid
            strokeDasharray="4 4"
            stroke="#f1f5f9"
          />

          <XAxis
            dataKey="day"
            axisLine={false}
            tickLine={false}
            tick={{ fontSize: 12 }}
          />

          <YAxis
            axisLine={false}
            tickLine={false}
            tick={{ fontSize: 12 }}
          />

          <Tooltip content={<CustomTooltip />} />

          {/* Area background */}
          <Area
            type="monotone"
            dataKey="users"
            stroke="none"
            fill="url(#activityFill)"
          />

          {/* Main analytic line */}
          <Line
            type="monotone"
            dataKey="users"
            stroke="#4f46e5"
            strokeWidth={3}
            dot={{ r: 4 }}
            activeDot={{ r: 7 }}
          />

          {/* Peak highlight */}
          <ReferenceDot
            x={peak.day}
            y={peak.users}
            r={7}
            fill="#ef4444"
            stroke="white"
            strokeWidth={2}
          />

        </AreaChart>
      </ResponsiveContainer>

    </div>
  );
}