import { TrendingUp, TrendingDown } from "lucide-react";

export default function AdvancedStatCard({ data }) {
  const Icon = data.icon;

  const isPositive = Number(data.trend ?? 0) >= 0;

  return (
    <div className="relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl">

      {/* Background Glow */}
      <div
        className={`absolute -right-10 -top-10 h-40 w-40 rounded-full opacity-15 blur-3xl ${data.glow}`}
      />

      <div className="relative flex items-start justify-between">

        {/* Left */}
        <div className="flex-1">

          <p className="text-sm font-medium text-gray-500">
            {data.title}
          </p>

          <h2 className="mt-2 text-3xl font-bold tracking-tight text-gray-900">
            {data.value}
          </h2>

          {data.subtitle && (
            <p className="mt-1 text-sm text-gray-500">
              {data.subtitle}
            </p>
          )}

          {data.trend !== undefined && (
            <div
              className={`mt-4 inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ${
                isPositive
                  ? "bg-emerald-100 text-emerald-700"
                  : "bg-red-100 text-red-700"
              }`}
            >
              {isPositive ? (
                <TrendingUp size={14} className="mr-1" />
              ) : (
                <TrendingDown size={14} className="mr-1" />
              )}

              {Math.abs(data.trend)}%

              <span className="ml-1 font-normal">
                vs last month
              </span>
            </div>
          )}

        </div>

        {/* Icon */}
        <div
          className={`flex h-14 w-14 items-center justify-center rounded-2xl text-white shadow-lg ${data.bg}`}
        >
          <Icon size={26} />
        </div>

      </div>
    </div>
  );
}