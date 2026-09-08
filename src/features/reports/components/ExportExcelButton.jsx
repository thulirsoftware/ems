import * as XLSX from "xlsx";
import { saveAs } from "file-saver";
import { FileSpreadsheet } from "lucide-react";

export default function ExportExcelButton() {

  const exportExcel = () => {

    const data = [
      { Student: "Arun", Exam: "React", Score: 82, Status: "Pass" },
      { Student: "Priya", Exam: "Python", Score: 61, Status: "Fail" },
      { Student: "Kiran", Exam: "SQL", Score: 91, Status: "Pass" },
    ];

    const worksheet = XLSX.utils.json_to_sheet(data);
    const workbook = XLSX.utils.book_new();

    XLSX.utils.book_append_sheet(workbook, worksheet, "Report");

    const excelBuffer = XLSX.write(workbook, {
      bookType: "xlsx",
      type: "array",
    });

    saveAs(
      new Blob([excelBuffer]),
      "exam-report.xlsx"
    );
  };

  return (
    <button
      onClick={exportExcel}
      className="flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-lg"
    >
      <FileSpreadsheet size={16}/>
      Export Excel
    </button>
  );
}