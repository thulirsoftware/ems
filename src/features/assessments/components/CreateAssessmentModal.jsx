import { useEffect, useState } from "react";
import { toast } from "sonner";
import AssessmentService from "../../../services/assesment.service";
import QuestionSection from "./QuestionSection";
import DescriptiveQuestionModal from "./DescriptiveQuestionModal";
import BatchService from "../../../services/batch.service";
import BatchForm from "./BatchForm";
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
        publish_date: "",
        start_time: "",
        end_time: "",
        shuffle: false,
        is_library: false,
        is_batch_wise: false,
        is_active: true,
        has_negative: false,
        negative_marks: 0,
        difficulty_level: "",
    });

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
                ...form,
                start_time: form.start_time
                    ? `${form.start_time}:00`
                    : null,
                end_time: form.end_time
                    ? `${form.end_time}:00`
                    : null,
            };

            const res = await AssessmentService.createAssessment(payload);

            const selectedType = types.find(
                (t) => t.id == form.assessment_type_id
            );

            setAssessmentTypeSlug(selectedType?.slug || "");

            // createAssessment resolves to { message, data } — the created
            // assessment is nested under `data`, not at the top level.
            setAssessmentId(res.data.id);

            if (form.is_batch_wise) {
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

                    {form.is_batch_wise && (
                        <span className={step === 2 ? "text-red-600 font-semibold" : ""}>
                            2. Batch
                        </span>
                    )}

                    <span
                        className={step === 3 ? "text-red-600 font-semibold" : ""}
                    >
                        {form.is_batch_wise ? "3. Questions" : "2. Questions"}
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
                            <label className="flex items-center py-2 gap-2">
                                <input
                                    type="checkbox"
                                    checked={form.is_batch_wise}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            is_batch_wise: e.target.checked,
                                        })
                                    }
                                />
                                Batch Wise Assessment
                            </label>

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