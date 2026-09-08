import { useEffect, useState } from "react";
import AssessmentService from "../../../services/assesment.service";
import * as XLSX from "xlsx";

const emptyChoices = [
  { option: "", is_correct: false },
  { option: "", is_correct: false },
  { option: "", is_correct: false },
  { option: "", is_correct: false },
];

export default function EditQuestionSection({ assessmentId }) {

  const [questions, setQuestions] = useState([]);
  const [questionText, setQuestionText] = useState("");
  const [choices, setChoices] = useState(emptyChoices);
  const [loading, setLoading] = useState(false);
  const [editingId, setEditingId] = useState(null);
  const [editingOrder, setEditingOrder] = useState(null);
  const [importRows, setImportRows] = useState([]);

  // ================= LOAD QUESTIONS =================

  useEffect(() => {
    if (!assessmentId) return;
    loadQuestions();
  }, [assessmentId]);

  const loadQuestions = async () => {
    const data =
      await AssessmentService.getAssessmentWithQuestionsChoices(
        assessmentId
      );
    setQuestions(data || []);
  };

  // ================= OPTION HANDLERS =================

  const handleOptionChange = (index, value) => {
    setChoices((prev) =>
      prev.map((c, i) =>
        i === index ? { ...c, option: value } : c
      )
    );
  };

  const handleCorrectChange = (index) => {
    setChoices((prev) =>
      prev.map((c, i) => ({
        ...c,
        is_correct: i === index,
      }))
    );
  };

  // ================= EDIT QUESTION =================

  const handleEdit = (question) => {

    setEditingId(question.id);
    setEditingOrder(question.order);
    setQuestionText(question.question_text);

    setChoices(
      question.choices.map((c) => ({
        option: c.option,
        is_correct: c.is_correct,
      }))
    );

  };

  const resetForm = () => {
    setQuestionText("");
    setChoices(emptyChoices);
    setEditingId(null);
    setEditingOrder(null);
  };

  // ================= ADD QUESTION =================

  const addQuestion = async () => {

    if (!questionText.trim()) {
      alert("Question is required");
      return;
    }

    if (choices.some((c) => !c.option.trim())) {
      alert("All options are required");
      return;
    }

    if (!choices.some((c) => c.is_correct)) {
      alert("Select correct answer");
      return;
    }

    try {

      setLoading(true);

      const payload = {
        type: "mcq",
        question_text: questionText,
        order: questions.length + 1,
        choices: choices.map((c, i) => ({
          option: c.option,
          is_correct: c.is_correct,
          order: i + 1,
        })),
      };

      const saved =
        await AssessmentService.createQuestionWithChoices(
          assessmentId,
          payload
        );

      setQuestions((prev) => [...prev, saved]);

      resetForm();

    } catch (err) {

      console.error(err);
      alert("Failed to add question");

    } finally {

      setLoading(false);

    }

  };

  // ================= UPDATE QUESTION =================

  const updateQuestion = async () => {

    if (!questionText.trim()) {
      alert("Question is required");
      return;
    }

    if (choices.some((c) => !c.option.trim())) {
      alert("All options are required");
      return;
    }

    if (!choices.some((c) => c.is_correct)) {
      alert("Select correct answer");
      return;
    }

    try {

      setLoading(true);

      const payload = {
        type: "mcq",
        question_text: questionText,
        order: editingOrder,
        choices: choices.map((c, i) => ({
          option: c.option,
          is_correct: c.is_correct,
          order: i + 1,
        })),
      };

      const updated =
        await AssessmentService.updateQuestionWithChoices(
          editingId,
          payload
        );

      setQuestions((prev) =>
        prev.map((q) => (q.id === editingId ? updated : q))
      );

      resetForm();

    } catch (err) {

      console.error(err?.response?.data || err);
      alert("Failed to update question");

    } finally {

      setLoading(false);

    }

  };

  // ================= DOWNLOAD TEMPLATE =================

  const downloadTemplate = () => {

    const template = [
      {
        question_text: "What is React?",
        option_1: "Library",
        option_2: "Framework",
        option_3: "Language",
        option_4: "Tool",
        correct_option: 1,
      },
    ];

    const worksheet = XLSX.utils.json_to_sheet(template);
    const workbook = XLSX.utils.book_new();

    XLSX.utils.book_append_sheet(workbook, worksheet, "Questions");

    XLSX.writeFile(workbook, "mcq_questions_template.xlsx");

  };

  // ================= FILE READ =================

  const handleFileUpload = (e) => {

    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();

    reader.onload = (event) => {

      const data = new Uint8Array(event.target.result);
      const workbook = XLSX.read(data, { type: "array" });

      const sheet = workbook.Sheets[workbook.SheetNames[0]];
      const rows = XLSX.utils.sheet_to_json(sheet);

      setImportRows(rows);

      alert(`${rows.length} questions ready to import`);

    };

    reader.readAsArrayBuffer(file);

  };

  // ================= IMPORT QUESTIONS =================

  const importQuestions = async () => {

    if (importRows.length === 0) {
      alert("Upload file first");
      return;
    }

    try {

      setLoading(true);

      for (let i = 0; i < importRows.length; i++) {

        const row = importRows[i];
        const correct = Number(row.correct_option);

        const payload = {
          type: "mcq",
          question_text: row.question_text,
          order: questions.length + i + 1,
          choices: [
            {
              option: row.option_1,
              is_correct: correct === 1,
              order: 1,
            },
            {
              option: row.option_2,
              is_correct: correct === 2,
              order: 2,
            },
            {
              option: row.option_3,
              is_correct: correct === 3,
              order: 3,
            },
            {
              option: row.option_4,
              is_correct: correct === 4,
              order: 4,
            },
          ],
        };

        const saved =
          await AssessmentService.createQuestionWithChoices(
            assessmentId,
            payload
          );

        setQuestions((prev) => [...prev, saved]);

      }

      setImportRows([]);
      alert("Questions imported successfully");

    } catch (err) {

      console.error(err);
      alert("Import failed");

    } finally {

      setLoading(false);

    }

  };

  return (
    <div className="space-y-6">

      {/* ===== IMPORT SECTION ===== */}

      <div className="flex items-center gap-4">

        <button
          onClick={downloadTemplate}
          className="px-4 py-2 bg-green-600 text-white rounded"
        >
          Download Template
        </button>

        <input
          type="file"
          accept=".xlsx,.csv"
          onChange={handleFileUpload}
          className="border p-2 rounded"
        />

        {importRows.length > 0 && (
          <button
            onClick={importQuestions}
            className="px-4 py-2 bg-purple-600 text-white rounded"
          >
            Import Questions ({importRows.length})
          </button>
        )}

      </div>


      {/* ===== ADD / EDIT FORM ===== */}

      <div className="border p-4 rounded space-y-4">

        <h3 className="font-semibold">
          {editingId ? "Edit Question" : "Add Question"}
        </h3>

        <textarea
          className="border w-full p-3 rounded"
          placeholder="Enter question"
          value={questionText}
          onChange={(e) => setQuestionText(e.target.value)}
        />

        <div className="space-y-3">
          {choices.map((choice, index) => (
            <div
              key={index}
              className="flex items-center gap-3 border p-3 rounded"
            >
              <input
                type="radio"
                name="correctAnswer"
                checked={choice.is_correct}
                onChange={() => handleCorrectChange(index)}
              />

              <input
                type="text"
                value={choice.option}
                placeholder={`Option ${index + 1}`}
                onChange={(e) =>
                  handleOptionChange(index, e.target.value)
                }
                className="flex-1 border rounded px-2 py-1"
              />
            </div>
          ))}
        </div>

        {editingId ? (

          <button
            disabled={loading}
            onClick={updateQuestion}
            className="bg-blue-600 text-white px-6 py-2 rounded"
          >
            {loading ? "Updating..." : "Update Question"}
          </button>

        ) : (

          <button
            disabled={loading}
            onClick={addQuestion}
            className="bg-green-600 text-white px-6 py-2 rounded"
          >
            {loading ? "Adding..." : "Add Question"}
          </button>

        )}

      </div>


      {/* ===== QUESTION LIST ===== */}

      <div className="space-y-3">

        {questions.map((q, i) => (

          <div
            key={q.id}
            className="border p-4 rounded bg-gray-50 flex justify-between"
          >

            <p className="font-semibold">
              {i + 1}. {q.question_text}
            </p>

            <button
              onClick={() => handleEdit(q)}
              className="text-sm text-blue-600"
            >
              Edit
            </button>

          </div>

        ))}

      </div>

    </div>
  );
}