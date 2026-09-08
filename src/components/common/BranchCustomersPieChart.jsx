import { useEffect, useState } from "react";
import {
  PieChart,
  Pie,
  Cell,
  Tooltip,
  Legend,
  ResponsiveContainer,
} from "recharts";
import CommonService from "../../services/common.service";

const COLORS = ["#6366f1", "#22c55e", "#f59e0b", "#ef4444"];

export default function BranchCustomersPieChart() {
  const [data, setData] = useState([]);
  const [loading, setLoading] = useState(true);
  const [activeIndex, setActiveIndex] = useState(null);

  useEffect(() => {
    load();
  }, []);

  const load = async () => {
    try {
      const res = await CommonService.getBranchCustomers();
      setData(res.data);
    } catch (e) {
      console.error("Pie chart error", e);
    } finally {
      setLoading(false);
    }
  };

  const renderLabel = ({ percent }) =>
    `${(percent * 100).toFixed(1)}%`;

  if (loading) return <div className="p-6 text-center">Loading chart...</div>;

  return (
    <div className="bg-gradient-to-br from-white to-gray-50 rounded-2xl p-6 shadow border">
      <h3 className="font-semibold mb-4">Customers by Branch</h3>

      <ResponsiveContainer width="100%" height={320}>
        <PieChart>
          <Pie
            data={data}
            dataKey="count"
            nameKey="branch"
            cx="50%"
            cy="50%"
            outerRadius={110}
            innerRadius={55}
            paddingAngle={4}
            isAnimationActive
            animationDuration={1200}
            onMouseEnter={(_, index) => setActiveIndex(index)}
            onMouseLeave={() => setActiveIndex(null)}
          >
            {data.map((entry, index) => (
              <Cell
                key={index}
                fill={COLORS[index % COLORS.length]}
                strokeWidth={activeIndex === index ? 4 : 1}
              />
            ))}
          </Pie>

          <Tooltip formatter={(v) => `${v} Customers`} />
          <Legend verticalAlign="bottom" height={36} />
        </PieChart>
      </ResponsiveContainer>
    </div>
  );
}
