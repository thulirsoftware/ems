import { useEffect, useState } from "react";
import { toast } from "sonner";
import ReportService from "../../../services/report.service";

import ReportFilterBar from "../components/ReportFilterBar";
import SummaryCards from "../components/SummaryCards";
import ScoreDistributionChart from "../components/ScoreDistributionChart";
import AssessmentPerformanceTable from "../components/AssessmentPerformanceTable";
import BatchPerformanceTable from "../components/BatchPerformanceTable";
import UsersReportTable from "../components/UsersReportTable";
import QuestionsReportTable from "../components/QuestionsReportTable";
import AttemptsReportTable from "../components/AttemptsReportTable";
import { PageLoader } from "../../../components/common/Spinner";
import ErrorState from "../../../components/common/ErrorState";

const TABS = [
  { key: "summary", label: "Summary" },
  { key: "assessments", label: "Assessments" },
  { key: "batches", label: "Batches" },
  { key: "users", label: "Candidates" },
  { key: "questions", label: "Questions" },
  { key: "attempts", label: "Attempts" },
];

const emptyFilters = {
  assessment_id: "",
  batch_id: "",
  assessment_type: "",
  status: "",
  passing_percentage: "",
  from: "",
  to: "",
};

function toSummaryCards(summary) {
  if (!summary) return [];

  return [
    { title: "Students", value: summary.totals.candidates, color: "indigo" },
    { title: "Assessments", value: summary.totals.assessments, color: "emerald" },
    { title: "Batches", value: summary.totals.batches, color: "sky" },
    { title: "Assignments", value: summary.totals.assignments, color: "violet" },
    { title: "Attempts", value: summary.attempts.total, color: "orange" },
    { title: "Completed", value: summary.attempts.submitted, color: "green" },
    { title: "Completion %", value: `${summary.participation.participation_rate}%`, color: "cyan" },
    { title: "Average Score", value: `${summary.performance.average_percentage}%`, color: "amber" },
    { title: "Highest Score", value: `${summary.performance.highest_percentage}%`, color: "rose" },
    { title: "Lowest Score", value: `${summary.performance.lowest_percentage}%`, color: "red" },
  ];
}

function toScoreDistributionData(distribution) {
  if (!distribution) return [];
  return Object.entries(distribution).map(([range, count]) => ({ range, count }));
}

export default function ReportsPage() {
  const [activeTab, setActiveTab] = useState("summary");
  const [filters, setFilters] = useState(emptyFilters);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);
  const [result, setResult] = useState(null);

  // Reset to page 1 whenever the tab or filters change.
  useEffect(() => {
    setPage(1);
  }, [activeTab, filters]);

  useEffect(() => {
    loadTab();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [activeTab, filters, page]);

  const cleanParams = (extra = {}) => {
    const params = { ...filters, ...extra };
    Object.keys(params).forEach((key) => {
      if (params[key] === "" || params[key] === null || params[key] === undefined) {
        delete params[key];
      }
    });
    return params;
  };

  const loadTab = async () => {
    setLoading(true);
    setError(false);

    try {
      const params = cleanParams({ page, page_size: 10 });
      let data;

      switch (activeTab) {
        case "summary":
          data = await ReportService.getSummary(cleanParams());
          break;
        case "assessments":
          data = await ReportService.getAssessmentsReport(params);
          break;
        case "batches":
          data = await ReportService.getBatchesReport(params);
          break;
        case "users":
          data = await ReportService.getUsersReport(params);
          break;
        case "questions":
          data = await ReportService.getQuestionsReport(params);
          break;
        case "attempts":
          data = await ReportService.getAttemptsReport(params);
          break;
        default:
          data = null;
      }

      setResult(data);
    } catch (err) {
      setError(true);
      toast.error(err?.response?.data?.message || "Failed to load report.");
      setResult(null);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="space-y-6 min-h-screen">
      <div>
        <h1 className="text-2xl font-semibold">Reports</h1>
        <p className="text-gray-500 text-sm mt-1">
          Assessment, batch, candidate and question performance
        </p>
      </div>

      <ReportFilterBar filters={filters} onChange={setFilters} />

      <div className="flex gap-2 border-b overflow-x-auto">
        {TABS.map((tab) => (
          <button
            key={tab.key}
            onClick={() => setActiveTab(tab.key)}
            className={`px-4 py-2 text-sm font-medium whitespace-nowrap border-b-2 transition ${
              activeTab === tab.key
                ? "border-purple-600 text-purple-600"
                : "border-transparent text-gray-500 hover:text-gray-700"
            }`}
          >
            {tab.label}
          </button>
        ))}
      </div>

      {loading ? (
        <PageLoader label="Loading report..." />
      ) : error ? (
        <ErrorState title="Couldn't load this report" onRetry={loadTab} />
      ) : (
        <div className="space-y-6">
          {activeTab === "summary" && result && (
            <>
              <SummaryCards data={toSummaryCards(result)} />
              <ScoreDistributionChart
                data={toScoreDistributionData(result.score_distribution)}
              />
            </>
          )}

          {activeTab === "assessments" && (
            <AssessmentPerformanceTable
              result={result}
              onPageChange={setPage}
            />
          )}

          {activeTab === "batches" && (
            <BatchPerformanceTable result={result} onPageChange={setPage} />
          )}

          {activeTab === "users" && (
            <UsersReportTable result={result} onPageChange={setPage} />
          )}

          {activeTab === "questions" && (
            <QuestionsReportTable result={result} onPageChange={setPage} />
          )}

          {activeTab === "attempts" && (
            <AttemptsReportTable result={result} onPageChange={setPage} />
          )}
        </div>
      )}
    </div>
  );
}
