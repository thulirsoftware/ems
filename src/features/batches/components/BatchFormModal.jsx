import { useEffect, useState } from "react";
import { toast } from "sonner";
import BatchService from "../../../services/batch.service";

export default function BatchFormModal({
    open,
    onClose,
    onSuccess,
    batch = null,
    assessments = [],
}) {
    const [loading, setLoading] = useState(false);

    const [form, setForm] = useState({
        assessment_id: "",
        name: "",
        publish_date: "",
        start_time: "",
        end_time: "",
        capacity: "",
    });

    useEffect(() => {
        if (batch) {
            setForm({
                assessment_id: batch.assessment_id || "",
                name: batch.name || "",
                publish_date: batch.publish_date || "",
                start_time: batch.start_time || "",
                end_time: batch.end_time || "",
                capacity: batch.capacity || "",
            });
        } else {
            setForm({
                assessment_id: "",
                name: "",
                publish_date: "",
                start_time: "",
                end_time: "",
                capacity: "",
            });
        }
    }, [batch]);

    if (!open) return null;

    const handleChange = (e) => {
        setForm({
            ...form,
            [e.target.name]: e.target.value,
        });
    };

    // <input type="time" step="1"> should yield "HH:mm:ss", but browser
    // support for the seconds sub-field is inconsistent — pad explicitly so
    // the backend's date_format:H:i:s validation never fails on that alone.
    const withSeconds = (value) => {
        if (!value) return value;
        return value.length === 5 ? `${value}:00` : value;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        const payload = {
            ...form,
            capacity: Number(form.capacity),
            start_time: withSeconds(form.start_time),
            end_time: withSeconds(form.end_time),
        };

        try {
            setLoading(true);

            if (batch) {
                await BatchService.updateBatch(batch.id, payload);
            } else {
                await BatchService.createBatch(payload);
            }

            toast.success(
                batch
                    ? "Batch updated successfully."
                    : "Batch created successfully."
            );

            onSuccess();
            onClose();
        } catch (err) {
            toast.error(
                err.response?.data?.message ||
                    "Something went wrong."
            );
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
            <div className="bg-white rounded-lg w-full max-w-2xl">

                <div className="border-b px-6 py-4">
                    <h2 className="text-xl font-semibold">
                        {batch ? "Edit Batch" : "Create Batch"}
                    </h2>
                </div>

                <form onSubmit={handleSubmit}>

                    <div className="p-6 grid grid-cols-2 gap-4">

                        <div className="col-span-2">
                            <label className="block mb-2 text-sm font-medium">
                                Assessment
                            </label>

                            <select
                                name="assessment_id"
                                value={form.assessment_id}
                                onChange={handleChange}
                                required
                                disabled={!!batch}
                                className="w-full border rounded-md px-3 py-2"
                            >
                                <option value="">
                                    Select Assessment
                                </option>

                                {Array.from(
                                    new Map(
                                        assessments
                                            .filter((item) => item.scheduling_type === "batch_wise")
                                            .map((item) => [item.id, item])
                                    ).values()
                                ).map((item) => (
                                        <option
                                            key={item.id}
                                            value={item.id}
                                        >
                                            {item.title || item.name}
                                        </option>
                                    ))}
                            </select>
                        </div>

                        <div className="col-span-2">
                            <label className="block mb-2 text-sm font-medium">
                                Batch Name
                            </label>

                            <input
                                type="text"
                                name="name"
                                value={form.name}
                                onChange={handleChange}
                                required
                                className="w-full border rounded-md px-3 py-2"
                            />
                        </div>

                        <div>
                            <label className="block mb-2 text-sm font-medium">
                                Publish Date
                            </label>

                            <input
                                type="date"
                                name="publish_date"
                                value={form.publish_date}
                                onChange={handleChange}
                                required
                                className="w-full border rounded-md px-3 py-2"
                            />
                        </div>

                        <div>
                            <label className="block mb-2 text-sm font-medium">
                                Capacity
                            </label>

                            <input
                                type="number"
                                name="capacity"
                                min="1"
                                value={form.capacity}
                                onChange={handleChange}
                                required
                                className="w-full border rounded-md px-3 py-2"
                            />
                        </div>

                        <div>
                            <label className="block mb-2 text-sm font-medium">
                                Start Time
                            </label>

                            <input
                                type="time"
                                name="start_time"
                                step="1"
                                value={form.start_time}
                                onChange={handleChange}
                                required
                                className="w-full border rounded-md px-3 py-2"
                            />
                        </div>

                        <div>
                            <label className="block mb-2 text-sm font-medium">
                                End Time
                            </label>

                            <input
                                type="time"
                                name="end_time"
                                step="1"
                                value={form.end_time}
                                onChange={handleChange}
                                required
                                className="w-full border rounded-md px-3 py-2"
                            />
                        </div>

                    </div>

                    <div className="border-t px-6 py-4 flex justify-end gap-3">

                        <button
                            type="button"
                            onClick={onClose}
                            className="px-5 py-2 border rounded-md"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            disabled={loading}
                            className="px-5 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700"
                        >
                            {loading
                                ? "Saving..."
                                : batch
                                ? "Update Batch"
                                : "Create Batch"}
                        </button>

                    </div>

                </form>
            </div>
        </div>
    );
}