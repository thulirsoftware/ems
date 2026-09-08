import {
  ResponsiveContainer,
  BarChart,
  Bar,
  CartesianGrid,
  XAxis,
  YAxis,
  Tooltip,
  Cell,
} from "recharts";

import { BarChart3 } from "lucide-react";

const colors = [
  "#EF4444",
  "#F97316",
  "#EAB308",
  "#22C55E",
  "#3B82F6",
];

export default function ScoreDistributionChart({ data = [] }) {
  return (
    <div className="rounded-2xl bg-white shadow-sm border border-gray-100 p-6">

      <div className="flex justify-between items-center mb-6">

        <div>

          <h2 className="text-xl font-semibold">
            Score Distribution
          </h2>

          <p className="text-gray-500 text-sm">
            Student performance by score range
          </p>

        </div>

        <div className="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center">
          <BarChart3 className="text-emerald-600" />
        </div>

      </div>

      <ResponsiveContainer width="100%" height={350}>

        <BarChart data={data}>

          <CartesianGrid
            strokeDasharray="3 3"
            vertical={false}
          />

          <XAxis dataKey="range" />

          <YAxis />

          <Tooltip />

          <Bar
            dataKey="count"
            radius={[8, 8, 0, 0]}
          >

            {data.map((entry, index) => (

              <Cell
                key={index}
                fill={colors[index % colors.length]}
              />

            ))}

          </Bar>

        </BarChart>

      </ResponsiveContainer>

      <div className="grid grid-cols-5 gap-2 mt-5">

        {data.map((item, index) => (

          <div
            key={index}
            className="text-center rounded-lg bg-gray-50 p-3"
          >

            <div
              className="w-4 h-4 rounded-full mx-auto mb-2"
              style={{
                background: colors[index % colors.length],
              }}
            />

            <p className="font-semibold">
              {item.count}
            </p>

            <p className="text-xs text-gray-500">
              {item.range}
            </p>

          </div>

        ))}

      </div>

    </div>
  );
}