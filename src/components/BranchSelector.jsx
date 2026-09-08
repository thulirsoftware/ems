import { useState } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { MapPin, CheckCircle } from "lucide-react";
import { useBranchStore } from "../store/branchStore";

const branches = [
    { id: "chennai", label: "Chennai" },
    { id: "salem", label: "Salem" },
    { id: "madurai", label: "Madurai" },
    { id: "vilupuram", label: "Vilupuram" },
    { id: "trichy", label: "Trichy" },
    { id: "tirunelveli", label: "Tirunelveli" },
];

export default function BranchSelector() {
    const [open, setOpen] = useState(false);
    const { branch, setBranch } = useBranchStore();

    return (
        <>
            {/* Floating Button */}
            <motion.button
                onClick={() => setOpen(true)}
                initial={{ x: 40, opacity: 0 }}
                animate={{ x: 0, opacity: 1 }}
                whileHover={{ x: -4 }}
                whileTap={{ scale: 0.95 }}
                className="
          fixed right-2 md:right-3 top-1/2 -translate-y-1/2 z-50
          w-10 h-10 md:w-12 md:h-12
          rounded-full
          bg-gradient-to-br from-purple-600 to-indigo-700
          shadow-lg shadow-purple-500/40
          flex items-center justify-center
          text-white
        "
            >
                <MapPin size={18} />

                {branch && (
                    <span className="absolute -top-1 -right-1 w-2.5 h-2.5 bg-green-400 rounded-full ring-2 ring-white" />
                )}
            </motion.button>

            {/* Overlay */}
            <AnimatePresence>
                {open && (
                    <motion.div
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        exit={{ opacity: 0 }}
                        className="fixed inset-0 z-50 bg-black/40 flex justify-end"
                    >
                        <motion.div
                            initial={{ x: 420 }}
                            animate={{ x: 0 }}
                            exit={{ x: 420 }}
                            transition={{ type: "spring", stiffness: 120, damping: 18 }}
                            className="
                w-full sm:w-[70%] lg:w-[420px]
                h-full bg-white/90 backdrop-blur-xl
                shadow-2xl p-4 sm:p-6 lg:p-8
                relative border-l
              "
                        >
                            {/* Close */}
                            <button
                                onClick={() => setOpen(false)}
                                className="absolute top-3 right-3 text-xl text-gray-600 hover:text-black"
                            >
                                ✕
                            </button>

                            {/* Content */}
                            <div className="h-full flex flex-col justify-center">
                                <h2 className="text-xl sm:text-2xl font-bold text-center mb-6 sm:mb-10">
                                    Select Branch
                                </h2>

                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 max-h-[65vh] overflow-y-auto pr-1">
                                    {branches.map((b) => {
                                        const active = branch === b.id;

                                        return (
                                            <motion.button
                                                key={b.id}
                                                whileHover={{ scale: 1.03 }}
                                                whileTap={{ scale: 0.98 }}
                                                onClick={() => {
                                                    setBranch(b.id);
                                                    setOpen(false);
                                                }}
                                                className={`
                          relative w-full py-3 sm:py-4 rounded-xl border-2
                          text-base sm:text-lg font-semibold
                          transition-all duration-300
                          ${active
                                                        ? "border-purple-600 bg-purple-50 text-purple-700 shadow-md"
                                                        : "border-gray-200 hover:border-purple-400 hover:bg-purple-50"
                                                    }
                        `}
                                            >
                                                <div className="flex items-center justify-center gap-2">
                                                    <MapPin size={18} />
                                                    {b.label}
                                                </div>

                                                {active && (
                                                    <CheckCircle
                                                        size={20}
                                                        className="absolute right-3 top-1/2 -translate-y-1/2 text-purple-600"
                                                    />
                                                )}
                                            </motion.button>
                                        );
                                    })}
                                </div>

                                {branch && (
                                    <p className="mt-6 sm:mt-10 text-center text-green-600 text-sm">
                                        Showing data for <b>{branch.toUpperCase()}</b>
                                    </p>
                                )}
                            </div>
                        </motion.div>
                    </motion.div>
                )}
            </AnimatePresence>
        </>
    );
}
