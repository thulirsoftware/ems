import { useCallback, useEffect, useState } from "react";
import StatCard from "../components/StatCard";
import ScoreChart from "../components/ScoreChart";
import UpcomingExams from "../components/UpcomingExams";
import { useAuthStore } from "../../../../store/authStore";
import RecentResultsTable from "../components/RecentResultsTable";
import PerformanceSummary from "../components/PerformanceSummary";
import DashboardService from "../../../../services/dashboard.service";

import {
  BookOpen,
  TrendingUp,
  Clock,
  Trophy,
  AlertCircle,
  RefreshCw,
} from "lucide-react";

export default function Dashboard() {
  const { admin } = useAuthStore();
  const userName = admin?.name || "Student";

  const [dashboard, setDashboard] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);

  const loadDashboard = useCallback(() => {
    let cancelled = false;
    setLoading(true);
    setError(false);

    DashboardService.getDashboard()
      .then((data) => {
        if (!cancelled) setDashboard(data);
      })
      .catch((err) => {
        console.error("Failed to load dashboard", err);
        if (!cancelled) setError(true);
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => loadDashboard(), [loadDashboard]);

  const results = dashboard?.results || {};
  const assignments = dashboard?.assignments || {};

  const stats = [
    {
      title: "Exams Attempted",
      value: results.submitted ?? 0,
      icon: BookOpen,
      color: "bg-blue-500",
    },
    {
      title: "Average Score",
      value: `${results.average_percentage ?? 0}%`,
      icon: TrendingUp,
      color: "bg-green-500",
    },
    {
      title: "Upcoming Exams",
      value: assignments.upcoming ?? 0,
      icon: Clock,
      color: "bg-orange-500",
    },
    {
      title: "Best Score",
      value: `${results.best_percentage ?? 0}%`,
      icon: Trophy,
      color: "bg-red-500",
    },
  ];

  return (
    <div className="min-h-screen">

      {/* HEADER */}
      <div className="mb-6">
        <h1 className="text-2xl font-bold">
          Welcome back, <span className="text-primary">{userName}</span>
        </h1>
        <p className="text-gray-500">
          Here is your exam performance overview
        </p>
      </div>

      {/* ERROR BANNER */}
      {error && (
        <div className="mb-6 flex items-center justify-between gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-red-700">
          <div className="flex items-center gap-3">
            <AlertCircle size={20} className="shrink-0" />
            <p className="text-sm">
              Couldn't load your dashboard data. Some information below may be missing.
            </p>
          </div>
          <button
            onClick={loadDashboard}
            className="flex items-center gap-1 text-sm font-medium hover:underline shrink-0"
          >
            <RefreshCw size={14} />
            Retry
          </button>
        </div>
      )}

      {/* STATS */}
      <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        {stats.map((item, i) => (
          <StatCard key={i} data={item} />
        ))}
      </div>

      {/* CHARTS */}
      <div className="grid grid-cols-1 xl:grid-cols-3 gap-6 mt-8">
        <div className="xl:col-span-2">
          <ScoreChart data={dashboard?.performance} loading={loading} />
        </div>

        <UpcomingExams exams={dashboard?.upcoming} loading={loading} />
      </div>

      {/* ACTIONABLE EXAMS: available to take right now, or resume in progress */}
      <div className="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-8">
        <UpcomingExams
          exams={dashboard?.in_progress}
          loading={loading}
          title="Continue Exam"
          emptyMessage="No exams in progress"
          iconColor="text-amber-600"
          iconBg="bg-amber-100"
        />
        <UpcomingExams
          exams={dashboard?.available_now}
          loading={loading}
          title="Available Now"
          emptyMessage="No exams available right now"
          iconColor="text-emerald-600"
          iconBg="bg-emerald-100"
        />
      </div>

      <div className="mt-8">
        <PerformanceSummary stats={results} loading={loading} />
      </div>

      <div className="mt-8">
        <RecentResultsTable results={dashboard?.recent_results} loading={loading} />
      </div>
    </div>
  );
}
