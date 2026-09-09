import { useEffect, useState } from "react";
import { toast } from "sonner";
import ReportService from "../../../services/report.service";
import { PageLoader } from "../../../components/common/Spinner";
import ErrorState from "../../../components/common/ErrorState";

import ReportSummary from "../components/ReportSummary";
import TopPerformers from "../components/TopPerformers";
import ExamDifficultyChart from "../components/ExamDifficultyChart";
import ResultInsights from "../components/ResultInsights";

export default function AnalyticsPage() {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(false);
    const [summary, setSummary] = useState(null);
    const [topUsers, setTopUsers] = useState([]);
    const [assessments, setAssessments] = useState([]);

    useEffect(() => {
        load();
    }, []);

    const load = async () => {
        setLoading(true);
        setError(false);

        try {
            const [summaryData, usersData, assessmentsData] = await Promise.all([
                ReportService.getSummary(),
                ReportService.getUsersReport({ page_size: 5 }),
                ReportService.getAssessmentsReport({ page_size: 200 }),
            ]);

            setSummary(summaryData);
            setTopUsers(usersData?.data || []);
            setAssessments(assessmentsData?.data || []);
        } catch (err) {
            setError(true);
            toast.error(err?.response?.data?.message || "Failed to load analytics.");
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return <PageLoader label="Loading analytics..." />;
    }

    if (error) {
        return (
            <ErrorState
                title="Couldn't load analytics"
                description="Something went wrong while fetching student performance data."
                onRetry={load}
            />
        );
    }

    return (
        <div className=" min-h-screen space-y-6">
            <div className="flex gap-4 justify-between">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Analytics
                    </h1>
                    <p className="text-gray-500 text-sm">
                        Analyze student performance and exam outcomes
                    </p>

                </div>
            </div>

            <ReportSummary summary={summary} />


            <div className="grid xl:grid-cols-2 gap-6">
                <TopPerformers students={topUsers} />
                <ResultInsights assessments={assessments} />
            </div>

            <ExamDifficultyChart assessments={assessments} />

        </div>
    );
}
