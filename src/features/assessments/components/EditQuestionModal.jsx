import { useState } from "react";
import AssessmentService from "../../../services/assesment.service";

export default function EditQuestionModal({ question, onClose, onUpdated }) {
  const [text, setText] = useState(question.question_text);
  const [choices, setChoices] = useState(question.choices);

  const save = async () => {
    await AssessmentService.updateQuestionWithChoices(question.id, {
      type: question.type,
      question_text: text,
      order: question.order,
      choices: choices.map((c, i) => ({
        option: c.option,
        is_correct: c.is_correct,
        order: i + 1,
      })),
    });

    onUpdated();
    onClose();
  };

  return (
    <div className="fixed inset-0 bg-black/40 flex justify-center items-center">
      <div className="bg-white w-[700px] rounded-xl">
        <div className="border-b p-4 flex justify-between">
          <h3>Edit Question</h3>
          <button onClick={onClose}>✕</button>
        </div>

        <div className="p-6 space-y-3">
          <textarea
            className="border w-full p-2 rounded"
            value={text}
            onChange={(e) => setText(e.target.value)}
          />

          {choices.map((c, i) => (
            <div key={i} className="flex gap-3">
              <input
                type="checkbox"
                checked={c.is_correct}
                onChange={() => {
                  const updated = [...choices];
                  updated[i].is_correct = !updated[i].is_correct;
                  setChoices(updated);
                }}
              />
              <input
                className="border p-2 rounded flex-1"
                value={c.option}
                onChange={(e) => {
                  const updated = [...choices];
                  updated[i].option = e.target.value;
                  setChoices(updated);
                }}
              />
            </div>
          ))}
        </div>

        <div className="border-t p-4 text-right">
          <button
            onClick={save}
            className="bg-blue-600 text-white px-4 py-2 rounded"
          >
            Update
          </button>
        </div>
      </div>
    </div>
  );
}
