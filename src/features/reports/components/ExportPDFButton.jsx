import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";
import { Download } from "lucide-react";

export default function ExportPDFButton() {

  const exportPDF = () => {
    const doc = new jsPDF();

    doc.text("Student Performance Report", 14, 15);

    autoTable(doc, {
      startY: 25,
      head: [["Student", "Exam", "Score", "Status"]],
      body: [
        ["Arun", "React", "82%", "Pass"],
        ["Priya", "Python", "61%", "Fail"],
        ["Kiran", "SQL", "91%", "Pass"],
      ],
    });

    doc.save("exam-report.pdf");
  };

  return (
    <button
      onClick={exportPDF}
      className="flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-lg"
    >
      <Download size={16}/>
      Export PDF
    </button>
  );
}