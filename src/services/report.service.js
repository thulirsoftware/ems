import http from "../lib/axios";

// Every user report endpoint shares the same filter query params:
// assessment_id, batch_id, status, passing_percentage, from, to, page, page_size.
const ReportService = {
  getSummary: async (params = {}) => {
    const res = await http.get("/user/reports/summary", { params });
    return res.data;
  },

  getAssessmentsReport: async (params = {}) => {
    const res = await http.get("/user/reports/assessments", { params });
    return res.data;
  },

  getAttemptsReport: async (params = {}) => {
    const res = await http.get("/user/reports/attempts", { params });
    return res.data;
  },

  getProgressReport: async (params = {}) => {
    const res = await http.get("/user/reports/progress", { params });
    return res.data;
  },

  getQuestionsReport: async (params = {}) => {
    const res = await http.get("/user/reports/questions", { params });
    return res.data;
  },
};

export default ReportService;
