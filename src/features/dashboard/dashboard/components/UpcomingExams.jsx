import { CalendarDays } from "lucide-react";

function formatDate(dateStr) {
  if (!dateStr) return "Unscheduled";

  return new Date(dateStr).toLocaleDateString(undefined, {
    day: "2-digit",
    month: "short",
  });
}

export default function UpcomingExams({ exams = [], loading }) {

  return (
    <div className="bg-white rounded-2xl shadow-sm p-6">

      <h3 className="font-semibold text-lg mb-4">
        Upcoming Exams
      </h3>

      {loading ? (
        <p className="text-gray-400 text-sm py-4">Loading...</p>
      ) : exams.length === 0 ? (
        <p className="text-gray-400 text-sm py-4">No upcoming exams</p>
      ) : (
        exams.map((exam, i) => (
          <div
            key={exam.assessment_id ?? i}
            className="flex items-center justify-between py-3 border-b last:border-none"
          >
            <div className="flex items-center gap-3">
              <div className="bg-red-100 p-2 rounded-lg">
                <CalendarDays size={18} className="text-red-500"/>
              </div>

              <span className="font-medium">
                {exam.assessment_title}
              </span>
            </div>

            <span className="text-gray-500 text-sm">
              {formatDate(exam.publish_date)}
            </span>
          </div>
        ))
      )}

    </div>
  );
}
