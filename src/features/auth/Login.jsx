import { motion, AnimatePresence } from "framer-motion";
import { Eye, EyeOff } from "lucide-react";
import { useState, useEffect, useRef } from "react";
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

/* ---------------- SCHEMAS ---------------- */

const loginSchema = z.object({
    email: z.string().email("Invalid email"),
    password: z.string().min(4),
});

const registerSchema = z.object({
    name: z.string().min(2),
    email: z.string().email(),
    password: z.string().min(4),
});

const images = [slide1, slide2, slide3];

export default function Login() {
    const loginStore = useAuthStore((state) => state.login);
    const navigate = useNavigate();

    const [mode, setMode] = useState("signin"); // signin | signup | otp
    const [showPass, setShowPass] = useState(false);
    const [index, setIndex] = useState(0);
    const [loading, setLoading] = useState(false);

    const [otp, setOtp] = useState(Array(6).fill(""));
    const otpRefs = useRef([]);
    const [otpEmail, setOtpEmail] = useState("");

    const schema = mode === "signin" ? loginSchema : registerSchema;

    const {
        register,
        handleSubmit,
        formState: { errors },
        reset,
    } = useForm({
        resolver: zodResolver(schema),
    });

    /* ---------------- SLIDER ---------------- */

    useEffect(() => {
        const timer = setInterval(() => {
            setIndex((prev) => (prev + 1) % images.length);
        }, 4000);
        return () => clearInterval(timer);
    }, []);

    /* ---------------- SUBMIT ---------------- */

    const onSubmit = async (data) => {
        if (loading) return;

        try {
            setLoading(true);

            if (mode === "signin") {
                const res = await authService.login(data);
                loginStore(res.token, res.user);
                toast.success("Login successful");
                navigate("/dashboard", { replace: true });
            }

            if (mode === "signup") {
                await authService.register(data);
                await authService.sendVerificationCode(data.email);

                setOtpEmail(data.email);
                setMode("otp");
                toast.success("Verification code sent to your email");
                reset();
            }
        } catch (err) {
            toast.error(err?.response?.data?.message || "Something went wrong");
        } finally {
            setLoading(false);
        }
    };

    /* ---------------- OTP HANDLING ---------------- */

    const handleOtpChange = (value, index) => {
        if (!/^[0-9]?$/.test(value)) return;

        const newOtp = [...otp];
        newOtp[index] = value;
        setOtp(newOtp);

        if (value && index < 5) {
            otpRefs.current[index + 1].focus();
        }
    };

    const handleVerifyOtp = async () => {
        const code = otp.join("");

        if (code.length !== 6) {
            toast.error("Enter 6 digit verification code");
            return;
        }

        try {
            setLoading(true);

            const res = await authService.verifyEmail({
                email: otpEmail,
                code,
            });

            // Optional: backend may return token here
            if (res?.token) {
                loginStore(res.token, res.user);
            }

            toast.success("Email verified successfully");
            navigate("/dashboard", { replace: true });
        } catch (err) {
            toast.error(err?.response?.data?.message || "Invalid code");
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
                        Learn. Grow. Build your future with Exam management system.
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

            {/* RIGHT */}
            <div className="w-full md:w-1/2 flex items-center justify-center bg-[#f8fafc]">
                <div className="w-[90%] max-w-md bg-white p-10 rounded-2xl shadow-xl">

                    {/* OTP SCREEN */}
                    {mode === "otp" && (
                        <>
                            <h2 className="text-2xl font-bold text-center mb-2">
                                Verify Email
                            </h2>
                            <p className="text-center text-gray-500 mb-6">
                                Enter the 6 digit code sent to <br />
                                <b>{otpEmail}</b>
                            </p>

                            <div className="flex justify-center gap-3 mb-6">
                                {otp.map((digit, i) => (
                                    <input
                                        key={i}
                                        ref={(el) => (otpRefs.current[i] = el)}
                                        maxLength={1}
                                        value={digit}
                                        onChange={(e) =>
                                            handleOtpChange(e.target.value, i)
                                        }
                                        className="w-12 h-12 text-center text-lg border rounded-lg"
                                    />
                                ))}
                            </div>

                            <button
                                onClick={handleVerifyOtp}
                                disabled={loading}
                                className="w-full py-3 rounded-lg bg-[#1e3a8a] text-white font-semibold"
                            >
                                {loading ? "Verifying..." : "Verify & Continue"}
                            </button>
                        </>
                    )}

                    {/* SIGN IN / SIGN UP */}
                    {mode !== "otp" && (
                        <>
                            <h1 className="text-3xl font-bold text-center mb-6">
                                {mode === "signin" ? "Welcome Back" : "Create Account"}
                            </h1>



                            <form onSubmit={handleSubmit(onSubmit)} className="space-y-5">

                                {mode === "signup" && (
                                    <input
                                        placeholder="Name"
                                        {...register("name")}
                                        className="w-full px-4 py-3 rounded-lg bg-gray-100 border"
                                    />
                                )}

                                <input
                                    placeholder="Email"
                                    {...register("email")}
                                    className="w-full px-4 py-3 rounded-lg bg-gray-100 border"
                                />

                                <div className="relative">
                                    <input
                                        type={showPass ? "text" : "password"}
                                        placeholder="Password"
                                        {...register("password")}
                                        className="w-full px-4 py-3 rounded-lg bg-gray-100 border"
                                    />
                                    <button
                                        type="button"
                                        className="absolute right-4 top-3"
                                        onClick={() => setShowPass(!showPass)}
                                    >
                                        {showPass ? <EyeOff /> : <Eye />}
                                    </button>
                                </div>

                                <button
                                    disabled={loading}
                                    className="w-full py-3 rounded-lg bg-[#1e3a8a] text-white font-semibold"
                                >
                                    {loading ? "Please wait..." : mode === "signin" ? "Sign In" : "Register"}
                                </button>
                            </form>
                            <div className="mt-6 text-center text-sm text-gray-600">
                                {mode === "signin" ? (
                                    <>
                                        Don’t have an account?{" "}
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setMode("signup");
                                                reset();
                                            }}
                                            className="text-[#1e3a8a] font-semibold hover:underline"
                                        >
                                            Sign Up
                                        </button>
                                    </>
                                ) : (
                                    <>
                                        Already have an account?{" "}
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setMode("signin");
                                                reset();
                                            }}
                                            className="text-[#1e3a8a] font-semibold hover:underline"
                                        >
                                            Sign In
                                        </button>
                                    </>
                                )}
                            </div>

                        </>
                    )}
                </div>
            </div>
        </div>
    );
}
