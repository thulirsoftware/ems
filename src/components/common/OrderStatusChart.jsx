import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip } from "recharts";

const orderData = [
    { name: "Delivered", value: 540 },
    { name: "Pending", value: 120 },
    { name: "Cancelled", value: 60 },
    { name: "Refunded", value: 30 },
];

const COLORS = ["#10b981", "#3b82f6", "#f59e0b", "#ef4444"];

export default function OrderStatusChart() {
    return (
        <div className="w-full h-full">
            <h2 className="text-lg font-semibold mb-4">Order Status Overview</h2>

            <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                    <Pie
                        data={orderData}
                        cx="50%"
                        cy="50%"
                        innerRadius={60}
                        outerRadius={90}
                        paddingAngle={3}
                        dataKey="value"
                    >
                        {orderData.map((entry, index) => (
                            <Cell key={`cell-${index}`} fill={COLORS[index]} />
                        ))}
                    </Pie>

                    <Tooltip 
                        contentStyle={{
                            borderRadius: "10px",
                            border: "1px solid #ddd",
                            boxShadow: "0 4px 12px rgba(0,0,0,0.1)",
                        }}
                    />
                </PieChart>
            </ResponsiveContainer>
        </div>
    );
}
