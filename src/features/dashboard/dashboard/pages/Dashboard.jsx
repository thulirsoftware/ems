import { useEffect, useState } from "react";
import {
  Users,
  FileCheck,
  Activity,
  TrendingUp,
  AlertTriangle,
} from "lucide-react";

import {
  ResponsiveContainer,
  AreaChart,
  Area,
 XAxis,
  YAxis,
  Tooltip,
  CartesianGrid,
} from "recharts";

import DashboardService from "../../../../services/dashboard.service";

const colors = {
  indigo: "from-indigo-500 to-indigo-600",
  emerald: "from-emerald-500 to-emerald-600",
  orange: "from-orange-500 to-orange-600",
  rose: "from-rose-500 to-rose-600",
};

const icons = {
  "Total Students": Users,
  "Active Exams": FileCheck,
  "Attempts Today": Activity,
  "Completion Rate": TrendingUp,
};

export default function Dashboard() {
  const [loading, setLoading] = useState(true);

  const [dashboard, setDashboard] = useState({
    stats: [],
    platformAnalytics: [],
    examInsights: [],
    studentRisk: [],
    recentActivities: [],
  });

  useEffect(() => {
    loadDashboard();
  }, []);

  async function loadDashboard() {
    try {
      const data = await DashboardService.getDashboard();
      console.log("dsa",data);
      setDashboard(data);
    } finally {
      setLoading(false);
    }
  }

  const monthNames = [
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

  const chartData = dashboard.platformAnalytics.map((i) => ({
    month: monthNames[i.month],
    Attempts: Number(i.total),
  }));

  if (loading) {
    return (
      <div className="p-8 text-center text-gray-500">
        Loading Dashboard...
      </div>
    );
  }

  return (
    <div className="">

      {/* Header */}

      <div className="mb-8">
        <h1 className="text-3xl font-bold">
          Dashboard
        </h1>

        <p className="text-gray-500">
          Assessment Management Overview
        </p>
      </div>

      {/* KPI */}

      <div className="grid xl:grid-cols-4 md:grid-cols-2 gap-6">

        {dashboard.stats.map((item) => {
          const Icon = icons[item.title] || Users;

          return (
            <div
              key={item.title}
              className="rounded-2xl bg-white shadow-sm p-6 relative overflow-hidden"
            >
              <div
                className={`absolute right-0 top-0 w-32 h-32 rounded-full blur-3xl opacity-20 bg-gradient-to-br ${colors[item.color]}`}
              />

              <div className="flex justify-between">

                <div>

                  <p className="text-gray-500 text-sm">
                    {item.title}
                  </p>

                  <h2 className="mt-3 text-4xl font-bold">
                    {item.value}
                  </h2>

                </div>

                <div
                  className={`h-14 w-14 rounded-xl bg-gradient-to-br ${colors[item.color]} text-white flex items-center justify-center`}
                >
                  <Icon size={26} />
                </div>

              </div>

            </div>
          );
        })}

      </div>

      {/* Analytics */}

      <div className="grid xl:grid-cols-3 gap-6 mt-8">

        <div className="bg-white rounded-2xl shadow-sm p-6 xl:col-span-2">

          <h2 className="text-xl font-semibold mb-5">
            Monthly Assessment Attempts
          </h2>

          <ResponsiveContainer width="100%" height={350}>
            <AreaChart data={chartData}>
              <defs>

                <linearGradient id="color" x1="0" y1="0" x2="0" y2="1">

                  <stop offset="0%" stopColor="#6366f1" stopOpacity={0.8} />

                  <stop offset="100%" stopColor="#6366f1" stopOpacity={0} />

                </linearGradient>

              </defs>

              <CartesianGrid strokeDasharray="3 3" />

              <XAxis dataKey="month" />

              <YAxis />

              <Tooltip />

              <Area
                dataKey="Attempts"
                stroke="#6366f1"
                fill="url(#color)"
                strokeWidth={3}
              />

            </AreaChart>
          </ResponsiveContainer>

        </div>

        {/* Recent Activities */}

        <div className="bg-white rounded-2xl shadow-sm p-6">

          <h2 className="font-semibold text-xl mb-5">
            Recent Activities
          </h2>

          <div className="space-y-5">

            {dashboard.recentActivities.map((item, index) => (

              <div
                key={index}
                className="border-l-4 border-indigo-500 pl-4"
              >

                <p className="font-semibold">
                  {item.student}
                </p>

                <p className="text-sm text-gray-500">
                  {item.exam}
                </p>

                <p className="text-indigo-600 font-semibold">
                  Score : {item.score}
                </p>

              </div>

            ))}

          </div>

        </div>

      </div>

      {/* Exam Cards */}

      <div className="mt-8">

        <h2 className="text-2xl font-bold mb-5">
          Exam Insights
        </h2>

        <div className="grid lg:grid-cols-3 gap-6">

          {dashboard.examInsights.map((exam) => (

            <div
              key={exam.id}
              className="rounded-2xl bg-white shadow-sm p-6 hover:shadow-lg transition"
            >

              <h3 className="font-semibold text-lg">
                {exam.title}
              </h3>

              <div className="grid grid-cols-3 mt-5 gap-4 text-center">

                <div>

                  <h2 className="font-bold text-2xl text-indigo-600">
                    {exam.questions_count}
                  </h2>

                  <p className="text-xs text-gray-500">
                    Questions
                  </p>

                </div>

                <div>

                  <h2 className="font-bold text-2xl text-emerald-600">
                    {exam.batches_count}
                  </h2>

                  <p className="text-xs text-gray-500">
                    Batches
                  </p>

                </div>

                <div>

                  <h2 className="font-bold text-2xl text-orange-600">
                    {exam.attempts_count}
                  </h2>

                  <p className="text-xs text-gray-500">
                    Attempts
                  </p>

                </div>

              </div>

            </div>

          ))}

        </div>

      </div>

      {/* Student Risk */}

      <div className="mt-8 bg-white rounded-2xl shadow-sm p-6">

        <div className="flex items-center gap-2 mb-6">

          <AlertTriangle className="text-red-500" />

          <h2 className="font-semibold text-xl">
            Students At Risk
          </h2>

        </div>

        <table className="w-full">

          <thead>

            <tr className="text-left border-b">

              <th className="pb-3">Student</th>

              <th>Attempts</th>

              <th>Average Score</th>

              <th>Status</th>

            </tr>

          </thead>

          <tbody>

            {dashboard.studentRisk.map((student) => (

              <tr
                key={student.id}
                className="border-b hover:bg-gray-50"
              >

                <td className="py-4">
                  {student.name}
                </td>

                <td>
                  {student.attempts}
                </td>

                <td>
                  {student.average_score
                    ? Number(student.average_score).toFixed(1)
                    : 0}
                  %
                </td>

                <td>

                  <span className="bg-red-100 text-red-600 px-3 py-1 rounded-full text-xs">

                    Needs Attention

                  </span>

                </td>

              </tr>

            ))}

          </tbody>

        </table>

      </div>

    </div>
  );
}