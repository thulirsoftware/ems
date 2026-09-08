import {
    BarChart,
    Bar,
    LineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
    Legend,
} from "recharts";

export default function VendorSalesHeatWave() {
    const data = [
        { vendor: "Vendor A", sales: 120000, growth: 12 },
        { vendor: "Vendor B", sales: 65000, growth: 4 },
        { vendor: "Vendor C", sales: 150000, growth: 18 },
        { vendor: "Vendor D", sales: 40000, growth: 2 },
    ];

    return (
        <div className="bg-white dark:bg-gray-900 rounded-2xl shadow p-6">
            <h2 className="text-xl font-semibold mb-4">Vendor Sales HeatWave Chart</h2>

            <ResponsiveContainer width="100%" height={250}>
                <BarChart data={data}>
                    <defs>
                        <linearGradient id="heatColor" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stopColor="#FF5733" />
                            <stop offset="50%" stopColor="#FFC300" />
                            <stop offset="100%" stopColor="#DAF7A6" />
                        </linearGradient>
                    </defs>

                    <CartesianGrid strokeDasharray="3 3" opacity={0.2} />
                    <XAxis dataKey="vendor" />
                    <YAxis />
                    <Tooltip
                        contentStyle={{
                            background: "#fff",
                            borderRadius: "10px",
                            border: "1px solid #ddd",
                        }}
                        formatter={(value, name) =>
                            name === "sales"
                                ? [`₹${value.toLocaleString()}`, "Total Sales"]
                                : [`${value}%`, "Growth"]
                        }
                    />
                    <Legend />

                    {/* HEATWAVE BAR */}
                    <Bar
                        dataKey="sales"
                        fill="url(#heatColor)"
                        radius={[6, 6, 0, 0]}
                    />

                    {/* GROWTH LINE */}
                    <Line
                        type="monotone"
                        dataKey="growth"
                        stroke="#1E90FF"
                        strokeWidth={3}
                        dot={{ r: 5 }}
                        activeDot={{ r: 7 }}
                    />
                </BarChart>
            </ResponsiveContainer>
        </div>
    );
}
