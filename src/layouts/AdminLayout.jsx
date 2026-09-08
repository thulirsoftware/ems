import Sidebar from "../components/layout/Sidebar/Sidebar";
import Header from "../components/layout/Header/Header";
import { Outlet } from "react-router-dom";

export default function AdminLayout() {
  return (
    <div className="flex w-full h-screen overflow-hidden">
      {/* Sidebar */}
      <Sidebar />

      {/* RIGHT SIDE */}
      <div className="flex flex-col flex-1 overflow-hidden">
        
        {/* HEADER */}
        <Header />

        {/* PAGE CONTENT */}
        <main className="flex-1 overflow-y-auto bg-[hsl(var(--background))] p-6">
          <Outlet />
        </main>

      </div>
    </div>
  );
}