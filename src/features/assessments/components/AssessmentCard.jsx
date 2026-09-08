export default function AssessmentCard({ exam, type }) {
  const isRunning = type === "running";

  return (
    <div
      className={`rounded-xl border shadow-sm p-5 bg-white
      transition hover:-translate-y-1 hover:shadow-lg
      ${isRunning ? "border-red-500" : "border-blue-500"}`}
    >
      <div className="flex justify-between items-start mb-3">
        <h3 className="font-semibold text-gray-800 text-lg">
          {exam.title}
        </h3>

        <span
          className={`text-xs font-semibold px-3 py-1 rounded-full
          ${isRunning
            ? "bg-red-100 text-red-600 animate-pulse"
            : "bg-blue-100 text-blue-600"}`}
        >
          {isRunning ? "LIVE" : "UPCOMING"}
        </span>
      </div>

      <div className="text-sm text-gray-600 space-y-1">
        <p>📚 Subject: {exam.subject}</p>
        <p>⏱ Duration: {exam.duration} mins</p>
        <p>📅 Start: {exam.start_time}</p>
      </div>

      <button
        className={`mt-4 w-full py-2 rounded-lg font-medium transition
        ${isRunning
          ? "bg-red-600 text-white hover:bg-red-700"
          : "bg-blue-600 text-white hover:bg-blue-700"}`}
      >
        {isRunning ? "Start Exam" : "View Details"}
      </button>
    </div>
  );
}
