import { motion, AnimatePresence } from "framer-motion";
import { Eye, EyeOff } from "lucide-react";
import { useState, useEffect } from "react";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { zodResolver } from "@hookform/resolvers/zod";
import authService from "../../services/auth.service";
import { useAuthStore } from "../../store/authStore";
import { toast } from "sonner";
import { useNavigate } from "react-router-dom";

import logo1 from "../../assets/images/header-logo/thulir-logo-1.png";

import slide1 from "../../assets/images/posters/1.jpg";
import slide2 from "../../assets/images/posters/2.jpg";
import slide3 from "../../assets/images/posters/3.jpg";

const schema = z.object({
    email: z.string().email("Invalid email"),
    password: z.string().min(8, "Minimum 8 characters"),
});

const images = [slide1, slide2, slide3];

export default function Login() {
    const loginStore = useAuthStore((state) => state.login);
    const navigate = useNavigate();
    const [showPass, setShowPass] = useState(false);
    const [index, setIndex] = useState(0);

    const [loading, setLoading] = useState(false);
    useEffect(() => {
        const timer = setInterval(() => {
            setIndex((prev) => (prev + 1) % images.length);
        }, 4000);
        return () => clearInterval(timer);
    }, []);

    const {
        register,
        handleSubmit,
        formState: { errors },
    } = useForm({
        resolver: zodResolver(schema),
    });

    const onSubmit = async (data) => {
        if (loading) return;

        try {
            setLoading(true);

            const res = await authService.login(data);

            if (!res?.token) {
                throw new Error("Token not received");
            }

            const { token, admin } = res;

            loginStore(token, admin);
            toast.success("Login successful");

            navigate("/admin/dashboard", { replace: true });
        } catch (err) {
            if (!err.response) {
                toast.error("Network error. Please try again.");
            } else {
                toast.error(
                    err?.response?.data?.message || "Incorrect email or password"
                );
            }
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="w-full h-screen flex">

            {/* LEFT SLIDER */}
            <div className="hidden md:flex w-1/2 relative overflow-hidden">

                {/* LOGO TOP CENTER */}
                {/* LOGO TOP CENTER */}
                <motion.div
                    initial={{ opacity: 0, y: -30 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.8 }}
                    className="
    absolute 
    top-8 
    w-full 
    flex 
    justify-center 
    z-20
  "
                >
                    <div className="flex items-center gap-3">
                        <img src={logo1} alt="logo1" className="h-12" />
                        
                    </div>
                </motion.div>
                {/* IMAGE SLIDER */}
                <AnimatePresence mode="wait">
                    <motion.img
                        key={index}
                        src={images[index]}
                        alt="slide"
                        className="absolute w-full h-full object-cover"
                        initial={{ opacity: 0, scale: 1.1 }}
                        animate={{ opacity: 1, scale: 1 }}
                        exit={{ opacity: 0 }}
                        transition={{ duration: 1 }}
                    />
                </AnimatePresence>

                {/* OVERLAY */}
                <div className="absolute inset-0 bg-black/40 flex flex-col justify-end p-12 text-white">
                    <h2 className="text-3xl font-bold mb-2">
                        Take your career to the next level.
                    </h2>
                    <p className="text-gray-200">
                        Learn. Grow. Build your future with Technychemy.
                    </p>

                    {/* DOTS */}
                    <div className="flex gap-2 mt-6">
                        {images.map((_, i) => (
                            <span
                                key={i}
                                className={`w-3 h-3 rounded-full ${i === index ? "bg-white" : "bg-white/40"
                                    }`}
                            />
                        ))}
                    </div>
                </div>
            </div>

            {/* RIGHT LOGIN */}
            <div className="w-full md:w-1/2 flex items-center justify-center bg-[#f8fafc]">
                <motion.div
                    initial={{ opacity: 0, x: 80 }}
                    animate={{ opacity: 1, x: 0 }}
                    transition={{ duration: 0.7 }}
                    className="w-[90%] max-w-md bg-white p-10 rounded-2xl shadow-xl"
                >
                    <h1 className="text-3xl font-bold text-center mb-2 text-[#1e293b]">
                        Welcome
                    </h1>
                    <p className="text-center text-gray-500 mb-8">
                        Enter your login details to access your account
                    </p>

                    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
                        <input
                            placeholder="Email"
                            {...register("email")}
                            className="w-full px-4 py-3 rounded-lg bg-gray-100 border"
                        />
                        {errors.email && <p className="text-red-500 text-sm">{errors.email.message}</p>}

                        <div className="relative">
                            <input
                                type={showPass ? "text" : "password"}
                                placeholder="Password"
                                {...register("password")}
                                className="w-full px-4 py-3 rounded-lg bg-gray-100 border"
                            />
                            <button
                                type="button"
                                className="absolute right-4 top-3 text-gray-400"
                                onClick={() => setShowPass(!showPass)}
                            >
                                {showPass ? <EyeOff size={20} /> : <Eye size={20} />}
                            </button>
                        </div>

                        <motion.button
                            whileHover={!loading ? { scale: 1.03 } : {}}
                            whileTap={!loading ? { scale: 0.97 } : {}}
                            type="submit"
                            disabled={loading}
                            className={`
    w-full py-3 rounded-lg text-lg font-semibold
    ${loading ? "bg-gray-400 cursor-not-allowed" : "bg-[#1e3a8a] text-white"}
  `}
                        >
                            {loading ? "Logging in..." : "Log In"}
                        </motion.button>

                    </form>
                </motion.div>
            </div>
        </div>
    );
}
