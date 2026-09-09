import { NavLink } from "react-router-dom";
import { motion } from "framer-motion";
import { X } from "lucide-react";
import { useEffect } from "react";
import { useSidebarStore } from "../../../store/sidebarStore";
import { useMenuBuilder } from "../../../lib/menuBuilder";
import { cn } from "../../../lib/utils";
import logo_img from '../../../assets/images/header-logo/thulir-logo-1.png'
import "../Sidebar/Sidebar.css";

export default function Sidebar() {
  // stores
  const { isOpen, toggleSidebar, closeSidebar, openSidebar } = useSidebarStore();

  const menu = useMenuBuilder();

  // keep sidebar open on desktop
  useEffect(() => {
    const handleResize = () => {
      if (window.innerWidth >= 1024) {
        openSidebar();
      }
    };

    window.addEventListener("resize", handleResize);
    return () => window.removeEventListener("resize", handleResize);
  }, [openSidebar]);

  return (
    <>
      {/* MOBILE BACKDROP */}
      {isOpen && (
        <div
          onClick={closeSidebar}
          className="fixed inset-0 bg-black/40 lg:hidden z-40"
        />
      )}

      {/* SIDEBAR */}
      <motion.aside
        initial={{ x: -260 }}
        animate={{
          x: isOpen ? 0 : -260,
          width: isOpen ? 260 : 0,
        }}
        transition={{ duration: 0.25 }}
        className="
          unique-sidebar fixed top-0 left-0 h-screen shadow-xl
          z-50 border-r overflow-hidden flex flex-col
          bg-[hsl(var(--sidebar-bg))] text-[color:var(--sidebar-text)]
          lg:w-[260px] lg:translate-x-0 lg:static
        "
      >
        {/* HEADER */}
        <div className="flex items-center bg-white justify-between p-4 border-b">
          <div className="text-xl font-semibold">
            <img src={logo_img} alt="" />
            </div>

          <button
            onClick={toggleSidebar}
            className="p-2 hover:bg-gray-100 rounded-lg lg:hidden"
          >
            <X size={20} />
          </button>
        </div>

        {/* MENU */}
        <nav className="flex-1 overflow-y-auto">
          {menu.map((item) => (
            <NavLink
              key={item.path}
              to={item.path}
              onClick={() => window.innerWidth < 1024 && closeSidebar()}
              className={({ isActive }) =>
                cn(
                  "flex items-center gap-3 p-3 rounded-md mx-3 my-1",
                  "hover:text-[hsl(var(--sidebar-hover-text))] hover:bg-[hsl(var(--sidebar-hover-bg))] transition-all",
                  isActive &&
                    "text-[hsl(var(--sidebar-hover-text))] bg-[hsl(var(--sidebar-hover-bg))] font-semibold"
                )
              }
            >
              <item.icon size={20} />
              <span>{item.label}</span>
            </NavLink>
          ))}
        </nav>
      </motion.aside>
    </>
  );
}
