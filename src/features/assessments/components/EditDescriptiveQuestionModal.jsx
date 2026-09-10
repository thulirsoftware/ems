import { useEffect, useState } from "react";
import { toast } from "sonner";
import AssessmentService from "../../../services/assesment.service";
import * as XLSX from "xlsx";
import { useConfirm } from "../../../hooks/useConfirm";

export default function EditDescriptiveQuestionModal({ assessmentId }) {

  const [questions, setQuestions] = useState([]);
  const [editingId, setEditingId] = useState(null);
  const [questionText, setQuestionText] = useState("");
  const [importFile, setImportFile] = useState(null);
  const [importing, setImporting] = useState(false);
  const [loading, setLoading] = useState(false);
  const [confirm, confirmDialog] = useConfirm();

  // ================= LOAD QUESTIONS =================

  const loadQuestions = async () => {
    const data = await AssessmentService.getQuestions(assessmentId);
    setQuestions(data || []);
  };

  useEffect(() => {

    if (!assessmentId) return;

    loadQuestions();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [assessmentId]);


  // ================= ADD QUESTION =================

  const addQuestion = async () => {

    if (!questionText.trim()) {
      toast.error("Question is required");
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
      toast.error("Failed to add question");

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
      toast.error("Question is required");
      return;
    }

    try {

      setLoading(true);

      const existing = questions.find((q) => q.id === editingId);

      const payload = {
        type: "descriptive",
        question_text: questionText,
        order: existing?.order ?? 1
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
      toast.error("Update failed");

    } finally {

      setLoading(false);

    }

  };


  // ================= DELETE =================

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

      setQuestions(prev => prev.filter(q => q.id !== question.id));

      if (editingId === question.id) {
        setEditingId(null);
        setQuestionText("");
      }

      toast.success("Question deleted");
    } catch (err) {
      toast.error(
        err.response?.data?.message || "Failed to delete question."
      );
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

            <div className="flex items-center gap-4">
              <button
                onClick={() => handleEdit(q)}
                className="text-blue-600"
              >
                Edit
              </button>

              <button
                onClick={() => deleteQuestion(q)}
                className="text-red-600"
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