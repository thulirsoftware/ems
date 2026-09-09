import { AlertTriangle, RotateCw } from "lucide-react";

export default function ErrorState({
  title = "Something went wrong",
  description = "We couldn't load this data. Please try again.",
  onRetry,
  className = "",
}) {
  return (
    <div className={`flex flex-col items-center justify-center gap-3 py-16 text-center ${className}`}>
      <AlertTriangle className="w-9 h-9 text-red-400" />
      <div>
        <p className="text-sm font-medium text-gray-700">{title}</p>
        <p className="text-xs text-gray-400 max-w-xs mt-1">{description}</p>
      </div>
      {onRetry && (
        <button
          onClick={onRetry}
          className="flex items-center gap-2 mt-1 px-4 py-2 rounded-lg bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium transition"
        >
          <RotateCw size={14} />
          Retry
        </button>
      )}
    </div>
  );
}
