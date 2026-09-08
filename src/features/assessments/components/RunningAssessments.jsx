import { useEffect, useState } from "react";
import AssessmentService from "../../../services/assesment.service";
import { useNavigate } from "react-router-dom";
export default function RunningAssessments() {
    const [data, setData] = useState([]);
    const [loading, setLoading] = useState(true);
    const navigate = useNavigate();

    useEffect(() => {
        fetchRunningAssessments();

        const interval = setInterval(() => {
            if (!document.hidden) {
                fetchRunningAssessments();
            }
        }, 10000);

        return () => clearInterval(interval);
    }, []);

    const fetchRunningAssessments = async () => {
        try {
            const res = await AssessmentService.RunningAssessmentList();
            console.log("running assesment", res);
            setData(res.data ?? res);

        } catch (err) {
            console.error("Running exam fetch failed", err);
            setData([]);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return (
            <p className="text-gray-500 italic">
                Checking running exams...
            </p>
        );
    }

    return (
        <section>
            <h2 className="text-lg font-semibold text-red-600 mb-4">
                Running Assessments
            </h2>

            {data.length === 0 ? (
                <p className="text-gray-500 italic">
                    No running exams right now
                </p>
            ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {data.map((exam) => (
                        <div
                            key={exam.id}
                            className="rounded-xl border border-red-500 p-5 bg-white shadow-sm
              hover:-translate-y-1 hover:shadow-lg transition"
                        >
                            <div className="flex justify-between items-start mb-3">
                                <h3 className="font-semibold text-gray-800 text-lg">
                                    {exam.title}
                                </h3>

                                <span
                                    className="text-xs px-3 py-1 rounded-full font-semibold
                  bg-red-100 text-red-600 animate-pulse"
                                >
                                    LIVE
                                </span>
                            </div>

                            <p className="text-sm text-gray-600 mb-2">
                                {exam.description}
                            </p>

                            <div className="text-sm text-gray-600 space-y-1">
                                <p>📅 Date: {exam.publish_date}</p>
                                <p>⏰ Time: {exam.start_time} - {exam.end_time}</p>
                                <p>
                                    ❌ Negative Marking: {exam.has_negative ? "Yes" : "No"}
                                </p>
                            </div>

                            <button
                                onClick={() => navigate(`/assesments/${exam.id}`)}
                                className="mt-4 w-full py-2 rounded-lg font-medium
    bg-red-600 text-white hover:bg-red-700 transition"
                            >
                                Start Exam
                            </button>
                        </div>
                    ))}
                </div>
            )}
        </section>
    );
}
