import { useEffect, useState } from "react";
import ReportHeader from "../components/ReportHeader";
import ReportFilters from "../components/ReportFilters";
import ReportStats from "../components/ReportStats";
import PerformanceHighlight from "../components/PerformanceHighlight";
import PerformanceBreakdown from "../components/PerformanceBreakdown";
import ReportChart from "../components/ReportChart";
import ReportTable from "../components/ReportTable";
import ReportService from "../../../services/report.service";

const emptyFilters = { status: "", from: "", to: "" };

export default function ReportPage() {
  const [filters, setFilters] = useState(emptyFilters);
  const [summary, setSummary] = useState(null);
  const [progress, setProgress] = useState(null);
  const [assessments, setAssessments] = useState([]);
  const [loading, setLoading] = useState(true);

  const fetchReports = async (appliedFilters) => {
    setLoading(true);

    const params = {};
    if (appliedFilters.status) params.status = appliedFilters.status;
    if (appliedFilters.from) params.from = appliedFilters.from;
    if (appliedFilters.to) params.to = appliedFilters.to;

    try {
      const [summaryRes, progressRes, assessmentsRes] = await Promise.all([
        ReportService.getSummary(params),
        ReportService.getProgressReport(params),
        ReportService.getAssessmentsReport(params),
      ]);

      setSummary(summaryRes);
      setProgress(progressRes);
      setAssessments(assessmentsRes.data || []);
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

    </div>
  );
}
