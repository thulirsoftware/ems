import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer } from "recharts";

const data = [
    { name: "Shirts", value: 12000 },
    { name: "T-shirts", value: 9000 },
    { name: "Kurthis", value: 7000 },
    { name: "Pants", value: 11000 },
    { name: "Tops", value: 6000 },
    { name: "Shorts", value: 8000 },
    { name: "Saree", value: 9000 }
];

export default function ProductBarChart() {
    return (
        <div className="w-full h-[350px] p-4">
            <h2 className="text-lg font-semibold mb-4">E-Commerce Sales by Products</h2>

            <ResponsiveContainer width="90%" height="90%">
                <BarChart data={data} barSize={35}>

                    {/* X Axis with Slanted Labels */}
                    <XAxis
                        dataKey="name"
                        angle={-30}
                        textAnchor="end"
                        interval={0}
                        tick={{ fontSize: 12 }}
                        axisLine={true}
                        tickLine={true}
                    />

                    {/* Y Axis with Lines */}
                    <YAxis
                        axisLine={true}
                        tickLine={true}
                        tick={{ fontSize: 12 }}
                    />

                    <Tooltip cursor={{ opacity: 0.1 }} />

                    <Bar
                        dataKey="value"
                        fill="#ec4899"
                        radius={[5, 5, 0, 0]}
                    />
                </BarChart>
            </ResponsiveContainer>
        </div>
    );
}
