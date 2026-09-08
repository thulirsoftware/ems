import ReportHeader from "../components/ReportHeader";
import PerformanceHighlight from "../components/PerformanceHighlight";
import ReportTrend from "../components/ReportTrend";
import PerformanceBreakdown from "../components/PerformanceBreakdown";
import ReportTable from "../components/ReportTable";
import ReportChart from "../components/ReportChart";

export default function ReportPage() {
  return (
    <div className=" min-h-screen space-y-6">

      <ReportHeader />

      <ReportTable />

    </div>
  );
}