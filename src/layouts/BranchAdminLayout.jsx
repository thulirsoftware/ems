import Sidebar from "../components/layout/Sidebar/Sidebar";

import { useSidebarStore } from "../store/sidebarStore";
import { Outlet } from "react-router-dom";

export default function BrachAdminLayout() {
    const { isOpen } = useSidebarStore();

    return (
        <div className="flex w-full h-screen overflow-hidden">
            {/* Sidebar - FIXED  */}
            <Sidebar />

            {/* Main Content - SCROLLABLE ONLY */}
            <div className="flex-1 overflow-y-auto bg-[hsl(var(--background))] p-6">
                <Outlet />
            </div>
        </div>
    );
}
