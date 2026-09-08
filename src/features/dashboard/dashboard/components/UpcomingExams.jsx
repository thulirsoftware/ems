import { CalendarDays } from "lucide-react";

export default function UpcomingExams() {

  const exams = [
    { name: "React Assessment", date: "20 Feb" },
    { name: "JavaScript Test", date: "25 Feb" },
    { name: "DBMS Quiz", date: "28 Feb" },
  ];

  return (
    <div className="bg-white rounded-2xl shadow-sm p-6">

      <h3 className="font-semibold text-lg mb-4">
        Upcoming Exams
      </h3>

      {exams.map((exam, i) => (
        <div
          key={i}
          className="flex items-center justify-between py-3 border-b last:border-none"
        >
          <div className="flex items-center gap-3">
            <div className="bg-red-100 p-2 rounded-lg">
              <CalendarDays size={18} className="text-red-500"/>
            </div>

            <span className="font-medium">
              {exam.name}
            </span>
          </div>

          <span className="text-gray-500 text-sm">
            {exam.date}
          </span>
        </div>
      ))}

    </div>
  );
}