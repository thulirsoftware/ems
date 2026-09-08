import { useEffect, useState } from "react";
import AssessmentService from "../../../services/assesment.service";
import * as XLSX from "xlsx";

export default function EditDescriptiveQuestionModal({ assessmentId }) {

  const [questions, setQuestions] = useState([]);
  const [editingId, setEditingId] = useState(null);
  const [questionText, setQuestionText] = useState("");
  const [importRows, setImportRows] = useState([]);
  const [loading, setLoading] = useState(false);

  // ================= LOAD QUESTIONS =================

  useEffect(() => {

    if (!assessmentId) return;

    AssessmentService.getQuestions(assessmentId)
      .then(setQuestions);

  }, [assessmentId]);


  // ================= ADD QUESTION =================

  const addQuestion = async () => {

    if (!questionText.trim()) {
      alert("Question is required");
      return;
    }

    try {

      setLoading(true);

      const payload = {
        type: "descriptive",
        question_text: questionText,
        order: questions.length + 1
      };

      const saved =
        await AssessmentService.createQuestion(
          assessmentId,
          payload
        );

      setQuestions(prev => [...prev, saved]);

      setQuestionText("");

    } catch (err) {

      console.error(err);
      alert("Failed to add question");

    } finally {

      setLoading(false);

    }

  };


  // ================= EDIT =================

  const handleEdit = (q) => {

    setEditingId(q.id);
    setQuestionText(q.question_text);

  };


  // ================= UPDATE =================

  const updateQuestion = async () => {

    if (!questionText.trim()) {
      alert("Question is required");
      return;
    }

    try {

      setLoading(true);

      const payload = {
        type: "descriptive",
        question_text: questionText,
        order: 1
      };

      const updated =
        await AssessmentService.updateQuestion(
          editingId,
          payload
        );

      setQuestions(prev =>
        prev.map(q => q.id === editingId ? updated : q)
      );

      setEditingId(null);
      setQuestionText("");

    } catch (err) {

      console.error(err);
      alert("Update failed");

    } finally {

      setLoading(false);

    }

  };


  // ================= TEMPLATE DOWNLOAD =================

  const downloadTemplate = () => {

    const template = [
      { question_text: "Explain React hooks" }
    ];

    const worksheet = XLSX.utils.json_to_sheet(template);
    const workbook = XLSX.utils.book_new();

    XLSX.utils.book_append_sheet(workbook, worksheet, "Questions");

    XLSX.writeFile(workbook, "descriptive_questions_template.xlsx");

  };


  // ================= FILE UPLOAD =================

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

        const payload = {
          type: "descriptive",
          question_text: row.question_text,
          order: questions.length + i + 1
        };

        const saved =
          await AssessmentService.createQuestion(
            assessmentId,
            payload
          );

        setQuestions(prev => [...prev, saved]);

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

    <div className="space-y-8">

      {/* ===== IMPORT SECTION ===== */}

      <div className="border rounded-lg p-4 bg-gray-50 space-y-3">

        <h3 className="font-semibold text-gray-700">
          Import Questions
        </h3>

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
              Import ({importRows.length})
            </button>
          )}

        </div>

      </div>


      {/* ===== ADD / EDIT QUESTION ===== */}

      <div className="border rounded-lg p-4 space-y-3">

        <h3 className="font-semibold text-gray-700">
          {editingId ? "Edit Question" : "Add Question"}
        </h3>

        <textarea
          className="border w-full p-3 rounded"
          rows={4}
          placeholder="Enter descriptive question..."
          value={questionText}
          onChange={(e) => setQuestionText(e.target.value)}
        />

        {editingId ? (

          <button
            onClick={updateQuestion}
            className="bg-blue-600 text-white px-5 py-2 rounded"
          >
            {loading ? "Updating..." : "Update Question"}
          </button>

        ) : (

          <button
            onClick={addQuestion}
            className="bg-blue-600 text-white px-5 py-2 rounded"
          >
            {loading ? "Saving..." : "Add Question"}
          </button>

        )}

      </div>


      {/* ===== QUESTION LIST ===== */}

      <div className="space-y-3">

        <h3 className="font-semibold text-gray-700">
          Questions
        </h3>

        {questions.length === 0 && (
          <p className="text-gray-400 text-sm">
            No questions added yet
          </p>
        )}

        {questions.map((q, i) => (

          <div
            key={q.id}
            className="border p-4 rounded bg-gray-50 flex justify-between"
          >

            <p>{i + 1}. {q.question_text}</p>

            <button
              onClick={() => handleEdit(q)}
              className="text-blue-600"
            >
              Edit
            </button>

          </div>

        ))}

      </div>

    </div>

  );

}