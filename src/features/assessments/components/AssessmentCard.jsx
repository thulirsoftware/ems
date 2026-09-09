const VARIANTS = {
  running: {
    badge: "LIVE",
    badgeClass: "bg-red-100 text-red-600 animate-pulse",
    borderClass: "border-red-500",
    actionLabel: "Start Exam",
    actionClass: "bg-red-600 hover:bg-red-700",
  },
  today: {
    badge: "TODAY",
    badgeClass: "bg-blue-100 text-blue-600",
    borderClass: "border-blue-500",
    actionLabel: "View Details",
    actionClass: "bg-blue-600 hover:bg-blue-700",
  },
  upcoming: {
    badge: "UPCOMING",
    badgeClass: "bg-indigo-100 text-indigo-600",
    borderClass: "border-indigo-400",
    actionLabel: "View Details",
    actionClass: "bg-indigo-600 hover:bg-indigo-700",
  },
  missed: {
    badge: "MISSED",
    badgeClass: "bg-gray-200 text-gray-600",
    borderClass: "border-gray-300",
    actionLabel: null,
  },
  completed: {
    badge: "COMPLETED",
    badgeClass: "bg-emerald-100 text-emerald-700",
    borderClass: "border-emerald-400",
    actionLabel: "View Result",
    actionClass: "bg-emerald-600 hover:bg-emerald-700",
  },
};

export default function AssessmentCard({ exam, variant = "today", onAction }) {
  const v = VARIANTS[variant] ?? VARIANTS.today;

  return (
    <div
      className={`rounded-xl border ${v.borderClass} p-5 bg-white shadow-sm
      hover:-translate-y-1 hover:shadow-lg transition`}
    >
      <div className="flex justify-between items-start gap-3 mb-3">
        <h3 className="font-semibold text-gray-800 text-lg">
          {exam.title || "Assessment"}
        </h3>

        <span
          className={`text-xs px-3 py-1 rounded-full font-semibold whitespace-nowrap ${v.badgeClass}`}
        >
          {v.badge}
        </span>
      </div>

      {exam.description && (
        <p className="text-sm text-gray-600 mb-2 line-clamp-2">
          {exam.description}
        </p>
      )}

      <div className="text-sm text-gray-600 space-y-1">
        <p>📅 Date: {exam.publish_date || "-"}</p>
        <p>
          ⏰ Time: {exam.start_time || "-"} - {exam.end_time || "-"}
        </p>
        {variant === "running" && (
          <p>❌ Negative Marking: {exam.has_negative ? "Yes" : "No"}</p>
        )}
      </div>

      {v.actionLabel && (
        <button
          onClick={onAction}
          className={`mt-4 w-full py-2 rounded-lg font-medium text-white transition ${v.actionClass}`}
        >
          {v.actionLabel}
        </button>
      )}
    </div>
  );
}
