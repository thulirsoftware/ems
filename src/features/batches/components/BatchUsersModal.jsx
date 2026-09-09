import { useEffect, useMemo, useState } from "react";
import { Search, X, Users } from "lucide-react";
import { toast } from "sonner";

import BatchService from "../../../services/batch.service";
import UserService from "../../../services/user.service";

export default function BatchUsersModal({
    open,
    onClose,
    batch,
}) {
    const [loading, setLoading] = useState(false);

    const [search, setSearch] = useState("");

    const [users, setUsers] = useState([]);
    const [batchUsers, setBatchUsers] = useState([]);
    const [selectedAvailable, setSelectedAvailable] = useState([]);
    const [selectedAssigned, setSelectedAssigned] = useState([]);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (!open || !batch) return;

        loadData();
        setSelectedAvailable([]);
        setSelectedAssigned([]);
    }, [open, batch]);

    const loadData = async () => {
        try {
            setLoading(true);

            const [allUsers, assignedUsers] =
                await Promise.all([
                    UserService.UserList(),
                    BatchService.getBatchUsers(batch.id),
                ]);

            setUsers(Array.isArray(allUsers) ? allUsers : []);
            setBatchUsers(
                Array.isArray(assignedUsers)
                    ? assignedUsers
                    : []
            );
        } catch (err) {
            console.error(err);
            toast.error("Failed to load users.");
        } finally {
            setLoading(false);
        }
    };
    const toggleAvailable = (id) => {
        setSelectedAvailable((prev) =>
            prev.includes(id)
                ? prev.filter((x) => x !== id)
                : [...prev, id]
        );
    };

    const toggleAssigned = (id) => {
        setSelectedAssigned((prev) =>
            prev.includes(id)
                ? prev.filter((x) => x !== id)
                : [...prev, id]
        );
    };
    const selectAllAvailable = () => {
        setSelectedAvailable(filteredAvailable.map((u) => u.id));
    };

    const clearAvailable = () => {
        setSelectedAvailable([]);
    };

    const selectAllAssigned = () => {
        setSelectedAssigned(filteredAssigned.map((u) => u.id));
    };

    const clearAssigned = () => {
        setSelectedAssigned([]);
    };
    const assignUsers = async () => {
        if (selectedAvailable.length === 0) return;

        try {
            setSaving(true);

            const res = await BatchService.addUsersToBatch(batch.id, {
                user_ids: selectedAvailable,
            });

            if (res?.blocked_due_to_conflict?.length) {
                const names = res.blocked_due_to_conflict
                    .map((c) => {
                        const user = users.find((u) => u.id === c.user_id);
                        return `${user?.name || `User #${c.user_id}`} (conflicts with "${c.conflicting_assessment_title}")`;
                    })
                    .join("\n");

                toast.error(
                    `${res.assigned?.length || 0} user(s) assigned.\n\nThe following users could not be assigned because they already have another exam scheduled at the same time:\n\n${names}`
                );
            }

            await loadData();
        } catch (err) {
            toast.error(
                err.response?.data?.message ||
                "Unable to assign users."
            );
        } finally {
            setSaving(false);
        }
    };
    const removeUsers = async () => {
        if (selectedAssigned.length === 0) return;

        try {
            setSaving(true);

            await BatchService.removeUsersFromBatch(batch.id, {
                user_ids: selectedAssigned,
            });

            await loadData();
        } catch (err) {
            toast.error(
                err.response?.data?.message ||
                "Unable to remove users."
            );
        } finally {
            setSaving(false);
        }
    };

    const assignedIds = useMemo(
        () => new Set(batchUsers.map((u) => u.id)),
        [batchUsers]
    );

    const availableUsers = useMemo(() => {
        return users.filter((u) => !assignedIds.has(u.id));
    }, [users, assignedIds]);

    const filteredAvailable = availableUsers.filter((u) => {
        const keyword = search.toLowerCase();

        return (
            (u.name || "")
                .toLowerCase()
                .includes(keyword) ||
            (u.email || "")
                .toLowerCase()
                .includes(keyword)
        );
    });

    const filteredAssigned = batchUsers.filter((u) => {
        const keyword = search.toLowerCase();

        return (
            (u.name || "")
                .toLowerCase()
                .includes(keyword) ||
            (u.email || "")
                .toLowerCase()
                .includes(keyword)
        );
    });

    if (!open) return null;

    return (
        <div className="fixed inset-0 z-50 bg-black/40 flex items-center justify-center">

            <div className="
bg-white
rounded-xl
shadow-xl
w-[95vw]
sm:w-[92vw]
lg:w-[90vw]
xl:w-[1400px]
max-h-[92vh]
flex
flex-col
overflow-hidden
">

                {/* Header */}

                <div className="border-b px-6 py-4 flex justify-between items-center">

                    <div>

                        <h2 className="text-xl font-semibold">
                            Manage Batch Users
                        </h2>

                        <p className="text-sm text-gray-500 mt-1">
                            {batch?.name}
                        </p>

                    </div>

                    <button
                        onClick={onClose}
                        className="p-2 rounded hover:bg-gray-100"
                    >
                        <X size={18} />
                    </button>

                </div>

                {/* Search */}

                <div className="border-b p-4">

                    <div className="relative">

                        <Search
                            className="absolute left-3 top-3 text-gray-400"
                            size={18}
                        />

                        <input
                            type="text"
                            placeholder="Search users..."
                            value={search}
                            onChange={(e) =>
                                setSearch(e.target.value)
                            }
                            className="w-full border rounded-lg pl-10 pr-4 py-2"
                        />

                    </div>

                </div>

                {/* Body */}

                <div
                    className="
    flex-1
    overflow-hidden
    grid
    grid-cols-1
    lg:grid-cols-2
    gap-5
    p-5
"
                >

                    {/* Available */}

                    <div className="flex flex-col min-h-0">

                        <div className="flex justify-between items-center mb-3">

                            <h3 className="font-semibold">
                                Available Users
                                <span className="ml-2 text-sm text-gray-500">
                                    ({filteredAvailable.length})
                                </span>
                            </h3>

                            <div className="flex gap-2">

                                <button
                                    onClick={selectAllAvailable}
                                    className="text-xs text-blue-600"
                                >
                                    Select All
                                </button>

                                <button
                                    onClick={clearAvailable}
                                    className="text-xs text-gray-500"
                                >
                                    Clear
                                </button>

                            </div>

                        </div>

                        <div className="
flex-1
border
rounded-lg
overflow-y-auto
">

                            {loading ? (

                                <div className="p-6 text-center text-gray-500">
                                    Loading...
                                </div>

                            ) : filteredAvailable.length === 0 ? (

                                <div className="p-8 text-center text-gray-400">

                                    <Users
                                        size={40}
                                        className="mx-auto mb-3"
                                    />

                                    No available users

                                </div>

                            ) : (

                                filteredAvailable.map((user) => (

                                    <div
                                        key={user.id}
                                        className="border-b px-4 py-3 flex items-center gap-3 hover:bg-gray-50"
                                    >

                                        <input
                                            type="checkbox"
                                            checked={selectedAvailable.includes(user.id)}
                                            onChange={() => toggleAvailable(user.id)}
                                        />

                                        <div>

                                            <div className="font-medium">
                                                {user.name}
                                            </div>

                                            <div className="text-xs text-gray-500">
                                                {user.email}
                                            </div>

                                        </div>

                                    </div>

                                ))

                            )}

                        </div>

                    </div>

                    {/* Assigned */}

                    <div className="flex flex-col min-h-0">

                        <div className="flex justify-between items-center mb-3">

                            <h3 className="font-semibold">
                                Assigned Users
                                <span className="ml-2 text-sm text-gray-500">
                                    ({batchUsers.length}{batch?.capacity ? ` / ${batch.capacity}` : ""})
                                </span>
                            </h3>

                            <div className="flex gap-2">

                                <button
                                    onClick={selectAllAssigned}
                                    className="text-xs text-blue-600"
                                >
                                    Select All
                                </button>

                                <button
                                    onClick={clearAssigned}
                                    className="text-xs text-gray-500"
                                >
                                    Clear
                                </button>

                            </div>

                        </div>

                        <div className="
flex-1
border
rounded-lg
overflow-y-auto
">

                            {loading ? (

                                <div className="p-6 text-center text-gray-500">
                                    Loading...
                                </div>

                            ) : filteredAssigned.length === 0 ? (

                                <div className="p-8 text-center text-gray-400">

                                    <Users
                                        size={40}
                                        className="mx-auto mb-3"
                                    />

                                    No assigned users

                                </div>

                            ) : (

                                filteredAssigned.map((user) => (

                                    <div
                                        key={user.id}
                                        className="border-b px-4 py-3 flex items-center gap-3 hover:bg-gray-50"
                                    >

                                        <input
                                            type="checkbox"
                                            checked={selectedAssigned.includes(user.id)}
                                            onChange={() => toggleAssigned(user.id)}
                                        />

                                        <div>

                                            <div className="font-medium">
                                                {user.name}
                                            </div>

                                            <div className="text-xs text-gray-500">
                                                {user.email}
                                            </div>

                                        </div>

                                    </div>

                                ))

                            )}

                        </div>

                    </div>

                </div>

                {/* Footer */}

                <div className="border-t px-6 py-4 flex justify-between">

                    <button
                        onClick={onClose}
                        className="px-5 py-2 border rounded-lg"
                    >
                        Close
                    </button>

                    <div className="flex items-center gap-3">

                        {batch?.capacity &&
                            batchUsers.length + selectedAvailable.length > batch.capacity && (
                                <span className="text-xs text-red-600">
                                    Exceeds capacity ({batch.capacity})
                                </span>
                            )}

                        <button
                            disabled={
                                saving ||
                                selectedAssigned.length === 0
                            }
                            onClick={removeUsers}
                            className="px-5 py-2 bg-red-600 text-white rounded-lg disabled:opacity-50"
                        >
                            Remove Selected
                        </button>

                        <button
                            disabled={
                                saving ||
                                selectedAvailable.length === 0 ||
                                (batch?.capacity &&
                                    batchUsers.length + selectedAvailable.length > batch.capacity)
                            }
                            onClick={assignUsers}
                            className="px-5 py-2 bg-green-600 text-white rounded-lg disabled:opacity-50"
                        >
                            Assign Selected
                        </button>

                    </div>

                </div>

            </div>

        </div>
    );
}