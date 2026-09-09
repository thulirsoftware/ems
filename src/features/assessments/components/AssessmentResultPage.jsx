import { useEffect, useState } from "react";
import { useParams, useNavigate } from "react-router-dom";
import AssessmentService from "../../../services/assesment.service";
import QuestionAccordion from "./QuestionAccordion";

export default function AssessmentResultPage() {
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
      <div className=" flex items-center justify-center text-gray-500">
        Loading Result...
      </div>
    );

  if (result?.status === "pending") {
    return (
      <section className="flex items-center justify-center h-full bg-gray-50">

        <div className="bg-white shadow-xl rounded-2xl p-10 text-center max-w-lg space-y-6">

          <div className="text-6xl">📝</div>

          <h2 className="text-2xl font-bold text-gray-800">
            Evaluation in Progress
          </h2>

          <p className="text-gray-600">
            {result.message}
          </p>

          <p className="text-sm text-gray-500">
            Your descriptive answers are being reviewed by the evaluator.
            Results will be published once evaluation is complete.
          </p>

          <button
            onClick={() => navigate("/assesments")}
            className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          >
            Back to Assessments
          </button>

        </div>

      </section>
    );
  }

  if (result?.status === "error" || !result) {
    return (
      <section className="flex items-center justify-center h-full bg-gray-50">
        <div className="bg-white shadow-xl rounded-2xl p-10 text-center max-w-lg space-y-6">
          <div className="text-6xl">⚠️</div>
          <h2 className="text-2xl font-bold text-gray-800">Result unavailable</h2>
          <p className="text-gray-600">{result?.message || "Unable to load your result right now."}</p>
          <button
            onClick={() => navigate("/assesments")}
            className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          >
            Back to Assessments
          </button>
        </div>
      </section>
    );
  }

  const percentage = Number(result.percentage || 0);

  return (
    <section className="flex flex-col bg-gray-50 overflow-hidden">

      {/* ===== TOP RESULT HEADER ===== */}
      <div className="bg-white shadow-sm border-b pb-5">
        <div className="flex flex-wrap justify-between items-center gap-6">

          {/* Score */}
          <div>
            <h1 className="text-2xl font-bold text-gray-800">
              Assessment Result
            </h1>
            <p className="text-gray-500 text-sm">
              Performance Summary
            </p>
          </div>

          {/* Score Card */}
          <div className="flex items-center gap-8">

            <div>
              <p className="text-sm text-gray-500">Score</p>
              <p className="text-3xl font-bold text-emerald-600">
                {result.score} / {result.total_marks}
              </p>
            </div>

            {/* Progress */}
            <div className="w-56">
              <p className="text-sm text-gray-500 mb-1">
                {percentage}% Performance
              </p>

              <div className="w-full h-3 bg-gray-200 rounded-full overflow-hidden">
                <div
                  className="h-full bg-gradient-to-r from-green-500 to-emerald-600 transition-all duration-700"
                  style={{ width: `${percentage}%` }}
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* ===== MAIN CONTENT ===== */}
      <div className="flex flex-1 overflow-hidden">


        <div className="w-72 bg-white border-r p-6 space-y-5">
          <h3 className="font-semibold text-gray-700">
            Statistics
          </h3>

          <StatBox
            label="Correct Answers"
            value={result.correct}
            color="green"
          />

          <StatBox
            label="Wrong Answers"
            value={result.wrong}
            color="red"
          />

          <StatBox
            label="Unanswered"
            value={result.unanswered}
            color="gray"
          />

          <button
            onClick={() => navigate("/assesments")}
            className="w-full mt-6 py-3 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition"
          >
            Back to Assessments
          </button>
        </div>

        {/* RIGHT QUESTION REVIEW (SCROLLABLE ONLY HERE) */}
        <div className="flex-1 overflow-y-auto  p-8 space-y-6">

          <h2 className="text-xl font-semibold text-gray-800">
            Question Review
          </h2>

          {result.questions.map((q, index) => (
            <QuestionAccordion
              key={q.id}
              q={q}
              index={index}
            />
          ))}
        </div>
      </div>
    </section>
  );
}

/* ===== STAT CARD ===== */
function StatBox({ label, value, color }) {
  const styles = {
    green:
      "bg-green-50 text-green-700 border-green-200",
    red: "bg-red-50 text-red-700 border-red-200",
    gray:
      "bg-gray-50 text-gray-700 border-gray-200",
  };

  return (
    <div
      className={`border rounded-xl p-4 text-center ${styles[color]}`}
    >
      <p className="text-sm">{label}</p>
      <p className="text-2xl font-bold">{value}</p>
    </div>
  );
}