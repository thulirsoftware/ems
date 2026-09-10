import { useEffect, useState } from "react";
import ReportHeader from "../components/ReportHeader";
import ReportFilters from "../components/ReportFilters";
import ReportStats from "../components/ReportStats";
import PerformanceHighlight from "../components/PerformanceHighlight";
import PerformanceBreakdown from "../components/PerformanceBreakdown";
import ReportChart from "../components/ReportChart";
import ReportTable from "../components/ReportTable";
import QuestionsReportTable from "../components/QuestionsReportTable";
import ReportService from "../../../services/report.service";

const emptyFilters = { status: "", from: "", to: "" };
const QUESTIONS_PAGE_SIZE = 10;

export default function ReportPage() {
  const [filters, setFilters] = useState(emptyFilters);
  const [summary, setSummary] = useState(null);
  const [progress, setProgress] = useState(null);
  const [assessments, setAssessments] = useState([]);
  const [loading, setLoading] = useState(true);

  const [questionsReport, setQuestionsReport] = useState(null);
  const [loadingMoreQuestions, setLoadingMoreQuestions] = useState(false);

  const fetchReports = async (appliedFilters) => {
    setLoading(true);

    const params = {};
    if (appliedFilters.status) params.status = appliedFilters.status;
    if (appliedFilters.from) params.from = appliedFilters.from;
    if (appliedFilters.to) params.to = appliedFilters.to;

    try {
      const [summaryRes, progressRes, assessmentsRes, questionsRes] = await Promise.all([
        ReportService.getSummary(params),
        ReportService.getProgressReport(params),
        ReportService.getAssessmentsReport(params),
        ReportService.getQuestionsReport({ ...params, page: 1, page_size: QUESTIONS_PAGE_SIZE }),
      ]);

      setSummary(summaryRes);
      setProgress(progressRes);
      setAssessments(assessmentsRes.data || []);
      setQuestionsReport(questionsRes);
    } catch (error) {
      console.error("Failed to load reports", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchReports(emptyFilters);
  }, []);

  const handleApply = (nextFilters) => {
    setFilters(nextFilters);
    fetchReports(nextFilters);
  };

  const loadMoreQuestions = async () => {
    if (loadingMoreQuestions || !questionsReport) return;
    if (questionsReport.current_page >= questionsReport.total_pages) return;

    setLoadingMoreQuestions(true);

    const params = {};
    if (filters.status) params.status = filters.status;
    if (filters.from) params.from = filters.from;
    if (filters.to) params.to = filters.to;

    try {
      const nextPage = questionsReport.current_page + 1;
      const res = await ReportService.getQuestionsReport({
        ...params,
        page: nextPage,
        page_size: QUESTIONS_PAGE_SIZE,
      });

      setQuestionsReport((prev) => ({
        ...res,
        data: [...prev.data, ...res.data],
      }));
    } catch (error) {
      console.error("Failed to load more questions", error);
    } finally {
      setLoadingMoreQuestions(false);
    }
  };

  return (
    <div className="min-h-screen space-y-6">

      <ReportHeader />

      <ReportFilters filters={filters} onApply={handleApply} />

      <ReportStats summary={summary} loading={loading} />

      <PerformanceHighlight
        summary={summary}
        trend={progress?.trend}
        loading={loading}
      />

      <div className="grid md:grid-cols-2 gap-6">
        <PerformanceBreakdown summary={summary} loading={loading} />
        <ReportChart data={progress?.data} loading={loading} />
      </div>

      <ReportTable rows={assessments} loading={loading} />

      <QuestionsReportTable
        totals={questionsReport?.totals}
        rows={questionsReport?.data || []}
        loading={loading}
        hasMore={!!questionsReport && questionsReport.current_page < questionsReport.total_pages}
        onLoadMore={loadMoreQuestions}
        loadingMore={loadingMoreQuestions}
      />

    </div>
  );
}
