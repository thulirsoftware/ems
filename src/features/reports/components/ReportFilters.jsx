import { Filter } from "lucide-react";
import ExportPDFButton from "./ExportPDFButton";
import ExportExcelButton from "./ExportExcelButton";

export default function ReportFilters() {
    return (
        <div className="bg-white border rounded-xl p-4 flex flex-wrap gap-4">

            <select className="border rounded-md px-3 py-2 text-sm">
                <option>All Exams</option>
            </select>

            <select className="border rounded-md px-3 py-2 text-sm">
                <option>All Students</option>
            </select>

            <input
                type="date"
                className="border rounded-md px-3 py-2 text-sm"
            />
            

            <button className="ml-auto flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-lg">
                <Filter size={16} />
                Apply
            </button>
        </div>
    );
}