import http from "../lib/axios";

const AssessmentService = {
  // ================= ASSESSMENTS =================
  UpcomingAssessmentList: async () => {
    const res = await http.get(`/user/assessments/upcoming`);
    return res.data;
  },

  TodaysAssessmentList: async () => {
    const res = await http.get(`/user/assessments/today`);
    return res.data;
  },

  RunningAssessmentList: async () => {
    const res = await http.get(`/user/assessments/running`);
    return res.data;
  },

  CompletedAssessmentList: async () => {
    const res = await http.get(`/user/assessments/completed`);
    return res.data;
  },

  MissedAssessmentList: async () => {
    const res = await http.get(`/user/assessments/missed`);
    return res.data;
  },

  GetAssessmentById: async (id) => {
    const res = await http.get(`/user/assessments/${id}`);
    return res.data;
  },

  GetAssessmentQuestions: async (id) => {
    const res = await http.get(`/user/assessments/${id}/questions`);
    return res.data;
  },

  // ================= ATTEMPT FLOW =================

  // 🔹 START EXAM (create / resume attempt)
  StartAssessment: async (id) => {
    const res = await http.post(`/user/assessments/${id}/start`);
    return res.data;
  },
  SaveAnswer: async (assessmentId, payload) => {
    /**
     payload format:
     {
        question_id: number,
        choice_id: number
     }
     */
    const res = await http.post(
      `/user/assessments/${assessmentId}/answer`,
      payload
    );
    return res.data;
  },

  // 🔹 SUBMIT EXAM
  SubmitAssessment: async (id, payload) => {
    const res = await http.post(
      `/user/assessments/${id}/submit`,
      payload
    );
    return res.data;
  },
  GetAssessmentResult: async (
    assessmentId,
    page = 1,
    pageSize = 10
  ) => {
    const res = await http.post(
      `/user/assessments/${assessmentId}/result`,
      {},
      {
        params: {
          page,
          page_size: pageSize,
        },
      }
    );

    return res.data;
  },

};

export default AssessmentService;
