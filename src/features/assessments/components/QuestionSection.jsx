import { useState } from "react";
import { toast } from "sonner";
import AssessmentService from "../../../services/assesment.service";
import * as XLSX from "xlsx";
import { useConfirm } from "../../../hooks/useConfirm";

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

  const [importFile, setImportFile] = useState(null);
  const [importing, setImporting] = useState(false);
  const [confirm, confirmDialog] = useConfirm();

  /* ---------------- DELETE QUESTION ---------------- */

  const deleteQuestion = async (question) => {
    const ok = await confirm({
      title: "Delete this question?",
      description: "This action cannot be undone.",
      confirmLabel: "Delete",
      variant: "danger",
    });

    if (!ok) return;

    try {
      await AssessmentService.deleteQuestion(question.id);

      setQuestions((prev) => prev.filter((q) => q.id !== question.id));

      if (editingId === question.id) {
        resetForm();
      }

      toast.success("Question deleted");
    } catch (err) {
      toast.error(
        err.response?.data?.message || "Failed to delete question."
      );
    }
  };

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
      toast.error("All options are required");
      return;
    }

    if (!choices.some((c) => c.is_correct)) {
      toast.error("Select correct answer");
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

        toast.error(err.response.data.message);

      } else {

        toast.error(
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
        choice_1: "Library",
        choice_2: "Framework",
        choice_3: "Language",
        choice_4: "Tool",
        correct_choice: "Library",
      },
    ];

    const worksheet = XLSX.utils.json_to_sheet(template);
    const workbook = XLSX.utils.book_new();

    XLSX.utils.book_append_sheet(workbook, worksheet, "Questions");

    XLSX.writeFile(workbook, "mcq_questions_template.xlsx");

  };

  /* ---------------- FILE SELECT ---------------- */

  const handleFileUpload = (e) => {

    const file = e.target.files[0];

    if (!file) return;

    const allowed = [
      "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
      "text/csv",
      "application/vnd.ms-excel",
    ];

    if (!allowed.includes(file.type)) {
      toast.error("Only CSV and XLSX files are allowed.");
      e.target.value = "";
      return;
    }

    if (file.size > 5 * 1024 * 1024) {
      toast.error("Maximum file size is 5 MB.");
      e.target.value = "";
      return;
    }

    setImportFile(file);

  };

  /* ---------------- IMPORT QUESTIONS ---------------- */

  const importQuestions = async () => {

    if (!importFile) {
      toast.error("No file selected");
      return;
    }

    try {

      setImporting(true);

      const res = await AssessmentService.bulkStoreQuestions(
        assessmentId,
        importFile
      );

      const refreshed = await AssessmentService.getAssessmentWithQuestionsChoices(
        assessmentId
      );

      setQuestions(refreshed || []);
      setImportFile(null);

      toast.success(
        `${res.inserted} question(s) imported.` +
          (res.errors?.length ? ` ${res.errors.length} row(s) skipped.` : "")
      );

      if (res.errors?.length) {
        console.warn("Bulk import skipped rows:", res.errors);
      }

    } catch (err) {

      toast.error(err.response?.data?.message || "Import failed");

    } finally {

      setImporting(false);

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

        {importFile && (
          <button
            onClick={importQuestions}
            disabled={importing}
            className="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 disabled:opacity-50"
          >
            {importing ? "Importing..." : `Import "${importFile.name}"`}
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

            <div className="flex items-center gap-4">
              <button
                onClick={() => handleEdit(q)}
                className="text-blue-600 text-sm"
              >
                Edit
              </button>

              <button
                onClick={() => deleteQuestion(q)}
                className="text-red-600 text-sm"
              >
                Delete
              </button>
            </div>

          </div>

        ))}

      </div>

      {confirmDialog}

    </div>

  );

}