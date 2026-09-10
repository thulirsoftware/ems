import { useEffect, useState } from "react";
import { toast } from "sonner";
import AssessmentService from "../../../services/assesment.service";
import { useConfirm } from "../../../hooks/useConfirm";

// Advanced, per-choice editing via the granular choice endpoints — unlike
// the question form's "replace all 4 at once" flow, this allows a question
// to end up with any number of choices (not just exactly 4).
export default function ManageChoicesModal({ question, onClose, onChanged }) {
  const [choices, setChoices] = useState([]);
  const [loading, setLoading] = useState(true);
  const [newOption, setNewOption] = useState("");
  const [adding, setAdding] = useState(false);
  const [confirm, confirmDialog] = useConfirm();

  const load = async () => {
    setLoading(true);
    try {
      const data = await AssessmentService.getChoices(question.id);
      setChoices(data || []);
    } catch (err) {
      toast.error(err.response?.data?.message || "Failed to load choices.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [question.id]);

  const updateLocal = (id, patch) => {
    setChoices((prev) =>
      prev.map((c) => (c.id === id ? { ...c, ...patch } : c))
    );
  };

  const saveChoice = async (choice) => {
    try {
      const updated = await AssessmentService.updateChoice(choice.id, {
        option: choice.option,
        is_correct: choice.is_correct,
      });
      updateLocal(choice.id, updated);
      toast.success("Choice updated");
      onChanged?.();
    } catch (err) {
      toast.error(err.response?.data?.message || "Failed to update choice.");
    }
  };

  const deleteChoice = async (choice) => {
    const ok = await confirm({
      title: "Delete this choice?",
      description: "This action cannot be undone.",
      confirmLabel: "Delete",
      variant: "danger",
    });

    if (!ok) return;

    try {
      await AssessmentService.deleteChoice(choice.id);
      setChoices((prev) => prev.filter((c) => c.id !== choice.id));
      toast.success("Choice deleted");
      onChanged?.();
    } catch (err) {
      toast.error(err.response?.data?.message || "Failed to delete choice.");
    }
  };

  const addChoice = async () => {
    if (!newOption.trim()) {
      toast.error("Option text is required");
      return;
    }

    try {
      setAdding(true);

      const created = await AssessmentService.createChoice(question.id, {
        option: newOption.trim(),
        is_correct: false,
        order: choices.length + 1,
      });

      setChoices((prev) => [...prev, created]);
      setNewOption("");
      toast.success("Choice added");
      onChanged?.();
    } catch (err) {
      toast.error(err.response?.data?.message || "Failed to add choice.");
    } finally {
      setAdding(false);
    }
  };

  return (
    <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center">
      <div className="bg-white w-[520px] max-h-[80vh] rounded-xl shadow-xl flex flex-col">

        <div className="border-b px-6 py-4 flex justify-between items-center">
          <div>
            <h2 className="font-semibold">Manage Choices</h2>
            <p className="text-xs text-gray-500 mt-0.5">{question.question_text}</p>
          </div>
          <button onClick={onClose}>✕</button>
        </div>

        <div className="p-6 overflow-y-auto flex-1 space-y-3">
          {loading ? (
            <p className="text-sm text-gray-400">Loading choices...</p>
          ) : choices.length === 0 ? (
            <p className="text-sm text-gray-400">No choices yet</p>
          ) : (
            choices.map((c) => (
              <div key={c.id} className="flex items-center gap-2 border p-2 rounded">
                <input
                  type="checkbox"
                  checked={!!c.is_correct}
                  title="Correct"
                  onChange={(e) =>
                    updateLocal(c.id, { is_correct: e.target.checked })
                  }
                />

                <input
                  type="text"
                  value={c.option}
                  onChange={(e) => updateLocal(c.id, { option: e.target.value })}
                  className="flex-1 border rounded px-2 py-1 text-sm"
                />

                <button
                  onClick={() => saveChoice(c)}
                  className="text-blue-600 text-sm"
                >
                  Save
                </button>

                <button
                  onClick={() => deleteChoice(c)}
                  className="text-red-600 text-sm"
                >
                  Delete
                </button>
              </div>
            ))
          )}

          <div className="flex items-center gap-2 border-t pt-4">
            <input
              type="text"
              placeholder="New option text"
              value={newOption}
              onChange={(e) => setNewOption(e.target.value)}
              className="flex-1 border rounded px-2 py-1 text-sm"
            />

            <button
              onClick={addChoice}
              disabled={adding}
              className="px-3 py-1 bg-green-600 text-white rounded text-sm disabled:opacity-50"
            >
              {adding ? "Adding..." : "Add Choice"}
            </button>
          </div>
        </div>

        <div className="border-t px-6 py-4 flex justify-end">
          <button onClick={onClose} className="px-4 py-2 border rounded-md">
            Close
          </button>
        </div>

        {confirmDialog}
      </div>
    </div>
  );
}
