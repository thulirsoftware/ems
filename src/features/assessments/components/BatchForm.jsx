import { useEffect, useState } from "react";
import { toast } from "sonner";
import BatchService from "../../../services/batch.service";

export default function BatchForm({
    assessmentId,
    batchId,
    isEdit = false,
    onSuccess,
}) {
    const [loading, setLoading] = useState(false);

    const [errors, setErrors] = useState({});

    const [form, setForm] = useState({
        name: "",
        publish_date: "",
        start_time: "",
        end_time: "",
        capacity: 1,
    });
    useEffect(() => {
        if (!isEdit || !batchId) return;

        const loadBatch = async () => {
            try {
                const batch = await BatchService.getBatchById(batchId);

                setForm({
                    name: batch.name || "",
                    publish_date: batch.publish_date || "",
                    start_time: batch.start_time?.slice(0, 5) || "",
                    end_time: batch.end_time?.slice(0, 5) || "",
                    capacity: batch.capacity || 1,
                });
            } catch (err) {
                console.error(err);
            }
        };

        loadBatch();
    }, [batchId, isEdit]);

    const handleChange = (e) => {
        const { name, value } = e.target;

        setForm((prev) => ({
            ...prev,
            [name]: value,
        }));

        setErrors((prev) => ({
            ...prev,
            [name]: "",
        }));
    };

    const validate = () => {
        const err = {};

        if (!form.name.trim()) {
            err.name = "Batch name is required.";
        }

        if (!form.publish_date) {
            err.publish_date = "Publish date is required.";
        }

        if (!form.start_time) {
            err.start_time = "Start time is required.";
        }

        if (!form.end_time) {
            err.end_time = "End time is required.";
        }

        if (form.start_time && form.end_time) {
            const start = new Date(`2000-01-01 ${form.start_time}`);
            const end = new Date(`2000-01-01 ${form.end_time}`);

            if (end <= start) {
                err.end_time = "End time must be greater than start time.";
            }
        }

        if (!form.capacity || Number(form.capacity) < 1) {
            err.capacity = "Capacity should be at least 1.";
        }

        setErrors(err);

        return Object.keys(err).length === 0;
    };

    const saveBatch = async () => {
        if (!validate()) return;

        setLoading(true);

        try {
            const payload = {
                assessment_id: assessmentId,
                name: form.name,
                publish_date: form.publish_date,
                start_time: `${form.start_time}:00`,
                end_time: `${form.end_time}:00`,
                capacity: Number(form.capacity),
            };

            let res;

            if (isEdit && batchId) {
                res = await BatchService.updateBatch(batchId, payload);
            } else {
                res = await BatchService.createBatch(payload);
            }

            if (onSuccess) {
                onSuccess(res);
            }
        } catch (err) {
            if (err.response?.status === 422) {
                const backendErrors = {};

                Object.entries(err.response.data.errors || {}).forEach(
                    ([key, value]) => {
                        backendErrors[key] = value[0];
                    }
                );

                setErrors(backendErrors);

                if (err.response.data.message) {
                    toast.error(err.response.data.message);
                }
            } else {
                toast.error(
                    err.response?.data?.message ||
                    "Failed to create batch."
                );
            }
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="space-y-5">
            <div className="grid grid-cols-2 gap-4">
                {/* Batch Name */}
                <div>
                    <label className="block text-sm mb-1">
                        Batch Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        value={form.name}
                        onChange={handleChange}
                        placeholder="Enter batch name"
                        className="w-full border rounded-lg p-2"
                    />

                    {errors.name && (
                        <p className="text-red-500 text-xs mt-1">
                            {errors.name}
                        </p>
                    )}
                </div>

                {/* Capacity */}
                <div>
                    <label className="block text-sm mb-1">
                        Capacity
                    </label>

                    <input
                        type="number"
                        min="1"
                        name="capacity"
                        value={form.capacity}
                        onChange={handleChange}
                        className="w-full border rounded-lg p-2"
                    />

                    {errors.capacity && (
                        <p className="text-red-500 text-xs mt-1">
                            {errors.capacity}
                        </p>
                    )}
                </div>

                {/* Publish Date */}
                <div>
                    <label className="block text-sm mb-1">
                        Publish Date
                    </label>

                    <input
                        type="date"
                        name="publish_date"
                        value={form.publish_date}
                        onChange={handleChange}
                        className="w-full border rounded-lg p-2"
                    />

                    {errors.publish_date && (
                        <p className="text-red-500 text-xs mt-1">
                            {errors.publish_date}
                        </p>
                    )}
                </div>

                {/* Start Time */}
                <div>
                    <label className="block text-sm mb-1">
                        Start Time
                    </label>

                    <input
                        type="time"
                        name="start_time"
                        value={form.start_time}
                        onChange={handleChange}
                        className="w-full border rounded-lg p-2"
                    />

                    {errors.start_time && (
                        <p className="text-red-500 text-xs mt-1">
                            {errors.start_time}
                        </p>
                    )}
                </div>

                {/* End Time */}
                <div>
                    <label className="block text-sm mb-1">
                        End Time
                    </label>

                    <input
                        type="time"
                        name="end_time"
                        value={form.end_time}
                        onChange={handleChange}
                        className="w-full border rounded-lg p-2"
                    />

                    {errors.end_time && (
                        <p className="text-red-500 text-xs mt-1">
                            {errors.end_time}
                        </p>
                    )}
                </div>
            </div>

            <div className="flex justify-end">
                <button
                    onClick={saveBatch}
                    disabled={loading}
                    className="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg disabled:opacity-50"
                >
                    {loading ? "Saving..." : "Save & Next"}
                </button>
            </div>
        </div>
    );
}