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
    const [resendCooldown, setResendCooldown] = useState(0);

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

    useEffect(() => {
        if (resendCooldown <= 0) return;
        const timer = setInterval(() => {
            setResendCooldown((s) => (s <= 1 ? 0 : s - 1));
        }, 1000);
        return () => clearInterval(timer);
    }, [resendCooldown]);

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
                setResendCooldown(30);
                toast.success("Verification code sent to your email");
                reset();
            }
        } catch (err) {
            const status = err?.response?.status;
            const message = err?.response?.data?.message || "Something went wrong";

            // Backend rejects login with 403 until the email is verified —
            // send the user back into the OTP flow instead of a dead-end error.
            if (mode === "signin" && status === 403) {
                toast.error(message);
                setOtpEmail(data.email);
                setMode("otp");
            } else {
                toast.error(message);
            }
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

    const handleOtpKeyDown = (e, index) => {
        if (e.key === "Backspace" && !otp[index] && index > 0) {
            otpRefs.current[index - 1].focus();
        }
    };

    const handleOtpPaste = (e) => {
        const pasted = e.clipboardData.getData("text").replace(/\D/g, "").slice(0, 6);
        if (!pasted) return;
        e.preventDefault();

        const newOtp = Array(6).fill("");
        pasted.split("").forEach((digit, i) => {
            newOtp[i] = digit;
        });
        setOtp(newOtp);
        otpRefs.current[Math.min(pasted.length, 5)]?.focus();
    };

    const handleResendOtp = async () => {
        if (resendCooldown > 0) return;

        try {
            await authService.sendVerificationCode(otpEmail);
            setOtp(Array(6).fill(""));
            otpRefs.current[0]?.focus();
            setResendCooldown(30);
            toast.success("Verification code resent");
        } catch (err) {
            toast.error(err?.response?.data?.message || "Failed to resend code");
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

            await authService.verifyEmail({
                email: otpEmail,
                code,
            });

            // Backend's verify-email endpoint only confirms the email — it
            // never issues a token, so the user still has to sign in.
            toast.success("Email verified! Please sign in.");
            setMode("signin");
            reset({ email: otpEmail, password: "" });
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
                                        inputMode="numeric"
                                        autoComplete="one-time-code"
                                        maxLength={1}
                                        value={digit}
                                        onChange={(e) =>
                                            handleOtpChange(e.target.value, i)
                                        }
                                        onKeyDown={(e) => handleOtpKeyDown(e, i)}
                                        onPaste={handleOtpPaste}
                                        className="w-12 h-12 text-center text-lg border rounded-lg"
                                    />
                                ))}
                            </div>

                            <button
                                onClick={handleVerifyOtp}
                                disabled={loading}
                                className="w-full py-3 rounded-lg bg-[#1e3a8a] text-white font-semibold disabled:opacity-60"
                            >
                                {loading ? "Verifying..." : "Verify & Continue"}
                            </button>

                            <div className="mt-4 text-center text-sm text-gray-600">
                                Didn't get the code?{" "}
                                <button
                                    type="button"
                                    onClick={handleResendOtp}
                                    disabled={resendCooldown > 0}
                                    className="text-[#1e3a8a] font-semibold hover:underline disabled:text-gray-400 disabled:no-underline disabled:cursor-not-allowed"
                                >
                                    {resendCooldown > 0
                                        ? `Resend in ${resendCooldown}s`
                                        : "Resend code"}
                                </button>
                            </div>
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
                                    <div>
                                        <input
                                            placeholder="Name"
                                            autoComplete="name"
                                            aria-invalid={!!errors.name}
                                            {...register("name")}
                                            className={`w-full px-4 py-3 rounded-lg bg-gray-100 border ${errors.name ? "border-red-500" : ""
                                                }`}
                                        />
                                        {errors.name && (
                                            <p className="text-sm text-red-600 mt-1">{errors.name.message}</p>
                                        )}
                                    </div>
                                )}

                                <div>
                                    <input
                                        placeholder="Email"
                                        type="email"
                                        autoComplete="email"
                                        aria-invalid={!!errors.email}
                                        {...register("email")}
                                        className={`w-full px-4 py-3 rounded-lg bg-gray-100 border ${errors.email ? "border-red-500" : ""
                                            }`}
                                    />
                                    {errors.email && (
                                        <p className="text-sm text-red-600 mt-1">{errors.email.message}</p>
                                    )}
                                </div>

                                <div>
                                    <div className="relative">
                                        <input
                                            type={showPass ? "text" : "password"}
                                            placeholder="Password"
                                            autoComplete={mode === "signin" ? "current-password" : "new-password"}
                                            aria-invalid={!!errors.password}
                                            {...register("password")}
                                            className={`w-full px-4 py-3 rounded-lg bg-gray-100 border ${errors.password ? "border-red-500" : ""
                                                }`}
                                        />
                                        <button
                                            type="button"
                                            aria-label={showPass ? "Hide password" : "Show password"}
                                            className="absolute right-4 top-3 text-gray-500"
                                            onClick={() => setShowPass(!showPass)}
                                        >
                                            {showPass ? <EyeOff size={20} /> : <Eye size={20} />}
                                        </button>
                                    </div>
                                    {errors.password && (
                                        <p className="text-sm text-red-600 mt-1">{errors.password.message}</p>
                                    )}
                                </div>

                                <button
                                    disabled={loading}
                                    className="w-full py-3 rounded-lg bg-[#1e3a8a] text-white font-semibold disabled:opacity-60"
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
