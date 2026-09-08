export default function StudentReportTable() {

  const students = [
    { name:"Arun", exam:"React", score:82, status:"Pass" },
    { name:"Priya", exam:"Python", score:61, status:"Fail" },
    { name:"Kiran", exam:"SQL", score:91, status:"Pass" },
  ];

  return (
    <div className="bg-white border rounded-xl p-6">

      <h3 className="font-semibold mb-4">
        Student Performance Report
      </h3>

      <table className="w-full text-sm">
        <thead className="border-b text-gray-500">
          <tr>
            <th className="text-left py-2">Student</th>
            <th>Exam</th>
            <th>Score</th>
            <th>Status</th>
          </tr>
        </thead>

        <tbody>
          {students.map((s,i)=>(
            <tr key={i} className="border-b">
              <td className="py-3">{s.name}</td>
              <td align="center">{s.exam}</td>
              <td align="center">{s.score}%</td>
              <td align="center">
                <span className={`px-3 py-1 rounded text-xs ${
                  s.status==="Pass"
                  ?"bg-green-100 text-green-600"
                  :"bg-red-100 text-red-600"
                }`}>
                  {s.status}
                </span>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}