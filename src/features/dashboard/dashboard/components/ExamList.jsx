export default function ExamList() {

  const exams = [
    { name: "React Assessment", date: "20 Feb" },
    { name: "JavaScript Test", date: "25 Feb" },
    { name: "DBMS Quiz", date: "28 Feb" },
  ];

  return (
    <div className="card">
      <h3>Upcoming Exams</h3>

      {exams.map((e, i) => (
        <div key={i} className="list-item">
          <span>{e.name}</span>
          <b>{e.date}</b>
        </div>
      ))}
    </div>
  );
}