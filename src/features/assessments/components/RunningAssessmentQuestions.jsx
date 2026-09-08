import { useEffect, useState } from "react";
import { useParams, useNavigate, useLocation } from "react-router-dom";
import AssessmentService from "../../../services/assesment.service";

export default function RunningAssessmentQuestions() {
    const { examid, attemptId } = useParams();
    const navigate = useNavigate();
    const location = useLocation();

    // exam meta coming from details page navigation
    const examMeta = location.state;

    const [questions, setQuestions] = useState([]);
    const [current, setCurrent] = useState(0);
    const [answers, setAnswers] = useState({});
    const [timeLeft, setTimeLeft] = useState(null);
    const [loading, setLoading] = useState(true);

    /* --------------------------------------------------
       ✅ CALCULATE REMAINING TIME FROM END DATETIME
    -------------------------------------------------- */
    const calculateRemainingTime = () => {
        if (!examMeta) return 0;

        const { publish_date, end_time } = examMeta;

        // exam end datetime
        const examEnd = new Date(`${publish_date}T${end_time}`);

        // current time
        const now = new Date();

        const diff = Math.floor((examEnd - now) / 1000);

        return diff > 0 ? diff : 0;
    };

    /* --------------------------------------------------
       LOAD QUESTIONS
    -------------------------------------------------- */
    useEffect(() => {
        if (examid && attemptId) loadQuestions();
    }, [examid, attemptId]);

    const loadQuestions = async () => {
        try {
            const data =
                await AssessmentService.GetAssessmentQuestions(
                    examid,
                    attemptId
                );

            setQuestions(data);

            // ✅ dynamic timer from exam end time
            setTimeLeft(calculateRemainingTime());
        } catch (err) {
            console.error("Assessment not started or expired");
            navigate("/assesments");
        } finally {
            setLoading(false);
        }
    };

    /* --------------------------------------------------
       TIMER RUNNER
    -------------------------------------------------- */
    useEffect(() => {
        if (timeLeft === null) return;

        if (timeLeft <= 0) {
            submitExam();
            return;
        }

        const timer = setInterval(() => {
            setTimeLeft((t) => t - 1);
        }, 1000);

        return () => clearInterval(timer);
    }, [timeLeft]);

    /* --------------------------------------------------
       ANSWER SELECT
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

            console.log("Answer saved:", questionId, optionId);

        } catch (err) {
            console.error(
                "Failed to save answer",
                err.response?.data || err.message
            );
        }
    };
    const handleTextAnswer = async (questionId, text) => {

        setAnswers(prev => ({
            ...prev,
            [questionId]: text
        }));

        try {

            await AssessmentService.SaveAnswer(examid, {
                question_id: questionId,
                answer: text
            });

            console.log("Text answer saved");

        } catch (err) {

            console.error(
                "Save failed",
                err.response?.data || err.message
            );

        }

    };

    /* --------------------------------------------------
       SUBMIT EXAM
    -------------------------------------------------- */
    const submitExam = async () => {
        try {
            const payload = {
                answers: Object.entries(answers).map(
                    ([question_id, value]) => {

                        const question = questions.find(
                            q => q.id === Number(question_id)
                        );

                        if (question.type === "MCQ") {

                            return {
                                question_id: Number(question_id),
                                choice_id: Number(value)
                            };

                        } else {

                            return {
                                question_id: Number(question_id),
                                answer: value
                            };

                        }

                    }
                ),
            };

            await AssessmentService.SubmitAssessment(
                examid,
                attemptId,
                payload
            );

            localStorage.removeItem("running_attempt");

            navigate(`/assesments/end/${examid}/result`);
        } catch (err) {
            console.error("Submit failed", err);
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

                <p className="text-red-600 font-bold">
                    ⏱ {Math.floor(timeLeft / 60)}:
                    {(timeLeft % 60).toString().padStart(2, "0")}
                </p>
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
                        onClick={submitExam}
                        className="px-6 py-2 bg-green-600 text-white rounded"
                    >
                        Submit Exam
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
        </section>
    );
}