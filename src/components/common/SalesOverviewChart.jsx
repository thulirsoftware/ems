import {
    AreaChart,
    Area,
    XAxis,
    YAxis,
    Tooltip,
    ResponsiveContainer,
    CartesianGrid,
} from "recharts";

const salesData = [
    { month: "Apr", pink: 40, purple: 20 },
    { month: "May", pink: 80, purple: 40 },
    { month: "Jun", pink: 300, purple: 120 },
    { month: "Jul", pink: 250, purple: 190 },
    { month: "Aug", pink: 500, purple: 250 },
    { month: "Sep", pink: 400, purple: 340 },
    { month: "Oct", pink: 350, purple: 360 },
    { month: "Nov", pink: 200, purple: 300 },
    { month: "Dec", pink: 480, purple: 390 },
];

export default function SalesOverviewChart() {
    return (
        <>
            <h2 className="text-lg font-semibold mb-4">Sales Overview</h2>
            
                <ResponsiveContainer width="100%" height="90%">
                    <AreaChart data={salesData} margin={{ top: 20, right: 20, left: 0, bottom: 20 }}>

                        {/* Smooth background grid */}
                        <CartesianGrid strokeDasharray="3 3" opacity={0.2} />

                        {/* Modern X-axis */}
                        <XAxis
                            dataKey="month"
                            tick={{ fontSize: 12, fill: "#555" }}
                            axisLine={false}
                            tickLine={false}
                        />

                        <YAxis
                            tick={{ fontSize: 12, fill: "#555" }}
                            axisLine={false}
                            tickLine={false}
                        />

                        {/* Modern tooltip */}
                        <Tooltip
                            contentStyle={{
                                borderRadius: "10px",
                                border: "1px solid #ddd",
                                boxShadow: "0 4px 12px rgba(0,0,0,0.15)",
                            }}
                        />

                        {/* Pink Gradient */}
                        <defs>
                            <linearGradient id="pinkGradient" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="10%" stopColor="#ec4899" stopOpacity={0.7} />
                                <stop offset="95%" stopColor="#ec4899" stopOpacity={0.1} />
                            </linearGradient>

                            {/* Purple Gradient */}
                            <linearGradient id="purpleGradient" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="10%" stopColor="#8b5cf6" stopOpacity={0.7} />
                                <stop offset="95%" stopColor="#8b5cf6" stopOpacity={0.1} />
                            </linearGradient>
                        </defs>

                        {/* Smooth, modern lines */}
                        <Area
                            type="monotone"
                            dataKey="pink"
                            stroke="#ec4899"
                            fill="url(#pinkGradient)"
                            strokeWidth={3}
                        />

                        <Area
                            type="monotone"
                            dataKey="purple"
                            stroke="#8b5cf6"
                            fill="url(#purpleGradient)"
                            strokeWidth={3}
                        />
                    </AreaChart>
                </ResponsiveContainer>




        </>


    );
}
