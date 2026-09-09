export default function Spinner({ size = 20, className = "" }) {
  return (
    <div
      role="status"
      aria-label="Loading"
      className={`inline-block animate-spin rounded-full border-2 border-current border-t-transparent text-purple-600 ${className}`}
      style={{ width: size, height: size }}
    />
  );
}

export function PageLoader({ label = "Loading..." }) {
  return (
    <div className="flex flex-col items-center justify-center gap-3 py-24 text-gray-400">
      <Spinner size={32} />
      <p className="text-sm">{label}</p>
    </div>
  );
}
