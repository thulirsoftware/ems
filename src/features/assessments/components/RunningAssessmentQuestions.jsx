import { useEffect, useRef, useState } from "react";
import { useParams, useNavigate } from "react-router-dom";
import { toast } from "sonner";
import AssessmentService from "../../../services/assesment.service";
import ConfirmDialog from "../../../components/common/ConfirmDialog";

export default function RunningAssessmentQuestions() {
    const { examid, attemptId } = useParams();
    const navigate = useNavigate();

    const [examMeta, setExamMeta] = useState(null);
    const [questions, setQuestions] = useState([]);
    const [current, setCurrent] = useState(0);
    const [answers, setAnswers] = useState({});
    // null = no countdown to show yet / no fixed duration for this exam
    const [timeLeft, setTimeLeft] = useState(null);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [confirmSubmitOpen, setConfirmSubmitOpen] = useState(false);

    const answerTimers = useRef({});

    /* --------------------------------------------------
       CALCULATE REMAINING TIME
       The backend is the authority on expiry (it re-checks the window on
       every /answer call regardless of what the client shows), so this is
       a best-effort display only:
       - non-flexible assessments: deadline = batch publish_date + end_time,
         which is available from GetAssessmentById on every load — safe
         across refreshes.
       - flexible assessments with a fixed duration: the backend anchors
         the deadline to the attempt's created_at, but that field is never
         returned to the client. We anchor a local copy the first time this
         attempt is opened (in localStorage, keyed by attempt id) so a page
         refresh doesn't reset the countdown to zero.
       - flexible assessments without a fixed duration: no countdown.
    -------------------------------------------------- */
    const calculateRemainingTime = (meta) => {
        if (!meta) return null;

        if (!meta.is_flexible) {
            if (!meta.publish_date || !meta.end_time) return null;
            const examEnd = new Date(`${meta.publish_date}T${meta.end_time}`);
            const diff = Math.floor((examEnd - new Date()) / 1000);
            return diff > 0 ? diff : 0;
        }

        if (meta.duration_minutes) {
            const key = `attempt_start_${attemptId}`;
            let startedAtMs = Number(localStorage.getItem(key)) || 0;
            if (!startedAtMs) {
                startedAtMs = Date.now();
                localStorage.setItem(key, String(startedAtMs));
            }
            const deadline = startedAtMs + meta.duration_minutes * 60 * 1000;
            const diff = Math.floor((deadline - Date.now()) / 1000);
            return diff > 0 ? diff : 0;
        }

        return null;
    };

    /* --------------------------------------------------
       LOAD ASSESSMENT META + QUESTIONS
    -------------------------------------------------- */
    useEffect(() => {
        if (!examid) return;
        let cancelled = false;

        (async () => {
            try {
                const [meta, data] = await Promise.all([
                    AssessmentService.GetAssessmentById(examid).catch(() => null),
                    AssessmentService.GetAssessmentQuestions(examid),
                ]);

                if (cancelled) return;
                setExamMeta(meta);
                setQuestions(Array.isArray(data) ? data : []);
                setTimeLeft(calculateRemainingTime(meta));
            } catch (err) {
                if (cancelled) return;
                const message =
                    err.response?.data?.message ||
                    "This assessment is not currently available.";
                toast.error(message);
                navigate("/assesments");
            } finally {
                if (!cancelled) setLoading(false);
            }
        })();

        return () => {
            cancelled = true;
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [examid]);

    /* --------------------------------------------------
       TIMER RUNNER
    -------------------------------------------------- */
    useEffect(() => {
        if (timeLeft === null) return;

        if (timeLeft <= 0) {
            submitExam(true);
            return;
        }

        const timer = setInterval(() => {
            setTimeLeft((t) => (t === null ? t : t - 1));
        }, 1000);

        return () => clearInterval(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [timeLeft]);

    useEffect(() => {
        const timers = answerTimers.current;
        return () => {
            Object.values(timers).forEach(clearTimeout);
        };
    }, []);

    /* --------------------------------------------------
       WARN BEFORE LEAVING — closing/refreshing mid-exam is easy to do by
       accident and re-entering loses your place in the question list.
    -------------------------------------------------- */
    useEffect(() => {
        const handler = (e) => {
            e.preventDefault();
            e.returnValue = "";
        };
        window.addEventListener("beforeunload", handler);
        return () => window.removeEventListener("beforeunload", handler);
    }, []);

    const reportSaveError = (err) => {
        const message = err.response?.data?.message;
        toast.error(
            message ||
                (err.response?.status === 403
                    ? "This assessment is no longer accepting answers."
                    : "Failed to save your answer.")
        );
    };

    /* --------------------------------------------------
       ANSWER SELECT (MCQ) — safe to call repeatedly, backend upserts
    -------------------------------------------------- */
    const handleOptionSelect = async (questionId, optionId) => {
        setAnswers((prev) => ({
            ...prev,
            [questionId]: optionId,
        }));
        try {
            await AssessmentService.SaveAnswer(examid, {
                question_id: questionId,
                choice_id: optionId,
            });
        } catch (err) {
            reportSaveError(err);
        }
    };

    /* --------------------------------------------------
       TEXT ANSWER — debounced autosave (previously fired on every keystroke)
    -------------------------------------------------- */
    const handleTextAnswer = (questionId, text) => {
        setAnswers((prev) => ({
            ...prev,
            [questionId]: text,
        }));

        clearTimeout(answerTimers.current[questionId]);
        answerTimers.current[questionId] = setTimeout(async () => {
            try {
                await AssessmentService.SaveAnswer(examid, {
                    question_id: questionId,
                    answer: text,
                });
            } catch (err) {
                reportSaveError(err);
            }
        }, 600);
    };

    /* --------------------------------------------------
       SUBMIT EXAM
       The backend scores from answers already saved via the calls above —
       submit itself takes no payload.
    -------------------------------------------------- */
    const submitExam = async (auto = false) => {
        if (submitting) return;

        if (!auto && !confirmSubmitOpen) {
            setConfirmSubmitOpen(true);
            return;
        }

        setSubmitting(true);
        try {
            await AssessmentService.SubmitAssessment(examid);
            localStorage.removeItem("running_attempt");
            localStorage.removeItem(`attempt_start_${attemptId}`);
            navigate(`/assesments/end/${examid}/result`);
        } catch (err) {
            if (err.response?.status === 400) {
                // Already submitted (e.g. auto-submit racing a manual click) —
                // the attempt is finalized either way, just go to the result.
                navigate(`/assesments/end/${examid}/result`);
                return;
            }
            toast.error(err.response?.data?.message || "Failed to submit assessment.");
            setSubmitting(false);
            setConfirmSubmitOpen(false);
        }
    };

    /* --------------------------------------------------
       UI STATES
    -------------------------------------------------- */
    if (loading) return <p>Loading questions...</p>;

    const q = questions[current];

    if (!q) {
        return (
            <p className="text-red-500 text-center">
                No questions available
            </p>
        );
    }

    /* --------------------------------------------------
       UI
    -------------------------------------------------- */
    return (
        <section className="bg-white rounded-xl shadow p-6 space-y-6">

            {/* HEADER */}
            <div className="flex justify-between items-center">
                <h2 className="font-semibold">
                    Question {current + 1} / {questions.length}
                </h2>

                {timeLeft !== null && (
                    <p className="text-red-600 font-bold">
                        ⏱ {Math.floor(timeLeft / 60)}:
                        {(timeLeft % 60).toString().padStart(2, "0")}
                    </p>
                )}
            </div>

            {/* QUESTION */}
            <div>
                <p className="font-medium text-gray-800 mb-4">
                    {q.question_text}
                </p>

                <div className="space-y-3">

                    {q.type === "mcq" ? (

                        q.choices.map((c) => (

                            <label
                                key={c.id}
                                className={`block border rounded-lg p-3 cursor-pointer
            ${answers[q.id] === c.id
                                        ? "bg-blue-100 border-blue-500"
                                        : ""
                                    }`}
                            >

                                <input
                                    type="radio"
                                    className="mr-2"
                                    checked={answers[q.id] === c.id}
                                    onChange={() =>
                                        handleOptionSelect(q.id, c.id)
                                    }
                                />

                                {c.option}

                            </label>

                        ))

                    ) : (

                        <textarea
                            className="w-full border rounded-lg p-3"
                            rows={6}
                            placeholder="Write your answer..."
                            value={answers[q.id] || ""}
                            onChange={(e) =>
                                handleTextAnswer(q.id, e.target.value)
                            }
                        />

                    )}

                </div>
            </div>

            {/* NAVIGATION */}
            <div className="flex justify-between">
                <button
                    disabled={current === 0}
                    onClick={() => setCurrent(current - 1)}
                    className="px-4 py-2 border rounded disabled:opacity-50"
                >
                    Prev
                </button>

                {current === questions.length - 1 ? (
                    <button
                        onClick={() => submitExam(false)}
                        disabled={submitting}
                        className="px-6 py-2 bg-green-600 text-white rounded disabled:opacity-50"
                    >
                        {submitting ? "Submitting..." : "Submit Exam"}
                    </button>
                ) : (
                    <button
                        onClick={() => setCurrent(current + 1)}
                        className="px-6 py-2 bg-blue-600 text-white rounded"
                    >
                        Next
                    </button>
                )}
            </div>

            <ConfirmDialog
                open={confirmSubmitOpen}
                title="Submit this assessment?"
                description="You won't be able to change your answers afterward."
                confirmLabel="Submit"
                confirmClass="bg-green-600 hover:bg-green-700"
                loading={submitting}
                onConfirm={() => submitExam(false)}
                onCancel={() => setConfirmSubmitOpen(false)}
            />
        </section>
    );
}
