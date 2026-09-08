import {
  PieChart,
  Pie,
  Cell,
  ResponsiveContainer,
  Tooltip,
} from "recharts";
import { CheckCircle, XCircle } from "lucide-react";

export default function PassFailChart() {

  const data = [
    { name: "Passed", value: 320 },
    { name: "Failed", value: 80 },
  ];

  const COLORS = ["#22c55e", "#ef4444"];

  const total = data.reduce((a, b) => a + b.value, 0);
  const passPercent = Math.round((data[0].value / total) * 100);

  const CustomTooltip = ({ active, payload }) => {
    if (active && payload?.length) {
      return (
        <div className="bg-white border shadow-lg rounded-lg px-3 py-2">
          <p className="text-sm font-medium">
            {payload[0].name}
          </p>
          <p className="text-indigo-600">
            {payload[0].value} Students
          </p>
        </div>
      );
    }
    return null;
  };

  return (
    <div className="bg-white border rounded-xl p-6 h-[340px]">

      {/* HEADER */}
      <h3 className="font-semibold mb-4">
        Pass vs Fail Analytics
      </h3>

      <div className="flex items-center h-[260px]">

        {/* CHART */}
        <div className="w-1/2 h-full relative">
          <ResponsiveContainer>
            <PieChart>
              <Pie
                data={data}
                innerRadius={70}
                outerRadius={100}
                paddingAngle={4}
                dataKey="value"
              >
                {data.map((entry, index) => (
                  <Cell key={index} fill={COLORS[index]} />
                ))}
              </Pie>

              <Tooltip content={<CustomTooltip />} />
            </PieChart>
          </ResponsiveContainer>

          {/* CENTER TEXT */}
          <div className="
            absolute inset-0 flex flex-col
            items-center justify-center
          ">
            <p className="text-sm text-gray-400">
              Pass Rate
            </p>
            <h2 className="text-2xl font-bold">
              {passPercent}%
            </h2>
          </div>
        </div>

        {/* LEGEND */}
        <div className="w-1/2 space-y-4">

          <div className="flex items-center gap-3">
            <CheckCircle className="text-green-500"/>
            <div>
              <p className="text-sm text-gray-500">Passed</p>
              <p className="font-semibold">
                {data[0].value} Students
              </p>
            </div>
          </div>

          <div className="flex items-center gap-3">
            <XCircle className="text-red-500"/>
            <div>
              <p className="text-sm text-gray-500">Failed</p>
              <p className="font-semibold">
                {data[1].value} Students
              </p>
            </div>
          </div>

        </div>

      </div>
    </div>
  );
}