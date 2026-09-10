import { useEffect, useState } from "react";
import { toast } from "sonner";
import AssessmentService from "../../../services/assesment.service";

const MODES = [
  { value: "previous", label: "Previous batch's users" },
  { value: "filtered", label: "Failed / absent users" },
  { value: "manual", label: "Select users manually" },
];

export default function ReExamModal({ assessment, onClose, onSuccess }) {
  const isBatchWise = assessment.scheduling_type === "batch_wise";

  const [mode, setMode] = useState("previous");
  const [users, setUsers] = useState([]);
  const [selectedUsers, setSelectedUsers] = useState([]);
  const [loadingUsers, setLoadingUsers] = useState(false);
  const [loading, setLoading] = useState(false);

  const [form, setForm] = useState({
    filter: "failed",
    passing_percentage: "40",
    publish_date: "",
    start_time: "",
    end_time: "",
  });

  useEffect(() => {
    if (mode !== "manual") return;

    setLoadingUsers(true);

    AssessmentService.getUsersWithAssignmentStatus(assessment.id)
      .then((data) => setUsers(data || []))
      .catch(() => toast.error("Failed to load users"))
      .finally(() => setLoadingUsers(false));
  }, [mode, assessment.id]);

  const toggleUser = (id) => {
    setSelectedUsers((prev) =>
      prev.includes(id) ? prev.filter((u) => u !== id) : [...prev, id]
    );
  };

  const buildSchedule = () => ({
    publish_date: form.publish_date,
    start_time: form.start_time ? `${form.start_time}:00` : "",
    end_time: form.end_time ? `${form.end_time}:00` : "",
  });

  const validateSchedule = () => {
    if (!form.publish_date || !form.start_time || !form.end_time) {
      toast.error("Publish date, start time and end time are required");
      return false;
    }

    if (form.end_time <= form.start_time) {
      toast.error("End time must be after start time");
      return false;
    }

    return true;
  };

  const submit = async () => {

    if (!validateSchedule()) return;

    try {
      setLoading(true);

      if (mode === "filtered") {

        if (
          ["failed", "both"].includes(form.filter) &&
          form.passing_percentage === ""
        ) {
          toast.error("Passing percentage is required for this filter");
          return;
        }

        const res = await AssessmentService.createFilteredReExam({
          assessment_id: assessment.id,
          filter: form.filter,
          passing_percentage: form.passing_percentage
            ? Number(form.passing_percentage)
            : undefined,
          source_batch_id: isBatchWise ? assessment.batch_id : undefined,
          ...buildSchedule(),
        });

        toast.success(
          `${res.message} — ${res.assigned_users_count} user(s) assigned.`
        );

      } else {

        const useprevious = mode === "previous";

        if (!useprevious && selectedUsers.length === 0) {
          toast.error("Select at least one user");
          return;
        }

        const res = await AssessmentService.createReExam({
          assessment_id: assessment.id,
          use_previous_users: useprevious,
          source_batch_id: isBatchWise ? assessment.batch_id : undefined,
          user_ids: useprevious ? undefined : selectedUsers,
          ...buildSchedule(),
        });

        toast.success(
          `${res.message} — ${res.assigned_users_count} user(s) assigned.`
        );
      }

      onSuccess();

    } catch (err) {
      toast.error(
        err?.response?.data?.message || "Failed to create re-exam."
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center">
      <div className="bg-white w-[560px] max-h-[85vh] rounded-xl shadow-xl flex flex-col">

        {/* Header */}
        <div className="border-b px-6 py-4 flex justify-between items-center">
          <div>
            <h2 className="font-semibold">Create Re-Exam</h2>
            <p className="text-xs text-gray-500 mt-0.5">{assessment.title}</p>
          </div>
          <button onClick={onClose}>✕</button>
        </div>

        <div className="p-6 overflow-y-auto flex-1 space-y-5">

          {/* Mode selector */}
          <div className="flex flex-col gap-2">
            <label className="text-xs text-gray-600">Who should retake it?</label>
            <div className="flex flex-col gap-2">
              {MODES.map((m) => (
                <label key={m.value} className="flex items-center gap-2 text-sm">
                  <input
                    type="radio"
                    name="re-exam-mode"
                    checked={mode === m.value}
                    onChange={() => setMode(m.value)}
                  />
                  {m.label}
                </label>
              ))}
            </div>
          </div>

          {/* Filtered options */}
          {mode === "filtered" && (
            <div className="grid grid-cols-2 gap-4">
              <div className="flex flex-col gap-1">
                <label className="text-xs text-gray-600">Filter</label>
                <select
                  className="border p-2 rounded text-sm"
                  value={form.filter}
                  onChange={(e) => setForm({ ...form, filter: e.target.value })}
                >
                  <option value="failed">Failed</option>
                  <option value="absent">Absent</option>
                  <option value="both">Failed or Absent</option>
                </select>
              </div>

              {["failed", "both"].includes(form.filter) && (
                <div className="flex flex-col gap-1">
                  <label className="text-xs text-gray-600">Passing %</label>
                  <input
                    type="number"
                    min="0"
                    max="100"
                    className="border p-2 rounded text-sm"
                    value={form.passing_percentage}
                    onChange={(e) =>
                      setForm({ ...form, passing_percentage: e.target.value })
                    }
                  />
                </div>
              )}
            </div>
          )}

          {/* Manual user picker */}
          {mode === "manual" && (
            <div className="flex flex-col gap-2">
              <label className="text-xs text-gray-600">
                Select users ({selectedUsers.length} selected)
              </label>

              <div className="border rounded max-h-48 overflow-y-auto">
                {loadingUsers ? (
                  <p className="text-sm text-gray-400 p-3">Loading users...</p>
                ) : users.length === 0 ? (
                  <p className="text-sm text-gray-400 p-3">No users found</p>
                ) : (
                  users.map((u) => (
                    <label
                      key={u.user_id}
                      className="flex items-center gap-2 px-3 py-2 border-b last:border-none hover:bg-gray-50 text-sm"
                    >
                      <input
                        type="checkbox"
                        checked={selectedUsers.includes(u.user_id)}
                        onChange={() => toggleUser(u.user_id)}
                      />
                      {u.name}
                    </label>
                  ))
                )}
              </div>
            </div>
          )}

          {/* Schedule */}
          <div className="grid grid-cols-3 gap-4">
            <div className="flex flex-col gap-1">
              <label className="text-xs text-gray-600">Publish Date</label>
              <input
                type="date"
                className="border p-2 rounded text-sm"
                value={form.publish_date}
                onChange={(e) => setForm({ ...form, publish_date: e.target.value })}
              />
            </div>

            <div className="flex flex-col gap-1">
              <label className="text-xs text-gray-600">Start Time</label>
              <input
                type="time"
                className="border p-2 rounded text-sm"
                value={form.start_time}
                onChange={(e) => setForm({ ...form, start_time: e.target.value })}
              />
            </div>

            <div className="flex flex-col gap-1">
              <label className="text-xs text-gray-600">End Time</label>
              <input
                type="time"
                className="border p-2 rounded text-sm"
                value={form.end_time}
                onChange={(e) => setForm({ ...form, end_time: e.target.value })}
              />
            </div>
          </div>

          <p className="text-xs text-gray-400">
            A re-exam can only be created once the source batch/schedule has finished.
          </p>

        </div>

        {/* Footer */}
        <div className="border-t px-6 py-4 flex justify-end gap-3">
          <button onClick={onClose} className="px-4 py-2 border rounded-md">
            Cancel
          </button>

          <button
            disabled={loading}
            onClick={submit}
            className="px-5 py-2 rounded-md bg-purple-600 hover:bg-purple-700 text-white disabled:opacity-50"
          >
            {loading ? "Creating..." : "Create Re-Exam"}
          </button>
        </div>

      </div>
    </div>
  );
}
