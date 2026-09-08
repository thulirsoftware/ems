
import ReportSummary from "../components/ReportSummary";
import TopPerformers from "../components/TopPerformers";
import ExamDifficultyChart from "../components/ExamDifficultyChart";
import ResultInsights from "../components/ResultInsights";

export default function AnalyticsPage() {
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

            <ReportSummary />


            <div className="grid xl:grid-cols-2 gap-6">
                <TopPerformers />
                <ResultInsights />
            </div>

            <ExamDifficultyChart />

        </div>
    );
}