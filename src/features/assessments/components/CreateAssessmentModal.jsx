import { useEffect, useState } from "react";
import { toast } from "sonner";
import AssessmentService from "../../../services/assesment.service";
import QuestionSection from "./QuestionSection";
import DescriptiveQuestionModal from "./DescriptiveQuestionModal";
import BatchService from "../../../services/batch.service";
import BatchForm from "./BatchForm";

const SCHEDULING_TYPES = [
    { value: "fixed", label: "Fixed" },
    { value: "flexible", label: "Flexible" },
    { value: "batch_wise", label: "Batch Wise" },
];

export default function CreateAssessmentModal({ onClose, onSuccess }) {

    const [step, setStep] = useState(1);
    const [types, setTypes] = useState([]);
    const [assessmentId, setAssessmentId] = useState(null);
    const [batchId, setBatchId] = useState(null);
    const [assessmentTypeSlug, setAssessmentTypeSlug] = useState("");
    const [errors, setErrors] = useState({});
    const [loading, setLoading] = useState(false);

    const [totalMinutes, setTotalMinutes] = useState(0);

    const [form, setForm] = useState({
        assessment_type_id: "",
        title: "",
        description: "",
        scheduling_type: "fixed",
        publish_date: "",
        start_time: "",
        end_time: "",
        start_date: "",
        end_date: "",
        duration_minutes: "",
        shuffle: false,
        is_library: false,
        is_active: true,
        has_negative: false,
        negative_marks: 0,
        difficulty_level: "",
    });

    const isBatchWise = form.scheduling_type === "batch_wise";
    const isFixed = form.scheduling_type === "fixed";
    const isFlexible = form.scheduling_type === "flexible";

    const validate = () => {
        const e = {};

        if (!form.assessment_type_id) {
            e.assessment_type_id = "Assessment type is required.";
        }

        if (!form.title.trim()) {
            e.title = "Title is required.";
        } else if (form.title.length > 255) {
            e.title = "Title cannot exceed 255 characters.";
        }

        if (isFixed) {

            if (!form.publish_date) {
                e.publish_date = "Publish date is required.";
            }

            if (!form.start_time) {
                e.start_time = "Start time is required.";
            }

            if (!form.end_time) {
                e.end_time = "End time is required.";
            }

            if (form.start_time && form.end_time) {

                const start = new Date(`2000-01-01 ${form.start_time}`);
                const end = new Date(`2000-01-01 ${form.end_time}`);

                if (end <= start) {
                    e.end_time = "End time must be after start time.";
                }

                const diff = (end - start) / (1000 * 60);

                if (diff <= 0) {
                    e.end_time = "Duration must be greater than zero.";
                }

                if (diff > 720) {
                    e.end_time = "Duration cannot exceed 12 hours.";
                }
            }

        } else if (isFlexible) {

            if (!form.start_date) {
                e.start_date = "Start date is required.";
            }

            if (!form.end_date) {
                e.end_date = "End date is required.";
            }

            if (
                form.start_date &&
                form.end_date &&
                new Date(form.end_date) < new Date(form.start_date)
            ) {
                e.end_date = "End date must be on or after start date.";
            }

            if (!form.duration_minutes || Number(form.duration_minutes) <= 0) {
                e.duration_minutes = "Duration is required.";
            }
        }

        if (!form.description.trim()) {
            e.description = "Description is required.";
        }

        if (!form.difficulty_level) {
            e.difficulty_level = "Please select difficulty level.";
        }

        if (form.has_negative) {

            if (
                form.negative_marks === "" ||
                form.negative_marks === null
            ) {
                e.negative_marks = "Negative marks are required.";
            }

            if (Number(form.negative_marks) < 0) {
                e.negative_marks = "Negative marks cannot be negative.";
            }
        }

        setErrors(e);

        return Object.keys(e).length === 0;
    };
    useEffect(() => {
        AssessmentService.getAssessmentTypes().then(setTypes);
    }, []);

    const saveAssessment = async () => {

        if (!validate()) return;

        setLoading(true);

        try {

            const payload = {
                assessment_type_id: form.assessment_type_id,
                title: form.title,
                description: form.description,
                scheduling_type: form.scheduling_type,
                shuffle: form.shuffle,
                is_library: form.is_library,
                is_active: form.is_active,
                has_negative: form.has_negative,
                negative_marks: form.negative_marks,
                difficulty_level: form.difficulty_level,
            };

            if (isFixed) {
                payload.publish_date = form.publish_date;
                payload.start_time = form.start_time
                    ? `${form.start_time}:00`
                    : null;
                payload.end_time = form.end_time
                    ? `${form.end_time}:00`
                    : null;
            } else if (isFlexible) {
                payload.start_date = form.start_date;
                payload.end_date = form.end_date;
                payload.duration_minutes = Number(form.duration_minutes);
            }

            const res = await AssessmentService.createAssessment(payload);

            const selectedType = types.find(
                (t) => t.id == form.assessment_type_id
            );

            setAssessmentTypeSlug(selectedType?.slug || "");

            // createAssessment resolves to { message, data } — the created
            // assessment is nested under `data`, not at the top level.
            setAssessmentId(res.data.id);

            if (isBatchWise) {
                setStep(2); // Batch
            } else {
                setBatchId(null);
                setStep(3); // Questions directly
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

            } else if (err.response?.status === 403) {

                toast.error(err.response.data.message);

            } else {

                toast.error(
                    err.response?.data?.message ||
                    "Failed to create assessment."
                );
            }

        } finally {

            setLoading(false);

        }
    };

    const calculateDuration = (start, end) => {

        if (!start || !end) return 0;

        const [sh, sm] = start.split(":").map(Number);
        const [eh, em] = end.split(":").map(Number);

        const startMinutes = sh * 60 + sm;
        const endMinutes = eh * 60 + em;

        return endMinutes > startMinutes
            ? endMinutes - startMinutes
            : 0;
    };

    return (
        <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center">

            <div className="bg-white w-[900px] rounded-xl shadow-xl">

                {/* Header */}
                <div className="border-b px-6 py-4 flex justify-between">
                    <h2 className="font-semibold">Create Assessment</h2>
                    <button onClick={onClose}>✕</button>
                </div>

                {/* Tabs */}
                <div className="flex gap-6 px-6 pt-4 text-sm">

                    <span className={step === 1 ? "text-red-600 font-semibold" : ""}>
                        1. Assessment
                    </span>

                    {isBatchWise && (
                        <span className={step === 2 ? "text-red-600 font-semibold" : ""}>
                            2. Batch
                        </span>
                    )}

                    <span
                        className={step === 3 ? "text-red-600 font-semibold" : ""}
                    >
                        {isBatchWise ? "3. Questions" : "2. Questions"}
                    </span>

                </div>

                {/* Body */}
                <div className="p-6 max-h-[70vh] overflow-y-auto">

                    {/* STEP 1 */}
                    {step === 1 && (
                        <>
                            <div className="grid grid-cols-2 gap-4">

                                {/* Assessment Type */}
                                <div className="flex flex-col gap-1">
                                    <label className="text-xs text-gray-600">
                                        Assessment Type
                                    </label>

                                    <select
                                        className={`border p-2 rounded ${errors.assessment_type_id ? "border-red-500" : ""
                                            }`}
                                        value={form.assessment_type_id}
                                        onChange={(e) => {
                                            setForm((prev) => ({
                                                ...prev,
                                                assessment_type_id: Number(e.target.value),
                                            }));

                                            setErrors((prev) => ({
                                                ...prev,
                                                assessment_type_id: "",
                                            }));
                                        }}
                                    >
                                        <option value="">Select Type</option>

                                        {types.map((t) => (
                                            <option key={t.id} value={t.id}>
                                                {t.name}
                                            </option>
                                        ))}
                                    </select>

                                    {errors.assessment_type_id && (
                                        <p className="text-red-500 text-xs mt-1">
                                            {errors.assessment_type_id}
                                        </p>
                                    )}
                                </div>

                                {/* Title */}
                                <div className="flex flex-col gap-1">
                                    <label className="text-xs text-gray-600">
                                        Title
                                    </label>

                                    <input
                                        className="border p-2 rounded"
                                        placeholder="Assessment title"
                                        onChange={(e) =>
                                            setForm({
                                                ...form,
                                                title: e.target.value,
                                            })
                                        }
                                    />
                                    {errors.title && (
                                        <p className="text-red-500 text-xs mt-1">
                                            {errors.title}
                                        </p>
                                    )}
                                </div>

                            </div>

                            {/* Scheduling Type */}
                            <div className="flex flex-col gap-2 mt-4">
                                <label className="text-xs text-gray-600">
                                    Scheduling Type
                                </label>

                                <div className="flex gap-3">
                                    {SCHEDULING_TYPES.map((opt) => (
                                        <button
                                            key={opt.value}
                                            type="button"
                                            onClick={() =>
                                                setForm({
                                                    ...form,
                                                    scheduling_type: opt.value,
                                                })
                                            }
                                            className={`px-4 py-2 rounded border text-sm transition
                                                ${form.scheduling_type === opt.value
                                                    ? "bg-purple-600 text-white border-purple-600"
                                                    : "bg-white text-gray-700 hover:bg-purple-50"
                                                }`}
                                        >
                                            {opt.label}
                                        </button>
                                    ))}
                                </div>
                            </div>

                            {/* Fixed schedule fields */}
                            {isFixed && (
                                <div className="grid grid-cols-2 gap-4 mt-4">

                                    {/* Publish Date */}
                                    <div className="flex flex-col gap-1">
                                        <label className="text-xs text-gray-600">
                                            Publish Date
                                        </label>

                                        <input
                                            type="date"
                                            className="border p-2 rounded"
                                            onChange={(e) =>
                                                setForm({
                                                    ...form,
                                                    publish_date: e.target.value,
                                                })
                                            }
                                        />
                                        {errors.publish_date && (
                                            <p className="text-red-500 text-xs mt-1">
                                                {errors.publish_date}
                                            </p>
                                        )}
                                    </div>

                                    {/* Start Time */}
                                    <div className="flex flex-col gap-1">
                                        <label className="text-xs text-gray-600">
                                            Start Time
                                        </label>

                                        <input
                                            type="time"
                                            className="border p-2 rounded"
                                            onChange={(e) => {

                                                const value = e.target.value;

                                                setForm((prev) => {

                                                    const updated = {
                                                        ...prev,
                                                        start_time: value,
                                                    };

                                                    setTotalMinutes(
                                                        calculateDuration(
                                                            updated.start_time,
                                                            updated.end_time
                                                        )
                                                    );

                                                    return updated;
                                                });
                                            }}
                                        />
                                        {errors.start_time && (
                                            <p className="text-red-500 text-xs mt-1">
                                                {errors.start_time}
                                            </p>
                                        )}
                                    </div>

                                    {/* End Time */}
                                    <div className="flex flex-col gap-1">
                                        <label className="text-xs text-gray-600">
                                            End Time
                                        </label>

                                        <input
                                            type="time"
                                            className="border p-2 rounded"
                                            onChange={(e) => {

                                                const value = e.target.value;

                                                setForm((prev) => {

                                                    const updated = {
                                                        ...prev,
                                                        end_time: value,
                                                    };

                                                    setTotalMinutes(
                                                        calculateDuration(
                                                            updated.start_time,
                                                            updated.end_time
                                                        )
                                                    );

                                                    return updated;
                                                });
                                            }}
                                        />
                                        {errors.end_time && (
                                            <p className="text-red-500 text-xs mt-1">
                                                {errors.end_time}
                                            </p>
                                        )}
                                    </div>

                                    {/* Duration */}
                                    <div className="flex flex-col gap-1">
                                        <label className="text-xs text-gray-600">
                                            Total Duration (Minutes)
                                        </label>

                                        <input
                                            type="number"
                                            className="border p-2 rounded bg-gray-100 cursor-not-allowed"
                                            value={totalMinutes}
                                            disabled
                                        />

                                    </div>

                                </div>
                            )}

                            {/* Flexible schedule fields */}
                            {isFlexible && (
                                <div className="grid grid-cols-2 gap-4 mt-4">

                                    {/* Start Date */}
                                    <div className="flex flex-col gap-1">
                                        <label className="text-xs text-gray-600">
                                            Start Date
                                        </label>

                                        <input
                                            type="date"
                                            className="border p-2 rounded"
                                            onChange={(e) =>
                                                setForm({
                                                    ...form,
                                                    start_date: e.target.value,
                                                })
                                            }
                                        />
                                        {errors.start_date && (
                                            <p className="text-red-500 text-xs mt-1">
                                                {errors.start_date}
                                            </p>
                                        )}
                                    </div>

                                    {/* End Date */}
                                    <div className="flex flex-col gap-1">
                                        <label className="text-xs text-gray-600">
                                            End Date
                                        </label>

                                        <input
                                            type="date"
                                            className="border p-2 rounded"
                                            onChange={(e) =>
                                                setForm({
                                                    ...form,
                                                    end_date: e.target.value,
                                                })
                                            }
                                        />
                                        {errors.end_date && (
                                            <p className="text-red-500 text-xs mt-1">
                                                {errors.end_date}
                                            </p>
                                        )}
                                    </div>

                                    {/* Duration Minutes */}
                                    <div className="flex flex-col gap-1">
                                        <label className="text-xs text-gray-600">
                                            Duration Per Attempt (Minutes)
                                        </label>

                                        <input
                                            type="number"
                                            min="1"
                                            className="border p-2 rounded"
                                            value={form.duration_minutes}
                                            onChange={(e) =>
                                                setForm({
                                                    ...form,
                                                    duration_minutes: e.target.value,
                                                })
                                            }
                                        />
                                        {errors.duration_minutes && (
                                            <p className="text-red-500 text-xs mt-1">
                                                {errors.duration_minutes}
                                            </p>
                                        )}
                                    </div>

                                </div>
                            )}

                            {isBatchWise && (
                                <p className="text-sm text-gray-500 mt-4">
                                    Schedule will be set per batch in the next step.
                                </p>
                            )}

                            {/* Description */}
                            <div className="flex flex-col gap-1 mt-4">
                                <label className="text-xs text-gray-600">
                                    Description
                                </label>

                                <textarea
                                    className="border w-full p-2 rounded"
                                    rows={3}
                                    placeholder="Assessment description"
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            description: e.target.value,
                                        })
                                    }
                                />
                                {errors.description && (
                                    <p className="text-red-500 text-xs mt-1">
                                        {errors.description}
                                    </p>
                                )}
                            </div>
                            {/* Difficulty Level */}
                            <div className="flex flex-col gap-2 mt-4">
                                <label className="text-xs text-gray-600">Difficulty Level</label>

                                <div className="flex gap-3">

                                    <button
                                        type="button"
                                        onClick={() => setForm({ ...form, difficulty_level: "easy" })}
                                        className={`px-4 py-2 rounded border text-sm transition
            ${form.difficulty_level === "easy"
                                                ? "bg-green-600 text-white border-green-600"
                                                : "bg-white text-gray-700 hover:bg-green-50"
                                            }`}
                                    >
                                        Easy
                                    </button>

                                    <button
                                        type="button"
                                        onClick={() => setForm({ ...form, difficulty_level: "medium" })}
                                        className={`px-4 py-2 rounded border text-sm transition
            ${form.difficulty_level === "medium"
                                                ? "bg-yellow-500 text-white border-yellow-500"
                                                : "bg-white text-gray-700 hover:bg-yellow-50"
                                            }`}
                                    >
                                        Medium
                                    </button>

                                    <button
                                        type="button"
                                        onClick={() => setForm({ ...form, difficulty_level: "hard" })}
                                        className={`px-4 py-2 rounded border text-sm transition
            ${form.difficulty_level === "hard"
                                                ? "bg-red-600 text-white border-red-600"
                                                : "bg-white text-gray-700 hover:bg-red-50"
                                            }`}
                                    >
                                        Hard
                                    </button>

                                </div>
                                {errors.difficulty_level && (
                                    <p className="text-red-500 text-xs mt-1">
                                        {errors.difficulty_level}
                                    </p>
                                )}
                            </div>

                            {/* Options */}
                            <div className="flex gap-6 mt-4 text-sm">

                                <label className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        onChange={(e) =>
                                            setForm({
                                                ...form,
                                                shuffle: e.target.checked,
                                            })
                                        }
                                    />
                                    Shuffle Questions
                                </label>

                                <label className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        onChange={(e) =>
                                            setForm({
                                                ...form,
                                                has_negative: e.target.checked,
                                            })
                                        }
                                    />
                                    Negative Marks
                                </label>

                                <label className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        checked={form.is_library}
                                        onChange={(e) =>
                                            setForm({
                                                ...form,
                                                is_library: e.target.checked,
                                            })
                                        }
                                    />
                                    Add to Library
                                </label>

                                <label className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        checked={form.is_active}
                                        onChange={(e) =>
                                            setForm({
                                                ...form,
                                                is_active: e.target.checked,
                                            })
                                        }
                                    />
                                    Active
                                </label>

                            </div>
                        </>
                    )}

                    {/* STEP 2 */}

                    {step === 2 && (
                        <BatchForm
                            assessmentId={assessmentId}
                            onSuccess={(batch) => {
                                setBatchId(batch.id);
                                setStep(3);
                            }}
                        />
                    )}

                    {step === 3 && assessmentTypeSlug === "mcq" && (
                        <QuestionSection
                            assessmentId={assessmentId}
                            batchId={batchId}
                        />
                    )}

                    {step === 3 && assessmentTypeSlug === "descriptive" && (
                        <DescriptiveQuestionModal
                            assessmentId={assessmentId}
                            batchId={batchId}
                        />
                    )}

                </div>

                {/* Footer */}
                <div className="border-t px-6 py-4 flex justify-end">

                    {step === 1 && (
                        <button
                            onClick={saveAssessment}
                            className="bg-red-600 text-white px-4 py-2 rounded"
                        >
                            Save & Next
                        </button>
                    )}

                    {step === 3 && (
                        <button
                            onClick={() => {
                                onSuccess();
                                onClose();
                            }}
                            className="bg-green-600 text-white px-4 py-2 rounded"
                        >
                            Finish
                        </button>
                    )}

                </div>

            </div>
        </div>
    );
}
