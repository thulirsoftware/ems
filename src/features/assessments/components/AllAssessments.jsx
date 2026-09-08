export default function AllAssessments({ data }) {
  return (
    <section>
      <h2 className="text-lg font-semibold text-gray-700 mb-4">
        All Assessments
      </h2>

      {data.length === 0 ? (
        <p className="text-gray-500 italic">
          No assessments available
        </p>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {data.map((exam) => (
            <div
              key={exam.id}
              className="rounded-xl border border-gray-300 p-5 bg-white shadow-sm
              hover:-translate-y-1 hover:shadow-lg transition"
            >
              <h3 className="font-semibold text-gray-800 text-lg mb-2">
                {exam.title}
              </h3>

              <p className="text-sm text-gray-600 mb-2">
                {exam.description}
              </p>

              <div className="text-sm text-gray-600 space-y-1">
                <p>📅 Date: {exam.publish_date}</p>
                <p>⏰ Time: {exam.start_time} - {exam.end_time}</p>
              </div>

              <button className="mt-4 w-full py-2 rounded-lg font-medium
                bg-gray-800 text-white hover:bg-gray-900 transition">
                Open
              </button>
            </div>
          ))}
        </div>
      )}
    </section>
  );
}
