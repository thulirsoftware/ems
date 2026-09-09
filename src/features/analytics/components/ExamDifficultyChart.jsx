import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  Tooltip,
  ResponsiveContainer,
  Cell,
  CartesianGrid,
} from "recharts";
import { BarChart3 } from "lucide-react";

// Difficulty is derived from each assessment's average score across
// evaluated attempts (/admin/reports/assessments): the lower the average
// score, the harder the assessment turned out to be in practice.
export default function ExamDifficultyChart({ assessments = [] }) {
  const data = assessments
    .filter((a) => a.evaluated > 0)
    .map((a) => ({
      exam: a.title,
      difficulty: Math.round(100 - a.average_percentage),
    }));

  const getColor = (value) => {
    if (value < 40) return "#7af4a6"; // Easy
    if (value < 70) return "#7f79f7"; // Medium
    return "#ef7272"; // Hard
  };

  return (
    <div className="bg-white border rounded-2xl p-6">

      {/* HEADER */}
      <div className="flex items-center gap-2 mb-6">
        <BarChart3 className="text-indigo-600"/>
        <h3 className="font-semibold text-gray-800">
          Assessment Difficulty Analysis
        </h3>
      </div>

      {data.length === 0 ? (
        <p className="text-sm text-gray-400 py-10 text-center">
          No evaluated assessments yet
        </p>
      ) : (
        <div className="grid xl:grid-cols-4 gap-6">

          {/* CHART */}
          <div className="xl:col-span-3 h-[380px]">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={data} barSize={34}>

                <CartesianGrid strokeDasharray="4 4" stroke="#f1f5f9"/>

                <XAxis
                  dataKey="exam"
                  angle={-20}
                  textAnchor="end"
                  height={60}
                  tick={{ fontSize: 11 }}
                  axisLine={false}
                  tickLine={false}
                />

                <YAxis axisLine={false} tickLine={false} />

                <Tooltip />

                <Bar dataKey="difficulty" radius={[0]}>
                  {data.map((entry, index) => (
                    <Cell
                      key={index}
                      fill={getColor(entry.difficulty)}
                    />
                  ))}
                </Bar>

              </BarChart>
            </ResponsiveContainer>
          </div>

          {/* LEGEND PANEL */}
          <div className="space-y-5">

            <h4 className="font-medium text-gray-700">
              Difficulty Scale
            </h4>

            <div className="flex items-center gap-3">
              <span className="w-4 h-4 rounded bg-green-500"></span>
              <div>
                <p className="font-medium text-sm">Easy</p>
                <p className="text-xs text-gray-500">
                  Below 40% difficulty
                </p>
              </div>
            </div>

            <div className="flex items-center gap-3">
              <span className="w-4 h-4 rounded bg-blue-500"></span>
              <div>
                <p className="font-medium text-sm">Medium</p>
                <p className="text-xs text-gray-500">
                  40% – 70% difficulty
                </p>
              </div>
            </div>

            <div className="flex items-center gap-3">
              <span className="w-4 h-4 rounded bg-red-500"></span>
              <div>
                <p className="font-medium text-sm">Hard</p>
                <p className="text-xs text-gray-500">
                  Above 70% difficulty
                </p>
              </div>
            </div>

          </div>

        </div>
      )}

    </div>
  );
}
