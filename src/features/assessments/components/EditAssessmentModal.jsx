import { useEffect, useState } from "react";
import { toast } from "sonner";
import AssessmentService from "../../../services/assesment.service";
import EditQuestionSection from "./EditQuestionSection";
import EditDescriptiveQuestionModal from "./EditDescriptiveQuestionModal";
import BatchService from "../../../services/batch.service";
import BatchForm from "./BatchForm";

const SCHEDULING_TYPE_LABELS = {
  fixed: "Fixed",
  flexible: "Flexible",
  batch_wise: "Batch Wise",
};

export default function EditAssessmentModal({
  assessmentId,
  onClose,
  onSuccess,
}) {

  const [step, setStep] = useState(1);
  const [types, setTypes] = useState([]);
  const [assessmentTypeSlug, setAssessmentTypeSlug] = useState("");
  const [batchId, setBatchId] = useState(null);
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

  // load types
  useEffect(() => {
    AssessmentService.getAssessmentTypes().then(setTypes);
  }, []);

  // load assessment
  useEffect(() => {

    if (!assessmentId) return;

    AssessmentService.getAssessmentById(assessmentId).then((data) => {

      const start = data.start_time?.slice(0, 5) || "";
      const end = data.end_time?.slice(0, 5) || "";

      setForm({
        assessment_type_id: data.assessment_type_id,
        title: data.title,
        description: data.description,
        scheduling_type: data.scheduling_type,
        publish_date: data.publish_date || "",
        start_time: start,
        end_time: end,
        start_date: data.start_date || "",
        end_date: data.end_date || "",
        duration_minutes: data.duration_minutes ?? "",
        shuffle: !!data.shuffle,
        is_library: !!data.is_library,
        is_active: !!data.is_active,
        has_negative: !!data.has_negative,
        negative_marks: data.negative_marks ?? 0,
        difficulty_level: data.difficulty_level || "",
      });

      if (start && end) {
        setTotalMinutes(calculateDuration(start, end));
      }

      const type = types.find(t => t.id === data.assessment_type_id);
      setAssessmentTypeSlug(type?.slug || "");

      // getBatchesByAssessment 422s for non-batch_wise assessments (their
      // schedule lives on the implicit batch, managed through this same
      // Assessment API) — only batch_wise ever has addressable batches.
      if (data.scheduling_type === "batch_wise") {
        BatchService.getBatchesByAssessment(assessmentId).then((res) => {
          if (res.length > 0) {
            setBatchId(res[0].id);
          }
        });
      }

    });

  }, [assessmentId, types]);



  const calculateDuration = (start, end) => {

    if (!start || !end) return 0;

    const [sh, sm] = start.split(":").map(Number);
    const [eh, em] = end.split(":").map(Number);

    return eh * 60 + em - (sh * 60 + sm);
  };


  const updateAssessment = async () => {

    const payload = {
      assessment_type_id: form.assessment_type_id,
      title: form.title,
      description: form.description,
      shuffle: form.shuffle,
      is_library: form.is_library,
      is_active: form.is_active,
      has_negative: form.has_negative,
      negative_marks: form.negative_marks,
      difficulty_level: form.difficulty_level,
    };

    if (isFixed) {
      payload.publish_date = form.publish_date;
      payload.start_time = form.start_time ? `${form.start_time}:00` : null;
      payload.end_time = form.end_time ? `${form.end_time}:00` : null;
    } else if (isFlexible) {
      payload.start_date = form.start_date;
      payload.end_date = form.end_date;
      payload.duration_minutes = form.duration_minutes
        ? Number(form.duration_minutes)
        : null;
    }

    try {
      await AssessmentService.updateAssessment(assessmentId, payload);

      if (isBatchWise) {
        setStep(2); // Go to Batch
      } else {
        setBatchId(null);
        setStep(3); // Go directly to Questions
      }
    } catch (err) {
      toast.error(
        err.response?.data?.message || "Failed to update assessment."
      );
    }
  };


  return (

    <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50">

      <div className="bg-white w-[900px] rounded-xl shadow-xl">

        {/* header */}

        <div className="border-b px-6 py-4 flex justify-between">
          <h2 className="font-semibold">Edit Assessment</h2>
          <button onClick={onClose}>✕</button>
        </div>


        {/* tabs */}

        <div className="flex gap-6 px-6 pt-4 text-sm">

          <span className={step === 1 ? "text-red-600 font-semibold" : ""}>
            1. Assessment
          </span>

          {isBatchWise && (
            <span className={step === 2 ? "text-red-600 font-semibold" : ""}>
              2. Batch
            </span>
          )}

          <span className={step === 3 ? "text-red-600 font-semibold" : ""}>
            {isBatchWise ? "3. Questions" : "2. Questions"}
          </span>

        </div>



        <div className="p-6 max-h-[70vh] overflow-y-auto">

          {step === 1 && (

            <>

              <div className="grid grid-cols-2 gap-4">

                {/* type */}

                <div className="flex flex-col gap-1">
                  <label className="text-xs text-gray-600">
                    Assessment Type
                  </label>

                  <select
                    className="border p-2 rounded"
                    value={form.assessment_type_id}
                    onChange={(e) =>
                      setForm({ ...form, assessment_type_id: e.target.value })
                    }
                  >

                    <option value="">Select Type</option>

                    {types.map(t => (
                      <option key={t.id} value={t.id}>{t.name}</option>
                    ))}

                  </select>
                </div>


                {/* title */}

                <div className="flex flex-col gap-1">
                  <label className="text-xs text-gray-600">Title</label>

                  <input
                    className="border p-2 rounded"
                    value={form.title}
                    onChange={(e) =>
                      setForm({ ...form, title: e.target.value })
                    }
                  />

                </div>

              </div>

              {/* scheduling type (immutable) */}

              <div className="flex flex-col gap-1 mt-4">
                <label className="text-xs text-gray-600">
                  Scheduling Type
                </label>

                <p className="text-sm font-medium text-gray-700">
                  {SCHEDULING_TYPE_LABELS[form.scheduling_type] || "-"}
                  <span className="text-xs text-gray-400 font-normal ml-2">
                    (cannot be changed after creation)
                  </span>
                </p>
              </div>

              {isFixed && (
                <div className="grid grid-cols-2 gap-4 mt-4">

                  {/* publish */}

                  <div className="flex flex-col gap-1">

                    <label className="text-xs text-gray-600">
                      Publish Date
                    </label>

                    <input
                      type="date"
                      className="border p-2 rounded"
                      value={form.publish_date}
                      onChange={(e) =>
                        setForm({ ...form, publish_date: e.target.value })
                      }
                    />

                  </div>


                  {/* start */}

                  <div className="flex flex-col gap-1">

                    <label className="text-xs text-gray-600">
                      Start Time
                    </label>

                    <input
                      type="time"
                      className="border p-2 rounded"
                      value={form.start_time}

                      onChange={(e) => {

                        const value = e.target.value;

                        setForm(prev => {
                          const updated = { ...prev, start_time: value };

                          setTotalMinutes(
                            calculateDuration(updated.start_time, updated.end_time)
                          );

                          return updated;
                        })

                      }}

                    />

                  </div>


                  {/* end */}

                  <div className="flex flex-col gap-1">

                    <label className="text-xs text-gray-600">
                      End Time
                    </label>

                    <input
                      type="time"
                      className="border p-2 rounded"
                      value={form.end_time}

                      onChange={(e) => {

                        const value = e.target.value;

                        setForm(prev => {
                          const updated = { ...prev, end_time: value };

                          setTotalMinutes(
                            calculateDuration(updated.start_time, updated.end_time)
                          );

                          return updated;
                        })

                      }}

                    />

                  </div>


                  {/* duration */}

                  <div className="flex flex-col gap-1">

                    <label className="text-xs text-gray-600">
                      Total Duration
                    </label>

                    <input
                      type="number"
                      className="border p-2 rounded bg-gray-100"
                      value={totalMinutes}
                      disabled
                    />

                  </div>

                </div>
              )}

              {isFlexible && (
                <div className="grid grid-cols-2 gap-4 mt-4">

                  <div className="flex flex-col gap-1">
                    <label className="text-xs text-gray-600">
                      Start Date
                    </label>

                    <input
                      type="date"
                      className="border p-2 rounded"
                      value={form.start_date}
                      onChange={(e) =>
                        setForm({ ...form, start_date: e.target.value })
                      }
                    />
                  </div>

                  <div className="flex flex-col gap-1">
                    <label className="text-xs text-gray-600">
                      End Date
                    </label>

                    <input
                      type="date"
                      className="border p-2 rounded"
                      value={form.end_date}
                      onChange={(e) =>
                        setForm({ ...form, end_date: e.target.value })
                      }
                    />
                  </div>

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
                        setForm({ ...form, duration_minutes: e.target.value })
                      }
                    />
                  </div>

                </div>
              )}

              {isBatchWise && (
                <p className="text-sm text-gray-500 mt-4">
                  Schedule is managed per batch.
                </p>
              )}


              {/* description */}

              <div className="flex flex-col gap-1 mt-4">

                <label className="text-xs text-gray-600">
                  Description
                </label>

                <textarea
                  className="border w-full p-2 rounded"
                  rows={3}
                  value={form.description}
                  onChange={(e) =>
                    setForm({ ...form, description: e.target.value })
                  }
                />

              </div>



              {/* difficulty */}

              <div className="flex flex-col gap-2 mt-4">

                <label className="text-xs text-gray-600">
                  Difficulty Level
                </label>

                <div className="flex gap-3">

                  {["easy", "medium", "hard"].map(level => {

                    const color = {
                      easy: "green",
                      medium: "yellow",
                      hard: "red"
                    }[level];

                    return (

                      <button
                        key={level}
                        type="button"
                        onClick={() => setForm({ ...form, difficulty_level: level })}
                        className={`px-4 py-2 rounded border text-sm

${form.difficulty_level === level
                            ? `bg-${color}-600 text-white`
                            : `bg-white hover:bg-${color}-50`
                          }

`}
                      >

                        {level}

                      </button>

                    )

                  })}

                </div>

              </div>



              {/* options */}

              <div className="flex gap-6 mt-4 text-sm">

                <label className="flex items-center gap-2">
                  <input
                    type="checkbox"
                    checked={form.shuffle}
                    onChange={(e) =>
                      setForm({ ...form, shuffle: e.target.checked })
                    }
                  />
                  Shuffle Questions
                </label>


                <label className="flex items-center gap-2">
                  <input
                    type="checkbox"
                    checked={form.has_negative}
                    onChange={(e) =>
                      setForm({ ...form, has_negative: e.target.checked })
                    }
                  />
                  Negative Marks
                </label>

                <label className="flex items-center gap-2">
                  <input
                    type="checkbox"
                    checked={form.is_library}
                    onChange={(e) =>
                      setForm({ ...form, is_library: e.target.checked })
                    }
                  />
                  Add to Library
                </label>

                <label className="flex items-center gap-2">
                  <input
                    type="checkbox"
                    checked={form.is_active}
                    onChange={(e) =>
                      setForm({ ...form, is_active: e.target.checked })
                    }
                  />
                  Active
                </label>

              </div>
              {form.has_negative && (
                <div className="flex flex-col gap-1 mt-4">
                  <label className="text-xs text-gray-600">
                    Negative Marks
                  </label>

                  <input
                    type="number"
                    min="0"
                    step="0.25"
                    className="border p-2 rounded"
                    value={form.negative_marks}
                    onChange={(e) =>
                      setForm({
                        ...form,
                        negative_marks: Number(e.target.value),
                      })
                    }
                  />
                </div>
              )}

            </>

          )}



          {/* STEP 2 */}

          {step === 2 && (
            <BatchForm
              assessmentId={assessmentId}
              batchId={batchId}
              isEdit={true}
              onSuccess={(batch) => {
                setBatchId(batch.id);
                setStep(3);
              }}
            />
          )}

          {step === 3 && assessmentTypeSlug === "mcq" && (
            <EditQuestionSection
              assessmentId={assessmentId}
              batchId={batchId}
            />
          )}

          {step === 3 && assessmentTypeSlug === "descriptive" && (
            <EditDescriptiveQuestionModal
              assessmentId={assessmentId}
              batchId={batchId}
            />
          )}



        </div>



        <div className="border-t px-6 py-4 flex justify-end">

          {step === 1 && (
            <button
              onClick={updateAssessment}
              className="bg-red-600 text-white px-4 py-2 rounded"
            >
              Update & Next
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
