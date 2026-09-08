import { ArrowUpRight } from "lucide-react";

export default function StatCard({ title, value, trendText ,showArrow = true}) {
    return (
        <div
            className="
            p-4 rounded-xl w-full shadow-md
            transition-all duration-300 hover:shadow-lg
            "
            style={{
                background: "linear-gradient(90deg, var(--stat-card-bg-start), var(--stat-card-bg-end))",
                color: "var(--stat-card-text)",
            }}
        >
            <h3 className="text-sm opacity-90">{title}</h3>
            <p className="text-2xl font-semibold mt-1">{value}</p>
            <div className="flex items-center mt-2 text-xs opacity-90">
                {showArrow && <ArrowUpRight className="w-3 h-3 mr-1" />}
                {trendText}
            </div>
        </div>
    );
}
