import { useEffect, useState } from "react";
import AssessmentService from "../../../services/assesment.service";

export default function ViewQuestionModal({ questionId, onClose }) {
  const [question, setQuestion] = useState(null);
  const [loading, setLoading] = useState(false);

  // ================= LOAD QUESTION =================
  useEffect(() => {
    if (!questionId) return;

    setLoading(true);
    AssessmentService.getQuestionWithChoices(questionId)
      .then((data) => {
        setQuestion(data);
      })
      .catch((err) => {
        console.error(err);
        alert("Failed to load question");
        onClose();
      })
      .finally(() => setLoading(false));
  }, [questionId]);

  if (!question) return null;

  return (
    <div className="fixed inset-0 bg-black/40 z-50 flex justify-center items-center">
      <div className="bg-white w-[600px] rounded-xl shadow-xl">

        {/* Header */}
        <div className="border-b p-4 flex justify-between items-center">
          <h3 className="font-semibold">View Question</h3>
          <button onClick={onClose}>✕</button>
        </div>

        {/* Body */}
        <div className="p-6 space-y-4">
          {loading && (
            <p className="text-sm text-gray-400">Loading...</p>
          )}

          {!loading && (
            <>
              {/* Question */}
              <p className="font-semibold text-lg">
                {question.question_text}
              </p>

              {/* Choices */}
              <div className="space-y-2">
                {question.choices.map((c, i) => (
                  <div
                    key={i}
                    className={`border p-3 rounded flex justify-between items-center
                      ${
                        c.is_correct
                          ? "bg-green-50 border-green-400"
                          : "bg-gray-50"
                      }`}
                  >
                    <span>{c.option}</span>
                    {c.is_correct && (
                      <span className="text-green-600 font-semibold">
                        ✔ Correct
                      </span>
                    )}
                  </div>
                ))}
              </div>
            </>
          )}
        </div>

        {/* Footer */}
        <div className="border-t p-4 flex justify-end">
          <button
            onClick={onClose}
            className="px-4 py-2 border rounded"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  );
}
