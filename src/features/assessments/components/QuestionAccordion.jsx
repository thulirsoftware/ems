import { useState } from "react";

export default function QuestionAccordion({ q, index }) {
  const [open, setOpen] = useState(false);

  const isCorrect =
    q.user_option_id === q.correct_option_id;

  return (
    <div className="bg-white border rounded-xl shadow-sm overflow-hidden">

      {/* ===== HEADER ===== */}
      <div
        onClick={() => setOpen(!open)}
        className="cursor-pointer px-6 py-4 flex justify-between items-center hover:bg-gray-50 transition"
      >
        <div className="flex items-center gap-4">
          <span className="font-semibold text-gray-700">
            Q{index + 1}
          </span>

          <p className="text-gray-800 font-medium line-clamp-1">
            {q.question_text}
          </p>
        </div>

        <div className="flex items-center gap-4">
          {/* Result Badge */}
          {q.user_option_id ? (
            isCorrect ? (
              <span className="text-xs px-3 py-1 rounded-full bg-green-100 text-green-700 font-semibold">
                Correct
              </span>
            ) : (
              <span className="text-xs px-3 py-1 rounded-full bg-red-100 text-red-700 font-semibold">
                Wrong
              </span>
            )
          ) : (
            <span className="text-xs px-3 py-1 rounded-full bg-gray-100 text-gray-600 font-semibold">
              Unanswered
            </span>
          )}

          {/* Arrow */}
          <span
            className={`transition-transform duration-300 ${
              open ? "rotate-180" : ""
            }`}
          >
            ▼
          </span>
        </div>
      </div>

      {/* ===== BODY ===== */}
      <div
        className={`transition-all duration-300 ease-in-out ${
          open ? "max-h-[600px] p-6 pt-0" : "max-h-0"
        } overflow-hidden`}
      >
        <div className="space-y-3 mt-4">
          {q.options.map((opt) => {
            const isUser = opt.id === q.user_option_id;
            const isAnswer = opt.id === q.correct_option_id;

            let style = "bg-gray-50 border-gray-200";

            if (isAnswer)
              style = "bg-green-100 border-green-500";
            else if (isUser && !isCorrect)
              style = "bg-red-100 border-red-500";

            return (
              <div
                key={opt.id}
                className={`border rounded-lg px-4 py-3 flex justify-between items-center ${style}`}
              >
                <span>{opt.option}</span>

                <div className="text-sm font-semibold">
                  {isAnswer && (
                    <span className="text-green-700">
                      ✓ Correct
                    </span>
                  )}
                  {isUser && !isCorrect && (
                    <span className="text-red-700">
                      ✗ Your Answer
                    </span>
                  )}
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
}