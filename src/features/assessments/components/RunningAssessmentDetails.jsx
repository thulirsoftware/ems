import { useEffect, useState } from "react";
import { useParams, useNavigate } from "react-router-dom";
import { toast } from "sonner";
import AssessmentService from "../../../services/assesment.service";

export default function RunningAssessmentDetails() {
  const { examid } = useParams();
  const navigate = useNavigate();

  const [exam, setExam] = useState(null);
  const [loading, setLoading] = useState(true);
  const [starting, setStarting] = useState(false);

  useEffect(() => {
    if (examid) loadData();
  }, [examid]);

  const loadData = async () => {
    try {
      const res = await AssessmentService.GetAssessmentById(examid);
      setExam(res.data ?? res);
    } catch (err) {
      console.error("Failed to load assessment data", err);
    } finally {
      setLoading(false);
    }
  };

  // ⏱ Duration
  const getDuration = () => {
    if (!exam.start_time || !exam.end_time) return "N/A";

    const start = new Date(`1970-01-01T${exam.start_time}`);
    const end = new Date(`1970-01-01T${exam.end_time}`);

    let diff = Math.max(0, (end - start) / 1000);
    const hrs = Math.floor(diff / 3600);
    diff %= 3600;
    const mins = Math.floor(diff / 60);

    return `${hrs} hr ${mins} min`;
  };

  // ✅ START EXAM
  const startExam = async () => {
    if (starting) return;
    setStarting(true);

    try {
      const res = await AssessmentService.StartAssessment(examid);

      const attemptId =
        res?.attempt?.id || res?.data?.attempt?.id;

      if (!attemptId) {
        throw new Error("Attempt ID not found");
      }

      // optional resume support
      localStorage.setItem("running_attempt", attemptId);

      // The questions screen re-fetches assessment details itself (it needs
      // to survive a page refresh, which route state does not), so no state
      // needs to be passed here.
      navigate(`/assesments/start/${examid}/run/${attemptId}`);
    } catch (err) {
      console.error("Failed to start assessment", err);
      toast.error(err.response?.data?.message || "Failed to start the assessment. Please try again.");
      setStarting(false);
    }
  };

  if (loading)
    return <p className="text-gray-500 italic">Loading assessment...</p>;

  if (!exam)
    return <p className="text-red-500">Assessment not found</p>;

  return (
    <section className="bg-white rounded-2xl shadow-lg p-4 space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-800">
          {exam.title}
        </h1>
        <p className="text-gray-600 mt-1">{exam.description}</p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50 border rounded-xl p-4 text-center text-sm">
        <div>
          <p className="text-gray-500">Duration</p>
          <p className="font-semibold">{getDuration()}</p>
        </div>

        <div>
          <p className="text-gray-500">Negative</p>
          <p className="font-semibold">
            {exam.has_negative ? `-${exam.negative_marks} / wrong` : "No"}
          </p>
        </div>

        <div>
          <p className="text-gray-500">Shuffle</p>
          <p className="font-semibold">
            {exam.shuffle ? "Yes" : "No"}
          </p>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-gray-700">
        <p><b>Date:</b> {exam.publish_date}</p>
        <p><b>Time:</b> {exam.start_time} – {exam.end_time}</p>
      </div>

      <button
        onClick={startExam}
        disabled={starting}
        className="w-full py-3 rounded-xl font-semibold text-white
        bg-red-600 hover:bg-red-700 transition disabled:opacity-60"
      >
        {starting ? "Starting..." : "Start Exam Now"}
      </button>
    </section>
  );
}