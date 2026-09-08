import StatCard from "../components/StatCard";
import ScoreChart from "../components/ScoreChart";
import SubjectChart from "../components/SubjectChart";
import UpcomingExams from "../components/UpcomingExams";
import { useAuthStore } from "../../../../store/authStore";
import RecentResultsTable from "../components/RecentResultsTable";
import PerformanceSummary from "../components/PerformanceSummary";

import {
  BookOpen,
  TrendingUp,
  Clock,
  Trophy,
} from "lucide-react";

export default function Dashboard() {
  const { admin } = useAuthStore();
  const userName = admin?.name || "Student";

  const stats = [
    {
      title: "Exams Attempted",
      value: "18",
      icon: BookOpen,
      color: "bg-blue-500",
    },
    {
      title: "Average Score",
      value: "78%",
      icon: TrendingUp,
      color: "bg-green-500",
    },
    {
      title: "Upcoming Exams",
      value: "3",
      icon: Clock,
      color: "bg-orange-500",
    },
    {
      title: "Pass Percentage",
      value: "85%",
      icon: Trophy,
      color: "bg-red-500",
    },
  ];

  return (
    <div className="min-h-screen">

      {/* HEADER */}
      <div className="mb-6">
        <h1 className="text-2xl font-bold">
          Welcome Back , <span className="text-primary">{userName}</span>
        </h1>
        <p className="text-gray-500">
          Here is your exam performance overview
        </p>
      </div>

      {/* STATS */}
      <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        {stats.map((item, i) => (
          <StatCard key={i} data={item} />
        ))}
      </div>

      {/* CHARTS */}
      <div className="grid grid-cols-1 xl:grid-cols-3 gap-6 mt-8">
        <div className="xl:col-span-2">
          <ScoreChart />
        </div>

        <UpcomingExams />
      </div>

      <div className="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-8">
        <SubjectChart />
        <PerformanceSummary />
      </div>

      <div className="mt-8">
        <RecentResultsTable />
      </div>
    </div>
  );
}