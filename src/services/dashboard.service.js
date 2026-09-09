import http from "../lib/axios";

const DashboardService = {
  getDashboard: async (limit) => {
    const res = await http.get("/user/dashboard", {
      params: limit ? { limit } : {},
    });
    return res.data;
  },
};

export default DashboardService;
