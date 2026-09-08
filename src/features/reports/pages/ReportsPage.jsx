import useReports from "../hooks/useReports";

import SummaryCards from "../components/SummaryCards";
import MonthlyAttemptsChart from "../components/MonthlyAttemptsChart";
import ScoreDistributionChart from "../components/ScoreDistributionChart";
import AssessmentPerformanceTable from "../components/AssessmentPerformanceTable";
import BatchPerformanceTable from "../components/BatchPerformanceTable";


export default function ReportsPage() {

    const { reports, loading } = useReports();

    if (loading) {

        return (
            <div className="p-10 text-center">

                Loading Reports...

            </div>
        );

    }

    return (

        <div className="space-y-8  min-h-screen">

            <SummaryCards
                data={reports.summary}
            />

            <div className="grid lg:grid-cols-2 gap-6">

                <MonthlyAttemptsChart
                    data={reports.monthlyAttempts}
                />

                <ScoreDistributionChart
                    data={reports.scoreDistribution}
                />

            </div>

            <AssessmentPerformanceTable
                data={reports.assessmentPerformance}
            />

            <BatchPerformanceTable
                data={reports.batchPerformance}
            />

           

           

        </div>

    );

}