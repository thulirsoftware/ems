import { useEffect, useState } from "react";
import { toast } from "sonner";
import AssessmentService from "../../../services/assesment.service";

export default function AssignAssessmentModal({
    assessmentId,
    onClose,
}) {
    const [users, setUsers] = useState([]);
    const [selectedUsers, setSelectedUsers] = useState([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        loadUsers();
    }, [assessmentId]);

    const loadUsers = async () => {
        const data = await AssessmentService.getUsersWithAssignmentStatus(assessmentId);
        setUsers(data);

        // auto-select already assigned users
        const alreadyAssigned = data
            .filter(u => u.assigned)
            .map(u => u.user_id);

        setSelectedUsers(alreadyAssigned);
    };


    const toggleUser = (id) => {
        setSelectedUsers((prev) =>
            prev.includes(id)
                ? prev.filter((u) => u !== id)
                : [...prev, id]
        );
    };

    const assign = async () => {

        const newUsers = users
            .filter(u => !u.assigned && selectedUsers.includes(u.user_id))
            .map(u => u.user_id);

        if (newUsers.length === 0) {
            toast.error("No new users selected");
            return;
        }

        try {

            setLoading(true);

            const res = await AssessmentService.assignAssessment({
                assessment_id: assessmentId,
                user_ids: newUsers,
            });

            const data = res.data ?? res;

            let message = "Assessment assigned successfully";

            if (data.blocked_due_to_conflict?.length) {

                const conflictNames = data.blocked_due_to_conflict
                    .map(c => {
                        const user = users.find(u => u.user_id === c.user_id);
                        return `${user?.name} (conflicts with "${c.conflicting_assessment_title}")`;
                    })
                    .join("\n");

                message += `\n\nThe following users could not be assigned because they already have another exam scheduled:\n\n${conflictNames}`;

            }

            toast.success(message);

            onClose();

        } catch (err) {

            console.error(err);
            toast.error("Assignment failed");

        } finally {

            setLoading(false);

        }

    };


    return (
        <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center">
            <div className="bg-white w-[500px] rounded-xl shadow-xl">

                {/* Header */}
                <div className="border-b px-6 py-4 flex justify-between">
                    <h2 className="font-semibold">Assign Assessment</h2>
                    <button onClick={onClose}>✕</button>
                </div>

                {/* Body */}
                <div className="p-6 max-h-[60vh] overflow-y-auto space-y-3">
                    {users.map((u) => (
                        <label
                            key={u.user_id}
                            className={`flex items-center gap-3 border p-3 rounded
      ${u.assigned ? "bg-gray-100 cursor-not-allowed" : "cursor-pointer"}
    `}
                        >
                            <input
                                type="checkbox"
                                checked={selectedUsers.includes(u.user_id)}
                                disabled={u.assigned}
                                onChange={() => toggleUser(u.user_id)}
                            />

                            <div className="flex-1">
                                <p className="font-medium">{u.name}</p>

                                {u.assigned && (
                                    <p className="text-xs text-green-600 font-semibold">
                                        Already Assigned
                                    </p>
                                )}
                            </div>
                        </label>
                    ))}


                    {users.length === 0 && (
                        <p className="text-sm text-gray-400">No users found</p>
                    )}
                </div>

                {/* Footer */}
                <div className="border-t px-6 py-4 flex justify-end gap-3">
                    <button
                        onClick={onClose}
                        className="border px-4 py-2 rounded"
                    >
                        Cancel
                    </button>

                    <button
                        disabled={loading}
                        onClick={assign}
                        className="bg-red-600 text-white px-4 py-2 rounded disabled:opacity-50"
                    >
                        {loading ? "Assigning..." : "Assign"}
                    </button>
                </div>
            </div>
        </div>
    );
}
