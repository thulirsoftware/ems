import http from "../lib/axios";

const ReportService = {

    getReports: async (params = {}) => {
        const res = await http.get("/admin/reports", {
            params
        });

        return res.data;
    }

};

export default ReportService;