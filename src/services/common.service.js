import http from "../lib/axios";

const CommonService = {
    getStats: async () => {
        const res = await http.get("/stats");
        return res.data;
    },
    getTopProductsPerformance: async () => {
        const res = await http.get("/top-products-performance-comparison");
        return res.data;
    },
    getBranchCustomers: async () => {
        const res = await http.get("/branches-pie-chart");
        return res.data;
    },

};

export default CommonService;
