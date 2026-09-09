import http from "../lib/axios";

// Every admin report endpoint shares the same filter query params:
// assessment_id, batch_id, user_id, assessment_type, status,
// passing_percentage, from, to, page, page_size.
const ReportService = {
    getSummary: async (params = {}) => {
        const res = await http.get("/admin/reports/summary", { params });
        return res.data;
    },

    getAssessmentsReport: async (params = {}) => {
        const res = await http.get("/admin/reports/assessments", { params });
        return res.data;
    },

    getBatchesReport: async (params = {}) => {
        const res = await http.get("/admin/reports/batches", { params });
        return res.data;
    },

    getUsersReport: async (params = {}) => {
        const res = await http.get("/admin/reports/users", { params });
        return res.data;
    },

    getQuestionsReport: async (params = {}) => {
        const res = await http.get("/admin/reports/questions", { params });
        return res.data;
    },

    getAttemptsReport: async (params = {}) => {
        const res = await http.get("/admin/reports/attempts", { params });
        return res.data;
    },
};

export default ReportService;
