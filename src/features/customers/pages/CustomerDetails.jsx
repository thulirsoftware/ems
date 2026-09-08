import { useParams } from "react-router-dom";
import { useEffect, useState } from "react";
import CustomerService from "../../../services/customer.service";
import PageHeader from "../../../components/common/PageHeader";
import { TrendingUp, TrendingDown } from "lucide-react";
import {
    LineChart,
    Line,
    XAxis,
    YAxis,
    Tooltip,
    ResponsiveContainer,
    BarChart,
    Bar,
    CartesianGrid,
} from "recharts";

/* ---------------- CARD ---------------- */
const ChartCard = ({ title, children }) => (
    <div
        className="
      bg-gray-50 dark:bg-gray-800 text-[hsl(var(--table-body-text))] backdrop-blur
      rounded-2xl shadow-lg border
      p-5 transition-all duration-500
      hover:shadow-xl hover:-translate-y-1
    "
    >
        <h3 className="font-semibold mb-4">{title}</h3>
        {children}
    </div>
);

export default function CustomerDetails() {
    const { id } = useParams();
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        loadDetails();
    }, []);

    const loadDetails = async () => {
        try {
            const res = await CustomerService.getById(id);
            setData(res);
        } finally {
            setLoading(false);
        }
    };

    if (loading) return <div className="p-6">Loading...</div>;

    const {
        customer,
        product_analysis = [],
        sales_analysis = {},
        value_analysis = {},
    } = data;

    const salesData = Object.values(sales_analysis)
        .flat()
        .map((s) => ({
            date: s.date,
            amount: Number(s.sales_amount),
        }));

    const productChart = product_analysis.map((p) => ({
        name: p.product,
        value: Number(p.FY2025_26_Total ?? 0),
    }));

    const growthColor = (val) => {
        if (!val) return "text-gray-500";
        return val > 0 ? "text-green-600" : "text-red-600";
    };


    return (
        <section className="space-y-8">
            <PageHeader
                title={customer.customer_name}
                subtitle={`Customer Code: ${customer.customer_code}`}
            />

            {/* ---------------- CUSTOMER INFO ---------------- */}
            <div
                className="
          bg-gradient-to-br from-white to-gray-100
          dark:from-gray-900 dark:to-gray-800
          bg-gray-50 dark:bg-gray-800 text-[hsl(var(--table-body-text))]
          p-6 rounded-2xl border shadow-lg
          grid grid-cols-1 md:grid-cols-3 gap-4
          transition-all duration-500 hover:shadow-xl
        "
            >
                <Info label="Branch" value={customer.branch} />
                <Info label="GST" value={customer.gst_number} />
                <Info label="Mobile" value={customer.mobile_number || "-"} />
                <Info label="Status" value={customer.status} />
                <Info label="Start Date" value={customer.start_date} />
                <Info label="Location" value={customer.customer_location} full />
            </div>

            {/* ---------------- CHARTS ---------------- */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* SALES TREND */}
                <ChartCard title="Sales Trend">
                    <ResponsiveContainer width="100%" height={260}>
                        <LineChart
                            data={salesData}
                            margin={{ top: 10, right: 20, left: -10, bottom: 0 }}
                        >
                            <CartesianGrid strokeDasharray="3 3" opacity={0.3} />
                            <XAxis dataKey="date" />
                            <YAxis />
                            <Tooltip />
                            <Line
                                type="monotone"
                                dataKey="amount"
                                stroke="#2563eb"
                                strokeWidth={3}
                                dot={{ r: 5 }}
                                activeDot={{ r: 8 }}
                                isAnimationActive
                                animationDuration={1200}
                                animationEasing="ease-out"
                            />
                        </LineChart>
                    </ResponsiveContainer>
                </ChartCard>

                {/* PRODUCT CONTRIBUTION */}
                <ChartCard title="Product Contribution 2025-2026">
                    <ResponsiveContainer width="100%" height={260}>
                        <BarChart data={productChart}>
                            <CartesianGrid strokeDasharray="3 3" opacity={0.3} />
                            <XAxis dataKey="name" />
                            <YAxis />
                            <Tooltip />
                            <Bar
                                dataKey="value"
                                fill="#6366f1"
                                radius={[8, 8, 0, 0]}
                                isAnimationActive
                                animationDuration={1200}
                                animationEasing="ease-out"
                            />
                        </BarChart>
                    </ResponsiveContainer>
                </ChartCard>
            </div>

            {/* ---------------- VALUE ANALYSIS ---------------- */}
            {/* ---------------- VALUE ANALYSIS ---------------- */}
            {value_analysis && Object.keys(value_analysis).length > 0 && (
                <div className="bg-white dark:bg-gray-900 rounded-2xl border shadow-lg overflow-hidden">
                    <h3 className="p-4 font-semibold border-b bg-gray-50 dark:bg-gray-800 dark:text-white">
                        Value Analysis
                    </h3>

                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 dark:bg-gray-800 text-[hsl(var(--table-head-text))]">
                            <tr >
                                <th className="px-6 py-3 text-left">Type</th>
                                <th className="px-6 py-3 text-center">2024–25</th>
                                <th className="px-6 py-3 text-center">2025–26</th>
                                <th className="px-6 py-3 text-right">Growth %</th>
                            </tr>
                        </thead>

                        <tbody className="divide-y dark:text-white">
                            <tr >
                                <td className="px-6 py-4 font-medium">Amount</td>
                                <td className="px-6 py-4 text-center">
                                    ₹{value_analysis?.amount_2024_25 ?? "-"}
                                </td>
                                <td className="px-6 py-4 text-center">
                                    ₹{value_analysis?.amount_2025_26 ?? "-"}
                                </td>
                                <td className="px-6 py-4 text-right">
                                    <GrowthBadge value={value_analysis?.growth_percent} />
                                </td>
                            </tr>

                            <tr>
                                <td className="px-6 py-4 font-medium">Average</td>
                                <td className="px-6 py-4 text-center">
                                    ₹{value_analysis?.avg_2024_25 ?? "-"}
                                </td>
                                <td className="px-6 py-4 text-center">
                                    ₹{value_analysis?.avg_2025_26 ?? "-"}
                                </td>
                                <td className="px-6 py-4 text-right">
                                    <GrowthBadge value={value_analysis?.growth_percent} />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            )}



            {/* ---------------- PRODUCT TABLE ---------------- */}
            <AnalysisTable
                title="Product Analysis"
                headers={["Product", "FY 24–25", "FY 25–26", "Growth %"]}
                rows={product_analysis.map((p) => [
                    p.product,
                    p.FY2024_25_Total ?? "-",
                    p.FY2025_26_Total ?? "-",
                    <span className={growthColor(p.growth_percent)}>
                        {p.growth_percent ?? "-"}
                    </span>,
                ])}
            />

            {/* ---------------- SALES TABLE ---------------- */}
            <AnalysisTable
                title="Sales Analysis"
                headers={["Date", "Invoice", "Product", "Qty", "Amount"]}
                rows={Object.values(sales_analysis)
                    .flat()
                    .map((s) => [
                        s.date,
                        s.inv_no,
                        s.product_name,
                        s.qty_in_kg,
                        s.sales_amount,
                    ])}
            />
        </section>
    );
}

/* ---------------- HELPERS ---------------- */

const Info = ({ label, value, full }) => (
    <div className={full ? "md:col-span-3" : ""}>
        <p className="text-xs text-gray-500">{label}</p>
        <p className="font-medium">{value}</p>
    </div>
);

const Row = ({ label, value, children }) => (
    <tr>
        <td className="px-4 py-2 text-gray-500">{label}</td>
        <td className="px-4 py-2 text-right font-medium">
            {children || value}
        </td>
    </tr>
);
const GrowthBadge = ({ value }) => {
    const val = Number(value);

    if (isNaN(val)) return <span className="text-gray-400">-</span>;

    return (
        <span
            className={`
        inline-flex items-center justify-center
        px-3 py-1 rounded-full text-xs font-semibold
        ${val > 0
                    ? "bg-green-100 text-green-700"
                    : "bg-red-100 text-red-700"}
      `}
        >
            {val}%
        </span>
    );
};


const AnalysisTable = ({ title, headers, rows }) => (
    <div
        className="
      bg-gradient-to-br from-white to-gray-50
      dark:from-gray-900 dark:to-gray-800
      rounded-2xl shadow-lg border overflow-hidden
    "
    >
        <h3 className="p-4 font-semibold border-b bg-white/60 dark:bg-gray-900/60 backdrop-blur dark:text-white">
            {title}
        </h3>

        <div className="overflow-auto max-h-[400px]">
            <table className="w-full text-sm">
                <thead
                    className="
            sticky top-0 z-10
            bg-gray-50 dark:bg-gray-800 text-[hsl(var(--table-head-text))]
          "
                >
                    <tr>
                        {headers.map((h) => (
                            <th key={h} className="px-6 py-3 text-left font-semibold">
                                {h}
                            </th>
                        ))}
                    </tr>
                </thead>

                <tbody className="divide-y divide-gray-200/60 dark:divide-gray-700/60">
                    {rows.map((r, i) => (
                        <tr
                            key={i}
                            className="
                group transition-all duration-300
                bg-gray-50 dark:bg-gray-800 text-[hsl(var(--table-body-text))]
              "
                        >
                            {r.map((c, j) => (
                                <td key={j} className="px-6 py-3 relative">
                                    {j === 0 && (
                                        <span
                                            className="
                        absolute left-0 top-0 h-full w-1 bg-indigo-500
                        scale-y-0 group-hover:scale-y-100 transition-transform origin-top
                      "
                                        />
                                    )}
                                    {c}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    </div>
);
