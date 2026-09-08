import BranchSelector from "../components/BranchSelector";
import Sidebar from "../components/layout/Sidebar/Sidebar";

import { useSidebarStore } from "../store/sidebarStore";
import { useAuthStore } from "../store/authStore";
import { Outlet } from "react-router-dom";

export default function AdminLayout() {
  const { isOpen } = useSidebarStore();

  const { role, branch } = useAuthStore();
  console.log("role:",role);


  const isSuperAdmin = role === "super_admin";

  const isBranchAdmin = role === "admin" && branch;

  return (
    <div className="flex w-full h-screen overflow-hidden">
      {/* Sidebar - FIXED */}
      <Sidebar />

      {/* Main Content - SCROLLABLE ONLY */}
      <div className="flex-1 overflow-y-auto bg-[hsl(var(--background))] p-6">
        <Outlet />
      </div>

      {/* Branch selector only for super admin */}
      {isSuperAdmin && <BranchSelector />}
    </div>
  );
}
