function ChoiceSection({ question, index }) {
  const [choices, setChoices] = useState([]);
  const [text, setText] = useState("");

  const isMulti = question.type === "MULTI_CHOICE";

  const addChoice = async () => {
    const res = await AssessmentService.createChoice(question.id, {
      choice_text: text,
      is_correct: false,
    });

    setChoices([...choices, res]);
    setText("");
  };

  const toggleCorrect = async (choice) => {
    if (isMulti) {
      await AssessmentService.updateChoice(choice.id, {
        is_correct: !choice.is_correct,
      });

      setChoices((prev) =>
        prev.map((c) =>
          c.id === choice.id
            ? { ...c, is_correct: !c.is_correct }
            : c
        )
      );
    } else {
      await Promise.all(
        choices.map((c) =>
          AssessmentService.updateChoice(c.id, {
            is_correct: c.id === choice.id,
          })
        )
      );

      setChoices((prev) =>
        prev.map((c) => ({
          ...c,
          is_correct: c.id === choice.id,
        }))
      );
    }
  };

  return (
    <div className="border rounded-lg p-4">
      <p className="font-medium mb-2">
        {index + 1}. {question.question_text}
        <span className="ml-2 text-xs text-gray-500">
          ({isMulti ? "Multiple Correct" : "Single Correct"})
        </span>
      </p>

      {choices.map((c) => (
        <label key={c.id} className="flex items-center gap-2 mb-2">
          <input
            type={isMulti ? "checkbox" : "radio"}
            name={`q-${question.id}`}
            checked={c.is_correct}
            onChange={() => toggleCorrect(c)}
          />
          {c.choice_text}
        </label>
      ))}

      <div className="flex gap-2 mt-3">
        <input
          className="border p-2 rounded flex-1"
          placeholder="Add choice"
          value={text}
          onChange={(e) => setText(e.target.value)}
        />
        <button
          onClick={addChoice}
          className="bg-gray-200 px-4 rounded"
        >
          Add
        </button>
      </div>
    </div>
  );
}
