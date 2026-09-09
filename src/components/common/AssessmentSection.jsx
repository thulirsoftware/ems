import { AlertCircle, RefreshCw } from "lucide-react";

function CardSkeleton() {
  return (
    <div className="rounded-xl border border-gray-200 p-5 bg-white space-y-3 animate-pulse">
      <div className="flex justify-between items-start gap-3">
        <div className="h-4 bg-gray-200 rounded w-2/3" />
        <div className="h-5 bg-gray-200 rounded-full w-16" />
      </div>
      <div className="h-3 bg-gray-200 rounded w-full" />
      <div className="h-3 bg-gray-200 rounded w-1/2" />
      <div className="h-9 bg-gray-200 rounded mt-4" />
    </div>
  );
}

export default function AssessmentSection({
  title,
  titleColor = "text-gray-700",
  loading,
  error,
  onRetry,
  isEmpty,
  emptyMessage = "Nothing here right now",
  children,
}) {
  return (
    <section>
      <h2 className={`text-lg font-semibold mb-4 ${titleColor}`}>{title}</h2>

      {loading ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {[0, 1, 2].map((i) => (
            <CardSkeleton key={i} />
          ))}
        </div>
      ) : error ? (
        <div className="flex items-center justify-between gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-red-700">
          <div className="flex items-center gap-3">
            <AlertCircle size={20} className="shrink-0" />
            <p className="text-sm">Couldn't load this section.</p>
          </div>
          {onRetry && (
            <button
              onClick={onRetry}
              className="flex items-center gap-1 text-sm font-medium hover:underline shrink-0"
            >
              <RefreshCw size={14} />
              Retry
            </button>
          )}
        </div>
      ) : isEmpty ? (
        <p className="text-gray-500 italic">{emptyMessage}</p>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {children}
        </div>
      )}
    </section>
  );
}
