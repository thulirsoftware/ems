import { useState } from "react";
import AssessmentService from "../../../services/assesment.service";
import * as XLSX from "xlsx";

const emptyChoices = [
  { option: "", is_correct: false },
  { option: "", is_correct: false },
  { option: "", is_correct: false },
  { option: "", is_correct: false },
];

export default function QuestionSection({ assessmentId }) {

  const [errors, setErrors] = useState({});

  const [questions, setQuestions] = useState([]);
  const [questionText, setQuestionText] = useState("");
  const [choices, setChoices] = useState(emptyChoices);
  const [loading, setLoading] = useState(false);
  const [editingId, setEditingId] = useState(null);
  const [editingOrder, setEditingOrder] = useState(null);

  const [importRows, setImportRows] = useState([]);

  /* ---------------- OPTION CHANGE ---------------- */

  const validateQuestion = () => {
    const e = {};

    if (!questionText.trim()) {
      e.question = "Question is required.";
    } else if (questionText.trim().length > 5000) {
      e.question = "Question is too long.";
    }

    if (choices.length !== 4) {
      e.choices = "Exactly 4 options are required.";
    }

    choices.forEach((choice, index) => {

      if (!choice.option.trim()) {
        e[`option_${index}`] = `Option ${index + 1} is required.`;
      }

      if (choice.option.length > 1000) {
        e[`option_${index}`] =
          `Option ${index + 1} is too long.`;
      }

    });

    const correctAnswers = choices.filter(c => c.is_correct);

    if (correctAnswers.length === 0) {
      e.correct = "Select one correct answer.";
    }

    if (correctAnswers.length > 1) {
      e.correct = "Only one correct answer is allowed.";
    }

    const values = choices
      .map(c => c.option.trim().toLowerCase())
      .filter(Boolean);

    if (new Set(values).size !== values.length) {
      e.duplicate = "Duplicate options are not allowed.";
    }

    setErrors(e);

    return Object.keys(e).length === 0;
  };

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

  /* ---------------- EDIT QUESTION ---------------- */

  const handleEdit = (question) => {

    setEditingId(question.id);
    setQuestionText(question.question_text);
    setEditingOrder(question.order);

    setChoices(
      question.choices.map((c) => ({
        option: c.option,
        is_correct: c.is_correct,
      }))
    );

  };

  /* ---------------- RESET FORM ---------------- */

  const resetForm = () => {

    setQuestionText("");
    setChoices(emptyChoices);
    setEditingId(null);
    setEditingOrder(null);

  };

  /* ---------------- SUBMIT QUESTION ---------------- */

  const submitQuestion = async () => {

    if (!validateQuestion()) {
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
        order: editingId ? editingOrder : questions.length + 1,
        choices: choices.map((c, i) => ({
          option: c.option,
          is_correct: c.is_correct,
          order: i + 1,
        })),
      };

      let savedQuestion;

      if (editingId) {

        savedQuestion =
          await AssessmentService.updateQuestionWithChoices(
            editingId,
            payload
          );

        setQuestions((prev) =>
          prev.map((q) =>
            q.id === editingId ? savedQuestion : q
          )
        );

      } else {

        savedQuestion =
          await AssessmentService.createQuestionWithChoices(
            assessmentId,
            payload
          );

        setQuestions((prev) => [...prev, savedQuestion]);

      }

      resetForm();

    } catch (err) {

      console.error(err);

      if (err.response?.status === 422) {

        const backendErrors = {};

        Object.entries(
          err.response.data.errors || {}
        ).forEach(([key, value]) => {
          backendErrors[key] = value[0];
        });

        setErrors(backendErrors);

      } else if (err.response?.status === 403) {

        alert(err.response.data.message);

      } else {

        alert(
          err.response?.data?.message ||
          "Failed to save question."
        );

      }

    } finally {

      setLoading(false);

    }

  };

  /* ---------------- DOWNLOAD TEMPLATE ---------------- */

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

  /* ---------------- FILE UPLOAD (ONLY READ) ---------------- */

  const handleFileUpload = (e) => {

    const file = e.target.files[0];

    if (!file) return;

    const allowed = [
      "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
      "text/csv",
      "application/vnd.ms-excel",
    ];

    if (!allowed.includes(file.type)) {
      alert("Only CSV and XLSX files are allowed.");
      e.target.value = "";
      return;
    }

    if (file.size > 5 * 1024 * 1024) {
      alert("Maximum file size is 5 MB.");
      e.target.value = "";
      return;
    }
    if (!file) return;

    const reader = new FileReader();

    reader.onload = (event) => {

      const data = new Uint8Array(event.target.result);
      const workbook = XLSX.read(data, { type: "array" });

      const sheet = workbook.Sheets[workbook.SheetNames[0]];
      const rows = XLSX.utils.sheet_to_json(sheet);

      setImportRows(rows);

      alert(`${rows.length} questions loaded. Click Import.`);

    };

    reader.readAsArrayBuffer(file);

  };

  /* ---------------- IMPORT QUESTIONS ---------------- */

  const importQuestions = async () => {

    if (importRows.length === 0) {
      alert("No file loaded");
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

      alert("Questions imported successfully");

      setImportRows([]);

    } catch (err) {

      console.error(err);
      alert("Import failed");

    } finally {

      setLoading(false);

    }

  };

  return (

    <div className="space-y-6">

      {/* IMPORT + TEMPLATE */}
      <div className="flex items-center gap-4">

        <button
          onClick={downloadTemplate}
          className="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
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
            className="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700"
          >
            Import Questions ({importRows.length})
          </button>
        )}

      </div>

      {/* QUESTION */}
      <textarea
        className="border w-full p-3 rounded"
        placeholder="Enter question"
        value={questionText}
        onChange={(e) => setQuestionText(e.target.value)}
      />
      {errors.question && (
        <p className="text-red-500 text-sm mt-1">
          {errors.question}
        </p>
      )}

      {/* OPTIONS */}
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
            {errors.correct && (
              <p className="text-red-500 text-sm">
                {errors.correct}
              </p>
            )}

            <input
              type="text"
              placeholder={`Option ${index + 1}`}
              value={choice.option}
              onChange={(e) =>
                handleOptionChange(index, e.target.value)
              }
              className="flex-1 border rounded px-2 py-1"
            />
            {errors[`option_${index}`] && (
              <p className="text-red-500 text-xs">
                {errors[`option_${index}`]}
              </p>
            )}


          </div>

        ))}

      </div>

      {/* BUTTON */}
      <button
        disabled={loading}
        onClick={submitQuestion}
        className="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 disabled:opacity-50"
      >
        {loading
          ? "Saving..."
          : editingId
            ? "Update Question"
            : "Add Question"}
      </button>

      {/* PREVIEW */}
      <div className="mt-6 space-y-3">

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
              className="text-blue-600 text-sm"
            >
              Edit
            </button>

          </div>

        ))}

      </div>

    </div>

  );

}