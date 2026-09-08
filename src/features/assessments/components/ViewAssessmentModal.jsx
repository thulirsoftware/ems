import { useEffect, useState } from "react";
import AssessmentService from "../../../services/assesment.service";
import BatchService from "../../../services/batch.service";
export default function ViewAssessmentModal({ assessment, onClose }) {
  const [activeTab, setActiveTab] = useState("details");
  const [questions, setQuestions] = useState([]);
  const [loading, setLoading] = useState(false);
  const [batches, setBatches] = useState([]);
  const [batchUsers, setBatchUsers] = useState({});

  if (!assessment) return null;

  // ================= LOAD QUESTIONS WITH CHOICES =================
  useEffect(() => {
    if (activeTab === "questions") {
      fetchQuestionsWithChoices();
    }
  }, [activeTab]);

  const fetchQuestionsWithChoices = async () => {
    setLoading(true);
    try {
      const data =
        await AssessmentService.getAssessmentWithQuestionsChoices(
          assessment.id
        );
      setQuestions(data);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };
  useEffect(() => {
    if (assessment?.is_batch_wise) {
      loadBatches();
    }
  }, [assessment]);
  const loadBatches = async () => {
    try {
      const data = await BatchService.getBatchesByAssessment(
        assessment.id
      );

      setBatches(data || []);

      const users = {};

      for (const batch of data) {
        users[batch.id] = await BatchService.getBatchUsers(batch.id);
      }

      setBatchUsers(users);
    } catch (err) {
      console.error(err);
    }
  };


  // ================= DURATION =================
  const calculateDuration = () => {
    if (!assessment.start_time || !assessment.end_time) return "-";

    const [sh, sm] = assessment.start_time.split(":").map(Number);
    const [eh, em] = assessment.end_time.split(":").map(Number);

    return eh * 60 + em - (sh * 60 + sm);
  };

  return (
    <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center">
      <div className="bg-white w-[900px] rounded-xl shadow-xl max-h-[80vh] overflow-hidden">

        {/* Header */}
        <div className="border-b p-4 flex justify-between items-center">
          <h3 className="font-semibold text-lg">
            {assessment.title}
          </h3>
          <button onClick={onClose}>✕</button>
        </div>

        {/* Tabs */}
        <div className="flex border-b">
          <button
            className={`px-5 py-3 text-sm font-medium ${activeTab === "details"
              ? "border-b-2 border-red-500 text-red-600"
              : "text-gray-500"
              }`}
            onClick={() => setActiveTab("details")}
          >
            Assessment Details
          </button>

          <button
            className={`px-5 py-3 ${activeTab === "questions"
              ? "border-b-2 border-red-500 text-red-600"
              : "text-gray-500"
              }`}
            onClick={() => setActiveTab("questions")}
          >
            Questions
          </button>

          {batches.length > 0 && (
            <button
              className={`px-5 py-3 ${activeTab === "batch"
                ? "border-b-2 border-red-500 text-red-600"
                : "text-gray-500"
                }`}
              onClick={() => setActiveTab("batch")}
            >
              Batch
            </button>
          )}
        </div>

        {/* Content */}
        <div className="p-6 overflow-y-auto max-h-[60vh]">

          {/* ================= DETAILS TAB ================= */}
          {activeTab === "details" && (
            <div className="grid grid-cols-2 gap-4 text-sm">

              <div>
                <b>Assessment Type</b>
                <p className="text-gray-600">
                  {assessment.assessment_type?.name || "-"}
                </p>
              </div>

              <div>
                <b>Status</b>
                <p
                  className={`font-medium ${assessment.is_active
                    ? "text-green-600"
                    : "text-gray-500"
                    }`}
                >
                  {assessment.is_active ? "Active" : "Inactive"}
                </p>
              </div>

              <div className="col-span-2">
                <b>Description</b>
                <p className="text-gray-600">
                  {assessment.description || "-"}
                </p>
              </div>

              <div>
                <b>Publish Date</b>
                <p className="text-gray-600">
                  {assessment.publish_date || "-"}
                </p>
              </div>

              <div>
                <b>Total Duration</b>
                <p className="text-gray-600">
                  {calculateDuration()} minutes
                </p>
              </div>

              <div>
                <b>Start Time</b>
                <p className="text-gray-600">
                  {assessment.start_time || "-"}
                </p>
              </div>

              <div>
                <b>End Time</b>
                <p className="text-gray-600">
                  {assessment.end_time || "-"}
                </p>
              </div>

              <div>
                <b>Shuffle Questions</b>
                <p className="text-gray-600">
                  {assessment.shuffle ? "Yes" : "No"}
                </p>
              </div>

              <div>
                <b>Negative Marks</b>
                <p className="text-gray-600">
                  {assessment.has_negative ? "Yes" : "No"}
                </p>
              </div>
              <div>
                <b>Difficulty level</b>
                <p className="text-gray-600">
                  {assessment.difficulty_level}
                </p>
              </div>

              {assessment.has_negative && (
                <div>
                  <b>Negative Mark Value</b>
                  <p className="text-gray-600">
                    {assessment.negative_marks}
                  </p>
                </div>
              )}
            </div>
          )}

          {/* ================= QUESTIONS + CHOICES TAB ================= */}
          {activeTab === "questions" && (
            <>
              {loading && (
                <p className="text-sm text-gray-400">
                  Loading questions...
                </p>
              )}

              {!loading && questions.length === 0 && (
                <p className="text-sm text-gray-400">
                  No questions added for this assessment
                </p>
              )}

              {!loading &&
                questions.map((q, index) => (
                  <div
                    key={q.id}
                    className="border rounded-lg p-4 mb-4"
                  >
                    {/* Question */}
                    <p className="font-medium mb-2">
                      {index + 1}. {q.question_text}
                    </p>

                    <p className="text-xs text-gray-500 mb-3">
                      Type: {q.type}
                    </p>

                    {/* Choices */}
                    <div className="space-y-2">
                      {q.choices.map((c, i) => (
                        <div
                          key={i}
                          className={`border p-2 rounded flex justify-between items-center
                            ${c.is_correct
                              ? "bg-green-50 border-green-400"
                              : "bg-gray-50"
                            }`}
                        >
                          <span>{c.option}</span>
                          {c.is_correct && (
                            <span className="text-green-600 font-semibold">
                              ✔ Correct
                            </span>
                          )}
                        </div>
                      ))}
                    </div>
                  </div>
                ))}
            </>
          )}
          {activeTab === "batch" && (
            <div className="space-y-6">
              {batches.map((batch) => (
                <div
                  key={batch.id}
                  className="border rounded-lg p-4"
                >
                  <h3 className="font-semibold text-lg mb-3">
                    {batch.name}
                  </h3>

                  <div className="grid grid-cols-2 gap-4 text-sm">

                    <div>
                      <b>Publish Date</b>
                      <p>{batch.publish_date}</p>
                    </div>

                    <div>
                      <b>Capacity</b>
                      <p>{batch.capacity ?? "-"}</p>
                    </div>

                    <div>
                      <b>Start Time</b>
                      <p>{batch.start_time}</p>
                    </div>

                    <div>
                      <b>End Time</b>
                      <p>{batch.end_time}</p>
                    </div>

                    <div>
                      <b>Total Users</b>
                      <p>
                        {batchUsers[batch.id]?.length || 0}
                      </p>
                    </div>
                  </div>

                  <div className="mt-5">

                    <h4 className="font-medium mb-2">
                      Users
                    </h4>

                    {batchUsers[batch.id]?.length ? (
                      <table className="w-full border text-sm">

                        <thead className="bg-gray-100">
                          <tr>
                            <th className="border p-2">
                              Name
                            </th>
                            <th className="border p-2">
                              Email
                            </th>
                          </tr>
                        </thead>

                        <tbody>
                          {batchUsers[batch.id].map((user) => (
                            <tr key={user.id}>
                              <td className="border p-2">
                                {user.name}
                              </td>

                              <td className="border p-2">
                                {user.email}
                              </td>
                            </tr>
                          ))}
                        </tbody>

                      </table>
                    ) : (
                      <p className="text-gray-400">
                        No users assigned.
                      </p>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
