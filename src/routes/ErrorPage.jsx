import React from "react";
import { useAuthStore } from "../store/authStore";
import { useNavigate } from "react-router-dom";
import { motion } from "framer-motion";
import { AlertTriangle } from "lucide-react";

export default function ErrorPage() {
    const { token } = useAuthStore();
    const navigate = useNavigate();

    const handleGoBack = () => {
        if (token) {
            navigate("/dashboard");
        } else {
            navigate("/login");
        }
    };

    return (
        <div className="flex items-center justify-center min-h-screen bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-900 dark:to-gray-800">
            <motion.div
                initial={{ opacity: 0, scale: 0.9, y: 30 }}
                animate={{ opacity: 1, scale: 1, y: 0 }}
                transition={{ duration: 0.5, ease: "easeOut" }}
                className="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-10 max-w-md text-center border border-gray-200 dark:border-gray-700"
            >
                <motion.div
                    initial={{ rotate: -10 }}
                    animate={{ rotate: 0 }}
                    transition={{ delay: 0.3, duration: 0.4 }}
                    className="flex justify-center"
                >
                    <AlertTriangle className="w-16 h-16 text-yellow-500 animate-bounce" />
                </motion.div>

                <h1 className="mt-4 text-2xl font-bold text-gray-800 dark:text-gray-200">
                    Oops! Something went wrong.
                </h1>

                <p className="mt-2 text-gray-600 dark:text-gray-400">
                    {token
                        ? "You are logged in, but this page does not exist."
                        : "You are not logged in. Please login to continue."}
                </p>

                <motion.button
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    transition={{ delay: 0.5 }}
                    whileHover={{ scale: 1.05 }}
                    whileTap={{ scale: 0.95 }}
                    onClick={handleGoBack}
                    className="mt-6 px-6 py-3 rounded-xl font-semibold bg-gradient-to-r from-purple-600 to-purple-500 hover:from-purple-700 hover:to-purple-600 text-white shadow-md hover:shadow-lg transition-all duration-200"
                >
                    {token ? "Go to Dashboard" : "Go to Login"}
                </motion.button>
            </motion.div>
        </div>
    );
}
