import { useState } from "react";
import { toast } from "sonner";
import AssessmentService from "../../../services/assesment.service";
import * as XLSX from "xlsx";
import { useConfirm } from "../../../hooks/useConfirm";

export default function DescriptiveQuestionModal({ assessmentId }) {

  const [questionText, setQuestionText] = useState("");
  const [questions, setQuestions] = useState([]);
  const [loading, setLoading] = useState(false);
  const [importFile, setImportFile] = useState(null);
  const [importing, setImporting] = useState(false);
  const [confirm, confirmDialog] = useConfirm();

  /* ---------------- ADD QUESTION ---------------- */

  const submitQuestion = async () => {

    if (!questionText.trim()) {
      toast.error("Question is required");
      return;
    }

    try {

      setLoading(true);

      const payload = {
        type: "descriptive",
        question_text: questionText,
        order: questions.length + 1,
      };

      const savedQuestion =
        await AssessmentService.createQuestion(
          assessmentId,
          payload
        );

      setQuestions((prev) => [...prev, savedQuestion]);
      setQuestionText("");

    } catch (err) {

      console.error(err);
      toast.error("Failed to save question");

    } finally {

      setLoading(false);

    }

  };

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
      toast.success("Question deleted");
    } catch (err) {
      toast.error(
        err.response?.data?.message || "Failed to delete question."
      );
    }
  };

  /* ---------------- DOWNLOAD TEMPLATE ---------------- */

  const downloadTemplate = () => {

    const template = [
      {
        question_text: "Explain React hooks",
      },
    ];

    const worksheet = XLSX.utils.json_to_sheet(template);
    const workbook = XLSX.utils.book_new();

    XLSX.utils.book_append_sheet(workbook, worksheet, "Questions");

    XLSX.writeFile(workbook, "descriptive_questions_template.xlsx");

  };

  /* ---------------- FILE SELECT ---------------- */

  const handleFileUpload = (e) => {

    const file = e.target.files[0];
    if (!file) return;

    setImportFile(file);

  };

  /* ---------------- IMPORT QUESTIONS ---------------- */

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

      const refreshed = await AssessmentService.getQuestions(assessmentId);

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

      {/* IMPORT SECTION */}
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

      {/* QUESTION INPUT */}
      <div>
        <label className="text-sm font-medium">
          Descriptive Question
        </label>

        <textarea
          className="border w-full p-3 rounded mt-2"
          rows={4}
          placeholder="Enter descriptive question..."
          value={questionText}
          onChange={(e) => setQuestionText(e.target.value)}
        />
      </div>

      {/* INFO */}
      <div className="bg-gray-50 border rounded p-3 text-sm text-gray-600">
        Candidate will write answer during the assessment.
      </div>

      {/* ADD BUTTON */}
      <button
        onClick={submitQuestion}
        disabled={loading}
        className="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700 disabled:opacity-50"
      >
        {loading ? "Saving..." : "Add Question"}
      </button>

      {/* QUESTION LIST */}
      <div className="space-y-3">
        {questions.map((q, i) => (
          <div
            key={q.id}
            className="border p-4 rounded bg-gray-50 flex justify-between items-start"
          >
            <p className="font-semibold">
              {i + 1}. {q.question_text}
            </p>

            <button
              onClick={() => deleteQuestion(q)}
              className="text-sm text-red-600"
            >
              Delete
            </button>
          </div>
        ))}
      </div>

      {confirmDialog}

    </div>
  );
}