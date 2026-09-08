export default function ReportTable() {

  const reports = [
    {
      exam: "React Assessment",
      attempts: 2,
      highest: 85,
      average: 78,
      lastAttempt: "12 Feb 2026",
      status: "Passed",
    },
    {
      exam: "Python Coding",
      attempts: 1,
      highest: 65,
      average: 65,
      lastAttempt: "05 Feb 2026",
      status: "Failed",
    },
    {
      exam: "JavaScript Fundamentals",
      attempts: 3,
      highest: 90,
      average: 82,
      lastAttempt: "18 Feb 2026",
      status: "Passed",
    },
    {
      exam: "SQL Database Test",
      attempts: 2,
      highest: 72,
      average: 69,
      lastAttempt: "10 Feb 2026",
      status: "Passed",
    },
    {
      exam: "Aptitude Assessment",
      attempts: 1,
      highest: 58,
      average: 58,
      lastAttempt: "02 Feb 2026",
      status: "Failed",
    },
    {
      exam: "HTML & CSS",
      attempts: 2,
      highest: 88,
      average: 84,
      lastAttempt: "20 Feb 2026",
      status: "Passed",
    },
    {
      exam: "Data Structures",
      attempts: 3,
      highest: 76,
      average: 71,
      lastAttempt: "15 Feb 2026",
      status: "Passed",
    },
    {
      exam: "Logical Reasoning",
      attempts: 1,
      highest: 62,
      average: 62,
      lastAttempt: "08 Feb 2026",
      status: "Failed",
    },
  ];

  return (
    <div className="bg-white border rounded-xl">

      <div className="p-6 border-b">
        <h3 className="font-semibold text-gray-700">
          Detailed Exam Reports
        </h3>
      </div>

      <table className="w-full text-sm">
        <thead className="bg-gray-50 text-gray-600">
          <tr>
            <th className="text-left p-4">Exam</th>
            <th>Attempts</th>
            <th>Highest</th>
            <th>Average</th>
            <th>Last Attempt</th>
            <th>Status</th>
          </tr>
        </thead>

        <tbody>
          {reports.map((r, i) => (
            <tr key={i} className="border-t hover:bg-gray-50">
              <td className="p-4 font-medium">{r.exam}</td>
              <td align="center">{r.attempts}</td>
              <td align="center">{r.highest}%</td>
              <td align="center">{r.average}%</td>
              <td align="center">{r.lastAttempt}</td>
              <td align="center">
                <span className={`px-3 py-1 rounded-md text-xs ${r.status === "Passed"
                    ? "bg-green-100 text-green-700"
                    : "bg-red-100 text-red-700"
                  }`}>
                  {r.status}
                </span>
              </td>
            </tr>
          ))}
        </tbody>
      </table>

    </div>
  );
}