import {
  LayoutDashboard,
  Users,
  GraduationCap,
  ClipboardList,
  FileBarChart2,
  Bot,
} from "lucide-react";

export const useMenuBuilder = () => {
  return [
    {
      label: "Dashboard",
      path: "/admin/dashboard",
      icon: LayoutDashboard,
    },
    {
      label: "Users",
      path: "/admin/users",
      icon: Users,
    },
    {
      label: "Batches",
      path: "/admin/batch",
      icon: GraduationCap,
    },
    {
      label: "Assessments",
      path: "/admin/assesments",
      icon: ClipboardList,
    },
    {
      label: "Reports",
      path: "/admin/reports",
      icon: FileBarChart2,
    },
    {
      label: "AI Interface",
      path: "/admin/ai-interface",
      icon: Bot,
    },
  ];
};