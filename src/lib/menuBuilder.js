import {
  LayoutDashboard,
  Users,
  BarChart3,
  Sparkles,
} from "lucide-react";

export const useMenuBuilder = () => {
  return [
    {
      label: "Dashboard",
      path: "/dashboard",
      icon: LayoutDashboard,
    },
    {
      label: "Assessments",
      path: "/assesments",
      icon: Users,
    },
    {
      label: "Results",
      path: "/results",
      icon: Users,
    },
    {
      label: "Certificates",
      path: "/certificates",
      icon: Users,
    },
    {
      label: "Reports",
      path: "/reports",
      icon: BarChart3,
    },
    {
      label: "AI Assistant",
      path: "/ai",
      icon: Sparkles,
    },
  ];
};
