import { useEffect, useState } from "react";
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  Tooltip,
  ResponsiveContainer,
  CartesianGrid,
  Legend,
} from "recharts";
import CommonService from "../../services/common.service";

export default function TopProductPerformanceChart() {
  const [data, setData] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    load();
  }, []);

  const load = async () => {
    try {
      const res = await CommonService.getTopProductsPerformance();
      setData(mergeProducts(res.data));
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  /* merge duplicate products */
  const mergeProducts = (rows) => {
    const map = {};

    rows.forEach((r) => {
      if (!map[r.product_name]) {
        map[r.product_name] = {
          product: r.product_name,
          fy2425: 0,
          fy2526: 0,
        };
      }
      map[r.product_name].fy2425 += Number(r.fy_2425 || 0);
      map[r.product_name].fy2526 += Number(r.fy_2526 || 0);
    });

    return Object.values(map);
  };

  if (loading) return <div className="text-center p-6">Loading chart...</div>;

  return (
    <div className="bg-gradient-to-br from-white to-gray-50 rounded-2xl p-5 shadow border">
      <h3 className="font-semibold mb-4">Top Product Performance</h3>

      <ResponsiveContainer width="100%" height={320}>
        <BarChart data={data}>
          <CartesianGrid strokeDasharray="3 3" opacity={0.3} />
          <XAxis dataKey="product" hide />
          <YAxis />
          <Tooltip />
          <Legend />

          <Bar
            dataKey="fy2425"
            name="FY 2024–25"
            fill="#94a3b8"
            radius={[6, 6, 0, 0]}
            animationDuration={1200}
          />
          <Bar
            dataKey="fy2526"
            name="FY 2025–26"
            fill="#6366f1"
            radius={[6, 6, 0, 0]}
            animationDuration={1200}
          />
        </BarChart>
      </ResponsiveContainer>
    </div>
  );
}
