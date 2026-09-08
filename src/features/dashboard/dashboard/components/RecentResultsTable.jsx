import { CheckCircle, XCircle } from "lucide-react";

export default function RecentResultsTable() {

  const results = [
    {
      exam: "React Assessment",
      date: "10 Feb 2026",
      score: 82,
      status: "Pass",
    },
    {
      exam: "Python Coding Round",
      date: "05 Feb 2026",
      score: 68,
      status: "Fail",
    },
    {
      exam: "SQL Evaluation",
      date: "28 Jan 2026",
      score: 90,
      status: "Pass",
    },
    {
      exam: "Logical Reasoning",
      date: "20 Jan 2026",
      score: 75,
      status: "Pass",
    },
  ];

  return (
    <div className="bg-white rounded-2xl shadow-sm p-6">

      <h3 className="text-lg font-semibold mb-4">
        Recent Exam Results
      </h3>

      <div className="overflow-x-auto">
        <table className="w-full text-sm">

          <thead className="text-gray-500 border-b">
            <tr>
              <th className="text-left py-3">Exam</th>
              <th className="text-left">Date</th>
              <th className="text-left">Score</th>
              <th className="text-left">Status</th>
            </tr>
          </thead>

          <tbody>
            {results.map((item, i) => (
              <tr
                key={i}
                className="border-b hover:bg-gray-50 transition"
              >
                <td className="py-3 font-medium">
                  {item.exam}
                </td>

                <td>{item.date}</td>

                <td>
                  <span className="font-semibold">
                    {item.score}%
                  </span>
                </td>

                <td>
                  {item.status === "Pass" ? (
                    <span className="flex items-center gap-2 text-green-600">
                      <CheckCircle size={16} /> Pass
                    </span>
                  ) : (
                    <span className="flex items-center gap-2 text-red-500">
                      <XCircle size={16} /> Fail
                    </span>
                  )}
                </td>
              </tr>
            ))}
          </tbody>

        </table>
      </div>
    </div>
  );
}