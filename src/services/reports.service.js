import http from "../lib/axios";

const Reportservice = {
    getTopCustomersValuegrowth: async (branch = null) => {
        const res = await http.get("/top-customers-value-growth", {
            params: branch ? { branch } : {},
        });
        return res.data;
    },
    getTopCustomers: async (branch = null) => {
        const res = await http.get("/top-customers", {
            params: branch ? { branch } : {},
        });
        return res.data;
    },
    getTopProductsPerformance: async () => {
        const res = await http.get("/top-products-performance-comparison");
        return res.data;
    },

};

export default Reportservice;
