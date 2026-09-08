import {
  PieChart,
  Pie,
  Cell,
  Tooltip,
  ResponsiveContainer,
} from "recharts";

export default function PerformanceBreakdown() {

  const data = [
    { name: "Passed", value: 18 },
    { name: "Failed", value: 6 },
  ];

  const COLORS = ["#22c55e", "#ef4444"];

  return (
    <div className="bg-white rounded-2xl border p-6 grid md:grid-cols-2 gap-6">

      {/* DONUT */}
      <div className="h-[250px]">
        <h3 className="font-semibold mb-3">
          Exam Outcome
        </h3>

        <ResponsiveContainer>
          <PieChart>
            <Pie
              data={data}
              innerRadius={70}
              outerRadius={100}
              paddingAngle={5}
              dataKey="value"
            >
              {data.map((entry, index) => (
                <Cell key={index} fill={COLORS[index]} />
              ))}
            </Pie>
            <Tooltip />
          </PieChart>
        </ResponsiveContainer>
      </div>

      {/* SIDE STATS */}
      <div className="flex flex-col justify-center space-y-4">
        <div>
          <p className="text-gray-500 text-sm">Total Exams</p>
          <h3 className="text-2xl font-semibold">24</h3>
        </div>

        <div>
          <p className="text-gray-500 text-sm">Best Score</p>
          <h3 className="text-2xl font-semibold text-green-600">
            92%
          </h3>
        </div>

        <div>
          <p className="text-gray-500 text-sm">Average Score</p>
          <h3 className="text-2xl font-semibold">
            81%
          </h3>
        </div>
      </div>

    </div>
  );
}