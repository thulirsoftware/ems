import { useEffect, useState } from "react";
import { useParams, useNavigate } from "react-router-dom";
import AssessmentService from "../../../services/assesment.service";

export default function EvaluateAssessment() {

  const { assessmentId, userId } = useParams();
  const navigate = useNavigate();

  const [questions, setQuestions] = useState([]);
  const [loading, setLoading] = useState(true);

  /* --------------------------------------------------
     LOAD ANSWERS
  -------------------------------------------------- */
  useEffect(() => {
    loadAnswers();
  }, []);

  const loadAnswers = async () => {
    try {

      const data = await AssessmentService.getStudentAnswers(
        assessmentId,
        userId
      );

      const formatted = (data?.questions || []).map((q) => ({
        ...q,
        user_answer:
          typeof q.user_answer === "object"
            ? q.user_answer?.text
            : q.user_answer
      }));

      setQuestions(formatted);

    } catch (err) {
      console.error("Failed to load answers", err);
    } finally {
      setLoading(false);
    }
  };

  /* --------------------------------------------------
     GRADE QUESTION
  -------------------------------------------------- */
  const handleGrade = async (questionId, value) => {

    try {

      await AssessmentService.gradeQuestion(
        assessmentId,
        userId,
        questionId,
        { is_correct: value }
      );

      const updatedQuestions = questions.map((q) =>
        q.question_id === questionId
          ? { ...q, is_correct: value }
          : q
      );

      setQuestions(updatedQuestions);

      /* check if all graded */
      const allGraded = updatedQuestions.every(
        (q) => q.is_correct !== null
      );

      if (allGraded) {

        const result = await AssessmentService.getUserResult(
          assessmentId,
          userId
        );

        navigate(`/admin/result/${assessmentId}/${userId}`, {
          state: { result }
        });
      }

    } catch (err) {
      console.error("Grading failed", err);
    }
  };

  /* --------------------------------------------------
     UI STATES
  -------------------------------------------------- */

  if (loading) {
    return (
      <div className="p-10 text-center text-gray-500">
        Loading answers...
      </div>
    );
  }

  if (!questions.length) {
    return (
      <div className="p-10 text-center text-gray-500">
        No questions found
      </div>
    );
  }

  const gradedCount = questions.filter(q => q.is_correct !== null).length;

  /* --------------------------------------------------
     UI
  -------------------------------------------------- */

  return (
    <div className="p-6 max-w-4xl mx-auto">

      {/* HEADER */}
      <div className="flex justify-between items-center mb-8">

        <h2 className="text-2xl font-semibold">
          Evaluate Answers
        </h2>

        <div className="text-sm text-gray-500">
          Progress: <span className="font-semibold">
            {gradedCount} / {questions.length}
          </span>
        </div>

      </div>

      {/* QUESTIONS */}

      <div className="space-y-6">

        {questions.map((q, index) => (

          <div
            key={q.question_id}
            className="border rounded-xl p-6 bg-white shadow-sm"
          >

            {/* QUESTION */}
            <p className="font-medium text-gray-800 mb-4">
              Q{index + 1}. {q.question_text}
            </p>

            {/* STUDENT ANSWER */}

            <div className="bg-gray-100 p-4 rounded mb-4">

              <p className="text-sm text-gray-700 whitespace-pre-wrap">
                {q.user_answer || "No answer submitted"}
              </p>

            </div>

            {/* ACTION BUTTONS */}

            <div className="flex gap-3">

              <button
                onClick={() =>
                  handleGrade(q.question_id, true)
                }
                className={`px-4 py-2 rounded-lg font-medium transition ${
                  q.is_correct === true
                    ? "bg-green-600 text-white"
                    : "bg-gray-200 hover:bg-green-100"
                }`}
              >
                Correct
              </button>

              <button
                onClick={() =>
                  handleGrade(q.question_id, false)
                }
                className={`px-4 py-2 rounded-lg font-medium transition ${
                  q.is_correct === false
                    ? "bg-red-600 text-white"
                    : "bg-gray-200 hover:bg-red-100"
                }`}
              >
                Incorrect
              </button>

            </div>

          </div>

        ))}

      </div>

    </div>
  );
}