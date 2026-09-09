import { useEffect, useState } from "react";
import {
  Users,
  FileCheck,
  Activity,
  TrendingUp,
  AlertTriangle,
  Trophy,
} from "lucide-react";

import {
  ResponsiveContainer,
  BarChart,
  Bar,
  XAxis,
  YAxis,
  Tooltip,
  CartesianGrid,
} from "recharts";

import DashboardService from "../../../../services/dashboard.service";
import { toast } from "sonner";
import { PageLoader } from "../../../../components/common/Spinner";
import ErrorState from "../../../../components/common/ErrorState";

const EMPTY_DASHBOARD = {
  assessments: { total: 0, active: 0, library: 0, batch_wise: 0, flexible: 0, by_type: {} },
  batches: { total: 0, upcoming: 0, running: 0, finished: 0 },
  questions: 0,
  candidates: 0,
  assignments: 0,
  attempts: { total: 0, in_progress: 0, submitted: 0, pending_evaluation: 0, evaluated: 0, completion_rate: 0 },
  average_percentage: 0,
  score_distribution: { "0-39": 0, "40-59": 0, "60-79": 0, "80-100": 0 },
  top_performers: [],
  upcoming_batches: [],
  recent_attempts: [],
  pending_evaluations: [],
};

export default function Dashboard() {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);
  const [dashboard, setDashboard] = useState(EMPTY_DASHBOARD);

  useEffect(() => {
    loadDashboard();
  }, []);

  async function loadDashboard() {
    setLoading(true);
    setError(false);

    try {
      const data = await DashboardService.getDashboard();
      setDashboard({ ...EMPTY_DASHBOARD, ...data });
    } catch (err) {
      setError(true);
      toast.error(
        err?.response?.data?.message || "Failed to load dashboard."
      );
    } finally {
      setLoading(false);
    }
  }

  const stats = [
    {
      title: "Candidates",
      value: dashboard.candidates,
      icon: Users,
      color: "from-indigo-500 to-indigo-600",
    },
    {
      title: "Active Assessments",
      value: dashboard.assessments.active,
      icon: FileCheck,
      color: "from-emerald-500 to-emerald-600",
    },
    {
      title: "Submitted Attempts",
      value: dashboard.attempts.submitted,
      icon: Activity,
      color: "from-orange-500 to-orange-600",
    },
    {
      title: "Completion Rate",
      value: `${dashboard.attempts.completion_rate}%`,
      icon: TrendingUp,
      color: "from-rose-500 to-rose-600",
    },
  ];

  const scoreDistributionData = Object.entries(dashboard.score_distribution).map(
    ([range, count]) => ({ range, count })
  );

  const statusLabel = {
    in_progress: "In Progress",
    evaluated: "Evaluated",
    pending_evaluation: "Pending Evaluation",
  };

  if (loading) {
    return <PageLoader label="Loading dashboard..." />;
  }

  if (error) {
    return (
      <ErrorState
        title="Couldn't load the dashboard"
        description="Something went wrong while fetching the latest overview."
        onRetry={loadDashboard}
      />
    );
  }

  return (
    <div className="">

      {/* Header */}
      <div className="mb-8">
        <h1 className="text-3xl font-bold">Dashboard</h1>
        <p className="text-gray-500">Assessment Management Overview</p>
      </div>

      {/* KPI */}
      <div className="grid xl:grid-cols-4 md:grid-cols-2 gap-6">
        {stats.map((item) => (
          <div
            key={item.title}
            className="rounded-2xl bg-white shadow-sm p-6 relative overflow-hidden"
          >
            <div
              className={`absolute right-0 top-0 w-32 h-32 rounded-full blur-3xl opacity-20 bg-gradient-to-br ${item.color}`}
            />

            <div className="flex justify-between">
              <div>
                <p className="text-gray-500 text-sm">{item.title}</p>
                <h2 className="mt-3 text-4xl font-bold">{item.value}</h2>
              </div>

              <div
                className={`h-14 w-14 rounded-xl bg-gradient-to-br ${item.color} text-white flex items-center justify-center`}
              >
                <item.icon size={26} />
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Analytics */}
      <div className="grid xl:grid-cols-3 gap-6 mt-8">
        <div className="bg-white rounded-2xl shadow-sm p-6 xl:col-span-2">
          <h2 className="text-xl font-semibold mb-5">
            Score Distribution ({dashboard.average_percentage}% average)
          </h2>

          <ResponsiveContainer width="100%" height={350}>
            <BarChart data={scoreDistributionData}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="range" />
              <YAxis allowDecimals={false} />
              <Tooltip />
              <Bar dataKey="count" fill="#6366f1" radius={[6, 6, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </div>

        {/* Recent Attempts */}
        <div className="bg-white rounded-2xl shadow-sm p-6">
          <h2 className="font-semibold text-xl mb-5">Recent Attempts</h2>

          <div className="space-y-5 max-h-[350px] overflow-y-auto">
            {dashboard.recent_attempts.length === 0 && (
              <p className="text-sm text-gray-400">No attempts yet</p>
            )}

            {dashboard.recent_attempts.map((item) => (
              <div
                key={item.attempt_id}
                className="border-l-4 border-indigo-500 pl-4"
              >
                <p className="font-semibold">{item.user_name || "-"}</p>
                <p className="text-sm text-gray-500">
                  {item.assessment_title || "-"}
                </p>
                <p className="text-indigo-600 font-semibold text-sm">
                  {statusLabel[item.status] || item.status}
                  {item.percentage !== null && item.percentage !== undefined
                    ? ` — ${item.percentage}%`
                    : ""}
                </p>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Overview cards */}
      <div className="mt-8">
        <h2 className="text-2xl font-bold mb-5">Overview</h2>

        <div className="grid lg:grid-cols-3 gap-6">
          <div className="rounded-2xl bg-white shadow-sm p-6">
            <h3 className="font-semibold text-lg">Assessments</h3>

            <div className="grid grid-cols-3 mt-5 gap-4 text-center">
              <div>
                <h2 className="font-bold text-2xl text-indigo-600">
                  {dashboard.assessments.total}
                </h2>
                <p className="text-xs text-gray-500">Total</p>
              </div>
              <div>
                <h2 className="font-bold text-2xl text-emerald-600">
                  {dashboard.assessments.active}
                </h2>
                <p className="text-xs text-gray-500">Active</p>
              </div>
              <div>
                <h2 className="font-bold text-2xl text-orange-600">
                  {dashboard.assessments.library}
                </h2>
                <p className="text-xs text-gray-500">Library</p>
              </div>
            </div>
          </div>

          <div className="rounded-2xl bg-white shadow-sm p-6">
            <h3 className="font-semibold text-lg">Batches</h3>

            <div className="grid grid-cols-3 mt-5 gap-4 text-center">
              <div>
                <h2 className="font-bold text-2xl text-indigo-600">
                  {dashboard.batches.upcoming}
                </h2>
                <p className="text-xs text-gray-500">Upcoming</p>
              </div>
              <div>
                <h2 className="font-bold text-2xl text-emerald-600">
                  {dashboard.batches.running}
                </h2>
                <p className="text-xs text-gray-500">Running</p>
              </div>
              <div>
                <h2 className="font-bold text-2xl text-orange-600">
                  {dashboard.batches.finished}
                </h2>
                <p className="text-xs text-gray-500">Finished</p>
              </div>
            </div>
          </div>

          <div className="rounded-2xl bg-white shadow-sm p-6">
            <h3 className="font-semibold text-lg">Attempts</h3>

            <div className="grid grid-cols-3 mt-5 gap-4 text-center">
              <div>
                <h2 className="font-bold text-2xl text-indigo-600">
                  {dashboard.attempts.in_progress}
                </h2>
                <p className="text-xs text-gray-500">In Progress</p>
              </div>
              <div>
                <h2 className="font-bold text-2xl text-emerald-600">
                  {dashboard.attempts.evaluated}
                </h2>
                <p className="text-xs text-gray-500">Evaluated</p>
              </div>
              <div>
                <h2 className="font-bold text-2xl text-orange-600">
                  {dashboard.attempts.pending_evaluation}
                </h2>
                <p className="text-xs text-gray-500">Pending</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Top performers + Pending evaluations */}
      <div className="grid xl:grid-cols-2 gap-6 mt-8">

        <div className="bg-white rounded-2xl shadow-sm p-6">
          <div className="flex items-center gap-2 mb-6">
            <Trophy className="text-yellow-500" />
            <h2 className="font-semibold text-xl">Top Performers</h2>
          </div>

          <table className="w-full">
            <thead>
              <tr className="text-left border-b">
                <th className="pb-3">Candidate</th>
                <th>Attempts</th>
                <th>Average %</th>
              </tr>
            </thead>
            <tbody>
              {dashboard.top_performers.length === 0 && (
                <tr>
                  <td colSpan={3} className="py-4 text-gray-400 text-sm">
                    No evaluated attempts yet
                  </td>
                </tr>
              )}

              {dashboard.top_performers.map((student) => (
                <tr key={student.user_id} className="border-b hover:bg-gray-50">
                  <td className="py-4">{student.user_name}</td>
                  <td>{student.attempts}</td>
                  <td>{student.average_percentage}%</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        <div className="bg-white rounded-2xl shadow-sm p-6">
          <div className="flex items-center gap-2 mb-6">
            <AlertTriangle className="text-red-500" />
            <h2 className="font-semibold text-xl">Pending Evaluations</h2>
          </div>

          <table className="w-full">
            <thead>
              <tr className="text-left border-b">
                <th className="pb-3">Candidate</th>
                <th>Assessment</th>
                <th>Submitted</th>
              </tr>
            </thead>
            <tbody>
              {dashboard.pending_evaluations.length === 0 && (
                <tr>
                  <td colSpan={3} className="py-4 text-gray-400 text-sm">
                    Nothing awaiting evaluation
                  </td>
                </tr>
              )}

              {dashboard.pending_evaluations.map((item) => (
                <tr key={item.attempt_id} className="border-b hover:bg-gray-50">
                  <td className="py-4">{item.user_name}</td>
                  <td>{item.assessment_title}</td>
                  <td>
                    {item.submitted_at
                      ? new Date(item.submitted_at).toLocaleString()
                      : "-"}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
