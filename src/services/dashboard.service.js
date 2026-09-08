import http from "../lib/axios";

const DashboardService = {
  // ================= DASHBOARD =================

  getDashboard: async () => {
    const res = await http.get("/admin/dashboard");
    return res.data;
  },

 
};

export default DashboardService;