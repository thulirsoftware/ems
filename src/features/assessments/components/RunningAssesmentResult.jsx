import { useEffect, useState } from "react";
import { useParams, useNavigate } from "react-router-dom";
import AssessmentService from "../../../services/assesment.service";

export default function RunningAssessmentResultPage() {
  const { examid } = useParams();
  const navigate = useNavigate();

  const [result, setResult] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (examid) loadResult();
  }, [examid]);

  const loadResult = async () => {
    try {

      const res = await AssessmentService.GetAssessmentResult(examid);
      setResult(res.data ?? res);

    } catch (err) {

      if (err.response?.status === 403) {

        // descriptive evaluation pending
        setResult({
          status: "pending",
          message: err.response.data.message
        });

      } else {

        console.error(err);
        setResult({
          status: "error",
          message: err.response?.data?.message || "Unable to load your result right now."
        });

      }

    } finally {

      setLoading(false);

    }
  };
  if (loading)
    return (
      <div className="h-screen flex items-center justify-center text-gray-500 text-lg">
        Loading Result...
      </div>
    );

  if (result?.status === "pending") {
    return (
      <section className="h-screen flex items-center justify-center bg-gradient-to-br from-slate-50 to-blue-50">

        <div className="bg-white rounded-3xl shadow-xl p-10 max-w-lg text-center space-y-6">

          <div className="text-6xl">📝</div>

          <h1 className="text-2xl font-bold text-gray-800">
            Evaluation in Progress
          </h1>

          <p className="text-gray-600">
            {result.message}
          </p>

          <p className="text-gray-500 text-sm">
            Your descriptive answers are being reviewed by the evaluator.
          </p>

          <button
            onClick={() => navigate("/")}
            className="mt-4 px-6 py-3 rounded-lg bg-blue-600 text-white hover:bg-blue-700"
          >
            Go to Dashboard
          </button>

        </div>

      </section>
    );
  }

  if (result?.status === "error" || !result) {
    return (
      <section className="h-screen flex items-center justify-center bg-gradient-to-br from-slate-50 to-blue-50">
        <div className="bg-white rounded-3xl shadow-xl p-10 max-w-lg text-center space-y-6">
          <div className="text-6xl">⚠️</div>
          <h1 className="text-2xl font-bold text-gray-800">Result unavailable</h1>
          <p className="text-gray-600">{result?.message || "Unable to load your result right now."}</p>
          <button
            onClick={() => navigate("/")}
            className="mt-4 px-6 py-3 rounded-lg bg-blue-600 text-white hover:bg-blue-700"
          >
            Go to Dashboard
          </button>
        </div>
      </section>
    );
  }

  const percentage = Number(result.percentage || 0);

  return (
    <section className=" bg-gradient-to-br from-slate-50 to-blue-50 flex items-center justify-center">

      <div className="max-w-2xl w-full bg-white rounded-3xl shadow-xl overflow-hidden">

        {/* ===== HEADER ===== */}
        <div className="bg-gradient-to-r from-emerald-500 to-green-600 text-white text-center py-10 relative">

          {/* Success Icon */}
          <div className="mx-auto w-24 h-24 rounded-full bg-white/20 backdrop-blur flex items-center justify-center text-5xl animate-bounce">
            ✓
          </div>

          <h1 className="text-3xl font-bold mt-4">
            Assessment Completed
          </h1>

          <p className="text-white/90 mt-2">
            Your responses have been successfully submitted
          </p>
        </div>

        {/* ===== BODY ===== */}
        <div className="p-10 space-y-8 text-center">

          {/* Score Section */}
          <div className="flex flex-col items-center">

            <div className="relative w-40 h-40 rounded-full bg-gradient-to-br from-emerald-100 to-green-50 flex items-center justify-center shadow-inner">
              <div className="text-center">
                <p className="text-sm text-gray-500">Score</p>
                <p className="text-4xl font-extrabold text-emerald-600">
                  {result.score}
                </p>
                <p className="text-gray-400 text-sm">
                  / {result.total_marks}
                </p>
              </div>
            </div>

          </div>

          {/* Progress */}
          <div>
            <div className="flex justify-between text-sm text-gray-500 mb-2">
              <span>Performance</span>
              <span className="font-semibold text-emerald-600">
                {percentage}%
              </span>
            </div>

            <div className="w-full h-4 bg-gray-200 rounded-full overflow-hidden">
              <div
                className="h-full bg-gradient-to-r from-emerald-500 to-green-600 transition-all duration-1000 ease-out"
                style={{ width: `${percentage}%` }}
              />
            </div>
          </div>

          {/* Extra Info */}
          <div className="grid grid-cols-2 gap-6 text-center">
            <div className="bg-gray-50 rounded-xl p-5">
              <p className="text-sm text-gray-500">Total Marks</p>
              <p className="text-xl font-bold text-gray-800">
                {result.total_marks}
              </p>
            </div>

            <div className="bg-gray-50 rounded-xl p-5">
              <p className="text-sm text-gray-500">Percentage</p>
              <p className="text-xl font-bold text-emerald-600">
                {percentage}%
              </p>
            </div>
          </div>

          {/* Actions */}
          <div className="flex gap-4 justify-center pt-4">
            <button
              onClick={() => navigate("/")}
              className="px-8 py-3 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition shadow-md hover:shadow-lg"
            >
              Go to Dashboard
            </button>


          </div>

        </div>
      </div>
    </section>
  );
}