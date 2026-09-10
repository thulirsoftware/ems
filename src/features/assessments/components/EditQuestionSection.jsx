import { useEffect, useState } from "react";
import { toast } from "sonner";
import AssessmentService from "../../../services/assesment.service";
import * as XLSX from "xlsx";
import { useConfirm } from "../../../hooks/useConfirm";
import ManageChoicesModal from "./ManageChoicesModal";

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
  const [importFile, setImportFile] = useState(null);
  const [importing, setImporting] = useState(false);
  const [manageChoicesFor, setManageChoicesFor] = useState(null);
  const [confirm, confirmDialog] = useConfirm();

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

  // ================= DELETE QUESTION =================

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

  // ================= ADD QUESTION =================

  const addQuestion = async () => {

    if (!questionText.trim()) {
      toast.error("Question is required");
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
      toast.error("Failed to add question");

    } finally {

      setLoading(false);

    }

  };

  // ================= UPDATE QUESTION =================

  const updateQuestion = async () => {

    if (!questionText.trim()) {
      toast.error("Question is required");
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
      toast.error("Failed to update question");

    } finally {

      setLoading(false);

    }

  };

  // ================= DOWNLOAD TEMPLATE =================

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

  // ================= FILE SELECT =================

  const handleFileUpload = (e) => {

    const file = e.target.files[0];
    if (!file) return;

    setImportFile(file);

  };

  // ================= IMPORT QUESTIONS =================

  const importQuestions = async () => {

    if (!importFile) {
      toast.error("Upload file first");
      return;
    }

    try {

      setImporting(true);

      const res = await AssessmentService.bulkStoreQuestions(
        assessmentId,
        importFile
      );

      await loadQuestions();
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

        {importFile && (
          <button
            onClick={importQuestions}
            disabled={importing}
            className="px-4 py-2 bg-purple-600 text-white rounded disabled:opacity-50"
          >
            {importing ? "Importing..." : `Import "${importFile.name}"`}
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

            <div className="flex items-center gap-4">
              <button
                onClick={() => handleEdit(q)}
                className="text-sm text-blue-600"
              >
                Edit
              </button>

              {q.type === "mcq" && (
                <button
                  onClick={() => setManageChoicesFor(q)}
                  className="text-sm text-purple-600"
                >
                  Choices
                </button>
              )}

              <button
                onClick={() => deleteQuestion(q)}
                className="text-sm text-red-600"
              >
                Delete
              </button>
            </div>

          </div>

        ))}

      </div>

      {confirmDialog}

      {manageChoicesFor && (
        <ManageChoicesModal
          question={manageChoicesFor}
          onClose={() => setManageChoicesFor(null)}
          onChanged={loadQuestions}
        />
      )}

    </div>
  );
}