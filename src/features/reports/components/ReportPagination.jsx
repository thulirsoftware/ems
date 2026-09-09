import { ChevronLeft, ChevronRight } from "lucide-react";

export default function ReportPagination({
  currentPage = 1,
  totalPages = 1,
  total = 0,
  onPageChange,
}) {
  if (totalPages <= 1) return null;

  return (
    <div className="flex items-center justify-between px-6 py-4 border-t text-sm text-gray-500">
      <span>
        Page {currentPage} of {totalPages} — {total} total
      </span>

      <div className="flex items-center gap-2">
        <button
          disabled={currentPage <= 1}
          onClick={() => onPageChange(currentPage - 1)}
          className="p-2 rounded-md border disabled:opacity-40 hover:bg-gray-50"
        >
          <ChevronLeft size={16} />
        </button>
        <button
          disabled={currentPage >= totalPages}
          onClick={() => onPageChange(currentPage + 1)}
          className="p-2 rounded-md border disabled:opacity-40 hover:bg-gray-50"
        >
          <ChevronRight size={16} />
        </button>
      </div>
    </div>
  );
}
