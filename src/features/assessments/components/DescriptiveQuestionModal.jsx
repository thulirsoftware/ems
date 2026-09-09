import { useState } from "react";
import { toast } from "sonner";
import AssessmentService from "../../../services/assesment.service";
import * as XLSX from "xlsx";

export default function DescriptiveQuestionModal({ assessmentId }) {

  const [questionText, setQuestionText] = useState("");
  const [questions, setQuestions] = useState([]);
  const [loading, setLoading] = useState(false);
  const [importRows, setImportRows] = useState([]);

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

  /* ---------------- READ FILE ---------------- */

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

      toast.success(`${rows.length} questions ready to import`);

    };

    reader.readAsArrayBuffer(file);

  };

  /* ---------------- IMPORT QUESTIONS ---------------- */

  const importQuestions = async () => {

    if (importRows.length === 0) {
      toast.error("Upload file first");
      return;
    }

    try {

      setLoading(true);

      for (let i = 0; i < importRows.length; i++) {

        const row = importRows[i];

        const payload = {
          type: "descriptive",
          question_text: row.question_text,
          order: questions.length + i + 1,
        };

        const saved =
          await AssessmentService.createQuestion(
            assessmentId,
            payload
          );

        setQuestions((prev) => [...prev, saved]);

      }

      setImportRows([]);
      toast.success("Questions imported successfully");

    } catch (err) {

      console.error(err);
      toast.error("Import failed");

    } finally {

      setLoading(false);

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

        {importRows.length > 0 && (
          <button
            onClick={importQuestions}
            className="px-4 py-2 bg-purple-600 text-white rounded"
          >
            Import Questions ({importRows.length})
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
          <div key={q.id} className="border p-4 rounded bg-gray-50">
            <p className="font-semibold">
              {i + 1}. {q.question_text}
            </p>
          </div>
        ))}
      </div>

    </div>
  );
}