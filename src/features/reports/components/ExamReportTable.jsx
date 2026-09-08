export default function ExamReportTable() {

  const exams = [
    { name: "React Assessment", attempts: 320, avg: 78 },
    { name: "Python Coding", attempts: 280, avg: 72 },
    { name: "SQL Evaluation", attempts: 410, avg: 83 },
  ];

  return (
    <div className="bg-white border rounded-xl p-6">

      <h3 className="font-semibold mb-4">
        Exam Performance Report
      </h3>

      <table className="w-full text-sm">
        <thead className="text-gray-500 border-b">
          <tr>
            <th className="text-left py-2">Exam</th>
            <th>Attempts</th>
            <th>Average Score</th>
          </tr>
        </thead>

        <tbody>
          {exams.map((e,i)=>(
            <tr key={i} className="border-b">
              <td className="py-3">{e.name}</td>
              <td align="center">{e.attempts}</td>
              <td align="center">{e.avg}%</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}