import {
    ChevronLeft, ChevronRight,
    Moon,
    Sun,
    ChevronDown,
    User,
    LogOut,
    Settings,
} from "lucide-react";
import { useSidebarStore } from "../../../store/sidebarStore";
import { useAuthStore } from "../../../store/authStore";
import { useThemeStore } from "../../../store/themeStore";
import { useNavigate } from "react-router-dom";
import { useState, useRef, useEffect } from "react";

export default function Header() {
    const navigate = useNavigate();

    // stores
    const { isOpen, toggleSidebar } = useSidebarStore();
    const { admin, logout } = useAuthStore();
    const { theme, toggleTheme } = useThemeStore();

    const [openProfile, setOpenProfile] = useState(false);
    const profileRef = useRef(null);

    // close dropdown outside click
    useEffect(() => {
        const handler = (e) => {
            if (profileRef.current && !profileRef.current.contains(e.target)) {
                setOpenProfile(false);
            }
        };

        document.addEventListener("mousedown", handler);
        return () => document.removeEventListener("mousedown", handler);
    }, []);


    // auth user details
    const userName = admin?.name || "Admin";
    const userInitial = userName.charAt(0).toUpperCase();

    return (
        <header
            className="
        h-16 px-6 flex items-center justify-between
        border-b bg-white shadow-sm
      "
        >
            {/* LEFT SIDE */}
            <div className="flex items-center gap-3">
                {/* SIDEBAR TOGGLE */}
                <button
                    onClick={toggleSidebar}
                    className="p-2 rounded-lg hover:bg-gray-100 transition"
                >
                    {isOpen ? <ChevronLeft size={22} /> : <ChevronRight size={22} />}
                </button>

                
            </div>

            {/* RIGHT SIDE */}
            <div className="flex items-center gap-4">
                
                {/* THEME TOGGLE */}
                <button
                    onClick={toggleTheme}
                    className="p-2 rounded-lg hover:bg-gray-100 transition"
                >
                    {theme === "light" ? (
                        <Moon size={20} />
                    ) : (
                        <Sun size={20} className="text-yellow-500" />
                    )}
                </button>

                {/* PROFILE */}
                <div className="relative" ref={profileRef}>
                    <div
                        onClick={() => setOpenProfile(!openProfile)}
                        className="
              flex items-center gap-3 cursor-pointer
              hover:bg-gray-100 px-3 py-2 rounded-lg transition
            "
                    >
                        <div className="w-9 h-9 rounded-full bg-indigo-500 text-white flex items-center justify-center font-semibold">
                            {userInitial}
                        </div>

                        {/* Name */}
                        <div className="hidden md:block text-left">
                            <p className="text-sm font-medium">{userName}</p>
                        </div>

                        <ChevronDown size={18} />
                    </div>

                    {/* DROPDOWN */}
                    {openProfile && (
                        <div
                            className=" 
                absolute right-0 mt-2 w-52
                bg-white border rounded-xl
                shadow-lg overflow-hidden z-50
              "
                        >
                            <button
                                onClick={() => navigate("/profile")}
                                className="flex items-center gap-2 w-full px-4 py-3 hover:bg-gray-100"
                            >
                                <User size={16} />
                                Profile
                            </button>
                            <button
                                onClick={() => navigate("/profile")}
                                className="flex items-center gap-2 w-full px-4 py-3 hover:bg-gray-100"
                            >
                                <Settings size={16} />
                                Settings
                            </button>

                            <button
                                onClick={() => {
                                    logout();
                                    navigate("/login");
                                }}
                                className="flex items-center gap-2 w-full px-4 py-3 hover:bg-red-50 text-red-500"
                            >
                                <LogOut size={16} />
                                Logout
                            </button>
                        </div>
                    )}
                </div>
            </div>
        </header>
    );
}