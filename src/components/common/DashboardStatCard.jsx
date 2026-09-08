import { motion } from "framer-motion";

export default function DashboardStatCard({ title, value, icon }) {
    return (
        <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.4 }}
            className="bg-white shadow-md rounded-xl p-5 flex items-center justify-between"
        >
            <div>
                <p className="text-gray-600 text-sm">{title}</p>
                <h2 className="text-2xl font-bold">{value}</h2>
                
            </div>
            <div className="bg-gradient-to-br from-purple-500 to-purple-500 p-3 rounded-xl text-white text-2xl" style={{
                background: "linear-gradient(90deg, var(--stat-card-bg-start), var(--stat-card-bg-end))",
                color: "var(--stat-card-text)",
            }}>
                {icon}
            </div>
        </motion.div>
    );
}
