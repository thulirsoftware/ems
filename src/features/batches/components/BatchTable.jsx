import { Pencil, Trash2, Users } from "lucide-react";
import { PageLoader } from "../../../components/common/Spinner";
import EmptyState from "../../../components/common/EmptyState";

export default function BatchTable({
    batches = [],
    loading = false,
    assessmentMap,
    onEdit,
    onDelete,
    onUsers,
}) {
    if (loading) {
        return (
            <div className="bg-white border rounded-lg">
                <PageLoader label="Loading batches..." />
            </div>
        );
    }

    return (
        <div className="bg-white border rounded-lg overflow-x-auto">
            <table className="w-full border-collapse">
                <thead>
                    <tr className="bg-gray-100 border-b">
                        <th className="px-4 py-3 text-left text-sm font-semibold">
                            Batch Name
                        </th>
                        <th className="px-4 py-3 text-left text-sm font-semibold">
                            Assessment
                        </th>
                        <th className="px-4 py-3 text-left text-sm font-semibold">
                            Publish Date
                        </th>
                        <th className="px-4 py-3 text-left text-sm font-semibold">
                            Time
                        </th>
                        <th className="px-4 py-3 text-center text-sm font-semibold">
                            Capacity
                        </th>
                        <th className="px-4 py-3 text-center text-sm font-semibold">
                            Actions
                        </th>
                    </tr>
                </thead>

                <tbody>
                    {batches.length === 0 ? (
                        <tr>
                            <td colSpan="6">
                                <EmptyState title="No batches found" />
                            </td>
                        </tr>
                    ) : (
                        batches.map((batch) => (
                            <tr
                                key={batch.id}
                                className="border-b hover:bg-gray-50"
                            >
                                <td className="px-4 py-3">
                                    {batch.name}
                                </td>

                                <td className="px-4 py-3">
                                    {assessmentMap[batch.assessment_id] || "-"}
                                </td>

                                <td className="px-4 py-3">
                                    {batch.publish_date}
                                </td>

                                <td className="px-4 py-3">
                                    {batch.start_time} - {batch.end_time}
                                </td>

                                <td className="px-4 py-3 text-center">
                                    {batch.capacity}
                                </td>

                                <td className="px-4 py-3">
                                    <div className="flex items-center justify-center gap-2">
                                        <button
                                            onClick={() => onEdit(batch)}
                                            className="p-2 rounded bg-blue-100 text-blue-600 hover:bg-blue-200"
                                            title="Edit"
                                        >
                                            <Pencil size={16} />
                                        </button>

                                        <button
                                            onClick={() => onUsers(batch)}
                                            className="p-2 rounded bg-green-100 text-green-600 hover:bg-green-200"
                                            title="Manage Users"
                                        >
                                            <Users size={16} />
                                        </button>

                                        <button
                                            onClick={() => onDelete(batch)}
                                            className="p-2 rounded bg-red-100 text-red-600 hover:bg-red-200"
                                            title="Delete"
                                        >
                                            <Trash2 size={16} />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))
                    )}
                </tbody>
            </table>
        </div>
    );
}