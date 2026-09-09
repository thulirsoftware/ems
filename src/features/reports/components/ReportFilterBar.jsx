import { useEffect, useState } from "react";
import { Filter, X } from "lucide-react";
import AssessmentService from "../../../services/assesment.service";
import BatchService from "../../../services/batch.service";

const STATUS_OPTIONS = [
  { value: "", label: "Any status" },
  { value: "in_progress", label: "In Progress" },
  { value: "pending_evaluation", label: "Pending Evaluation" },
  { value: "evaluated", label: "Evaluated" },
];

const emptyFilters = {
  assessment_id: "",
  batch_id: "",
  assessment_type: "",
  status: "",
  passing_percentage: "",
  from: "",
  to: "",
};

export default function ReportFilterBar({ filters, onChange }) {
  const [assessments, setAssessments] = useState([]);
  const [assessmentTypes, setAssessmentTypes] = useState([]);
  const [batches, setBatches] = useState([]);
  const [local, setLocal] = useState({ ...emptyFilters, ...filters });

  useEffect(() => {
    AssessmentService.AssessmentList().then((data) => {
      // Response has one row per batch for batch-wise assessments — dedupe
      // to a single entry per assessment for the dropdown.
      const seen = new Map();
      (data || []).forEach((row) => {
        if (!seen.has(row.id)) seen.set(row.id, row);
      });
      setAssessments(Array.from(seen.values()));
    });

    AssessmentService.getAssessmentTypes().then((data) =>
      setAssessmentTypes(data || [])
    );
  }, []);

  useEffect(() => {
    if (!local.assessment_id) {
      setBatches([]);
      return;
    }

    BatchService.getBatchesByAssessment(local.assessment_id).then((data) =>
      setBatches(data || [])
    );
  }, [local.assessment_id]);

  const update = (key, value) => {
    setLocal((prev) => ({
      ...prev,
      [key]: value,
      ...(key === "assessment_id" ? { batch_id: "" } : {}),
    }));
  };

  const apply = () => onChange(local);

  const reset = () => {
    setLocal(emptyFilters);
    onChange(emptyFilters);
  };

  return (
    <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
      <div className="flex items-center gap-2 mb-4">
        <Filter size={18} className="text-gray-500" />
        <h3 className="font-semibold">Filters</h3>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div>
          <label className="block text-xs text-gray-500 mb-1">Assessment</label>
          <select
            value={local.assessment_id}
            onChange={(e) => update("assessment_id", e.target.value)}
            className="w-full border rounded-md px-3 py-2 text-sm"
          >
            <option value="">All assessments</option>
            {assessments.map((a) => (
              <option key={a.id} value={a.id}>
                {a.title}
              </option>
            ))}
          </select>
        </div>

        <div>
          <label className="block text-xs text-gray-500 mb-1">Batch</label>
          <select
            value={local.batch_id}
            onChange={(e) => update("batch_id", e.target.value)}
            disabled={!local.assessment_id}
            className="w-full border rounded-md px-3 py-2 text-sm disabled:bg-gray-50"
          >
            <option value="">All batches</option>
            {batches.map((b) => (
              <option key={b.id} value={b.id}>
                {b.name}
              </option>
            ))}
          </select>
        </div>

        <div>
          <label className="block text-xs text-gray-500 mb-1">Type</label>
          <select
            value={local.assessment_type}
            onChange={(e) => update("assessment_type", e.target.value)}
            className="w-full border rounded-md px-3 py-2 text-sm"
          >
            <option value="">All types</option>
            {assessmentTypes.map((t) => (
              <option key={t.id} value={t.slug}>
                {t.name}
              </option>
            ))}
          </select>
        </div>

        <div>
          <label className="block text-xs text-gray-500 mb-1">Status</label>
          <select
            value={local.status}
            onChange={(e) => update("status", e.target.value)}
            className="w-full border rounded-md px-3 py-2 text-sm"
          >
            {STATUS_OPTIONS.map((s) => (
              <option key={s.value} value={s.value}>
                {s.label}
              </option>
            ))}
          </select>
        </div>

        <div>
          <label className="block text-xs text-gray-500 mb-1">From</label>
          <input
            type="date"
            value={local.from}
            onChange={(e) => update("from", e.target.value)}
            className="w-full border rounded-md px-3 py-2 text-sm"
          />
        </div>

        <div>
          <label className="block text-xs text-gray-500 mb-1">To</label>
          <input
            type="date"
            value={local.to}
            onChange={(e) => update("to", e.target.value)}
            className="w-full border rounded-md px-3 py-2 text-sm"
          />
        </div>

        <div>
          <label className="block text-xs text-gray-500 mb-1">
            Passing %
          </label>
          <input
            type="number"
            min={0}
            max={100}
            placeholder="40"
            value={local.passing_percentage}
            onChange={(e) => update("passing_percentage", e.target.value)}
            className="w-full border rounded-md px-3 py-2 text-sm"
          />
        </div>

        <div className="flex items-end gap-2">
          <button
            onClick={apply}
            className="flex-1 px-4 py-2 rounded-md bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium"
          >
            Apply
          </button>
          <button
            onClick={reset}
            title="Clear filters"
            className="p-2 rounded-md border hover:bg-gray-50"
          >
            <X size={16} />
          </button>
        </div>
      </div>
    </div>
  );
}
