import { Download, FileText } from "lucide-react";

export default function ReportHeader() {
  return (
    <div className="flex justify-between items-center">

      <div>
        <h1 className="text-2xl font-semibold text-gray-800">
          Reports
        </h1>
        <p className="text-gray-500 text-sm">
          Analyze your exam Reports and progress history
        </p>
      </div>

      <div className="flex gap-3">
        <button className="flex items-center gap-2 border px-4 py-2 rounded-lg hover:bg-gray-100">
          <FileText size={18}/>
          Generate Report
        </button>

        <button className="flex items-center gap-2 bg-gray-900 text-white px-4 py-2 rounded-lg">
          <Download size={18}/>
          Export PDF
        </button>
      </div>

    </div>
  );
}