import {
    BarChart,
    Bar,
    XAxis,
    YAxis,
    Tooltip,
    ResponsiveContainer,
    Cell,
    ReferenceLine,
} from "recharts";

export default function SubjectChart() {

    const data = [
        { subject: "Frontend Evaluation", marks: 86 },
        { subject: "Backend API Test", marks: 72 },
        { subject: "Problem Solving", marks: 80 },
        { subject: "Data Structures", marks: 77 },
        { subject: "Logical Reasoning", marks: 68 },
        { subject: "Technical MCQ", marks: 90 },
    ];

    // different colors per subject
    const colors = [
        "#6366f1",
        "#22c55e",
        "#f59e0b",
        "#ef4444",
    ];

    const avg =
        data.reduce((a, b) => a + b.marks, 0) / data.length;

    return (
        <div className="bg-white rounded-2xl shadow-sm p-6 h-[380px]">

            {/* HEADER */}
            <div className="flex justify-between items-center mb-4">
                <div>
                    <h3 className="font-semibold text-lg">
                        Subject Performance
                    </h3>
                    <p className="text-sm text-gray-500">
                        Your marks comparison by subject
                    </p>
                </div>

                <span className="text-sm bg-green-100 text-green-600 px-3 py-1 rounded-full">
                    Avg {avg.toFixed(0)}%
                </span>
            </div>

            {/* CHART */}
            <ResponsiveContainer width="100%" height="85%">
                <BarChart data={data} barSize={45}>

                    <XAxis
                        dataKey="subject"
                        tick={{ fontSize: 13 }}
                    />

                    <YAxis />

                    <Tooltip
                        contentStyle={{
                            borderRadius: "12px",
                            border: "none",
                            boxShadow: "0 8px 20px rgba(0,0,0,0.1)",
                        }}
                    />

                    {/* Average Line */}
                    <ReferenceLine
                        y={avg}
                        stroke="#94a3b8"
                        strokeDasharray="4 4"
                        label="Average"
                    />

                    <Bar
                        dataKey="marks"
                        radius={[12, 12, 0, 0]}
                        animationDuration={1200}
                    >
                        {data.map((entry, index) => (
                            <Cell
                                key={`cell-${index}`}
                                fill={colors[index % colors.length]}
                            />
                        ))}
                    </Bar>

                </BarChart>
            </ResponsiveContainer>

        </div>
    );
}