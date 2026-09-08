import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import AssessmentService from "../../../services/assesment.service";

export default function CompletedAssessments() {

  const [data, setData] = useState([]);
  const [loading, setLoading] = useState(true);
  const navigate = useNavigate();

  useEffect(() => {
    fetchCompletedAssessments();
  }, []);

  const fetchCompletedAssessments = async () => {
    try {

      const res = await AssessmentService.CompletedAssessmentList();

      // normalize response
      const result = Array.isArray(res?.data)
        ? res.data
        : Array.isArray(res)
        ? res
        : [];

      // remove only null values
      const cleaned = result.filter(item => item !== null);

      setData(cleaned);

    } catch (error) {

      console.error("Error fetching completed assessments", error);
      setData([]);

    } finally {

      setLoading(false);

    }
  };

  if (loading) {
    return <p className="text-gray-500 italic">Loading assessments...</p>;
  }

  return (
    <section>

      <h2 className="text-lg font-semibold text-blue-600 mb-4">
        Completed Assessments
      </h2>

      {data.length === 0 ? (
        <p className="text-gray-500 italic">No Completed exams</p>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

          {data.map((exam) => (

            <div
              key={exam.id}
              className="rounded-xl border border-blue-500 p-5 bg-white shadow-sm hover:-translate-y-1 hover:shadow-lg transition"
            >

              <div className="flex justify-between items-start mb-3">

                <h3 className="font-semibold text-gray-800 text-lg">
                  {exam.title}
                </h3>

                <span className="text-xs px-3 py-1 rounded-full font-semibold bg-blue-100 text-blue-600">
                  COMPLETED
                </span>

              </div>

              <p className="text-sm text-gray-600 mb-2">
                {exam.description}
              </p>

              <div className="text-sm text-gray-600 space-y-1">

                <p>📅 Date: {exam.publish_date}</p>

                <p>
                  ⏰ Time: {exam.start_time} - {exam.end_time}
                </p>

              </div>

              <button
                onClick={() =>
                  navigate(`/assesments/end/${exam.id}/result`)
                }
                className="mt-4 w-full py-2 rounded-lg font-medium bg-blue-600 text-white hover:bg-blue-700 transition"
              >
                View Result
              </button>

            </div>

          ))}

        </div>
      )}

    </section>
  );
}