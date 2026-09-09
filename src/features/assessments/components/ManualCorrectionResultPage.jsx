import { useEffect, useState } from "react";
import { useParams, useSearchParams, useNavigate } from "react-router-dom";
import { CheckCircle } from "lucide-react";
import AssessmentService from "../../../services/assesment.service";
import { PageLoader } from "../../../components/common/Spinner";

export default function ManualCorrectionResultPage() {

  const { assessmentId, userId } = useParams();
  const [searchParams] = useSearchParams();
  const batchId = searchParams.get("batch_id");
  const navigate = useNavigate();

  const [result, setResult] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    let cancelled = false;

    (async () => {
      try {
        const data = await AssessmentService.getUserResult(
          assessmentId,
          userId,
          batchId
        );

        if (!cancelled) setResult(data);
      } catch (err) {
        if (!cancelled) {
          setError(
            err?.response?.data?.message || "Unable to load result."
          );
        }
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [assessmentId, userId, batchId]);

  if (loading) {
    return <PageLoader label="Loading result..." />;
  }

  if (error || !result) {
    return (
      <div className="p-10 text-center text-gray-500">
        {error || "No result available"}
      </div>
    );
  }

  return (
    <div className="flex items-center justify-center min-h-[80vh] p-6">

      <div className="bg-white shadow-xl rounded-2xl w-full max-w-3xl p-10">

        {/* SUCCESS ICON */}
        <div className="flex flex-col items-center text-center">

          <CheckCircle className="w-16 h-16 text-green-500 mb-4" />

          <h2 className="text-3xl font-bold text-gray-800">
            Evaluation Completed
          </h2>

          <p className="text-gray-500 mt-2">
            Manual correction has been successfully submitted.
          </p>

        </div>

        {/* SCORE SUMMARY */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-6 mt-10">

          <div className="bg-green-50 border border-green-200 rounded-xl p-4 text-center">
            <p className="text-sm text-gray-500">Score</p>
            <p className="text-2xl font-bold text-green-600">
              {result.score} / {result.total_marks}
            </p>
          </div>

          <div className="bg-blue-50 border border-blue-200 rounded-xl p-4 text-center">
            <p className="text-sm text-gray-500">Percentage</p>
            <p className="text-2xl font-bold text-blue-600">
              {result.percentage}%
            </p>
          </div>

          <div className="bg-green-50 border border-green-200 rounded-xl p-4 text-center">
            <p className="text-sm text-gray-500">Correct</p>
            <p className="text-2xl font-bold text-green-600">
              {result.correct}
            </p>
          </div>

          <div className="bg-red-50 border border-red-200 rounded-xl p-4 text-center">
            <p className="text-sm text-gray-500">Incorrect</p>
            <p className="text-2xl font-bold text-red-600">
              {result.wrong}
            </p>
          </div>

        </div>

        {/* TOTAL QUESTIONS */}
        <div className="mt-8 text-center text-gray-600">
          Total Questions: <span className="font-semibold">{result.questions?.length}</span>
        </div>

        {/* ACTION BUTTON */}
        <div className="flex justify-center mt-10">

          <button
            onClick={() => navigate("/admin/assesments")}
            className="px-6 py-3 rounded-lg bg-purple-600 hover:bg-purple-700 text-white font-medium transition"
          >
            Go To Assessments
          </button>

        </div>

      </div>

    </div>
  );
}
