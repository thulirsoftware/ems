import { CalendarDays } from "lucide-react";
import { useNavigate } from "react-router-dom";

function formatDate(dateStr) {
  if (!dateStr) return "Unscheduled";

  return new Date(dateStr).toLocaleDateString(undefined, {
    day: "2-digit",
    month: "short",
  });
}

export default function UpcomingExams({
  exams = [],
  loading,
  title = "Upcoming Exams",
  emptyMessage = "No upcoming exams",
  iconColor = "text-red-500",
  iconBg = "bg-red-100",
}) {
  const navigate = useNavigate();

  return (
    <div className="bg-white rounded-2xl shadow-sm p-6">

      <h3 className="font-semibold text-lg mb-4">
        {title}
      </h3>

      {loading ? (
        <p className="text-gray-400 text-sm py-4">Loading...</p>
      ) : exams.length === 0 ? (
        <p className="text-gray-400 text-sm py-4">{emptyMessage}</p>
      ) : (
        exams.map((exam, i) => (
          <div
            key={exam.assessment_id ?? i}
            onClick={() => exam.assessment_id && navigate(`/assesments/${exam.assessment_id}`)}
            className="flex items-center justify-between py-3 border-b last:border-none cursor-pointer hover:bg-gray-50 rounded-lg px-2 -mx-2 transition"
          >
            <div className="flex items-center gap-3">
              <div className={`${iconBg} p-2 rounded-lg`}>
                <CalendarDays size={18} className={iconColor}/>
              </div>

              <span className="font-medium">
                {exam.assessment_title}
              </span>
            </div>

            <span className="text-gray-500 text-sm">
              {formatDate(exam.publish_date || exam.start_date)}
            </span>
          </div>
        ))
      )}

    </div>
  );
}
