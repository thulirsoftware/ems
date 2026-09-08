import http from "../lib/axios";

const AssessmentService = {
  // ================= ASSESSMENTS =================
  AssessmentList: async (page = 1, perPage = 10) => {
    const res = await http.get(
      `/admin/assessments?page=${page}&per_page=${perPage}`
    );
    return res.data;
  },

  getAssessmentTypes: async () => {
    const res = await http.get("/admin/assessment-types");
    return res.data;
  },


  createAssessment: async (payload) => {
    const res = await http.post("/admin/assessments", payload);
    return res.data;
  },
  updateAssessment: async (id, payload) => {
    const res = await http.put(`/admin/assessments/${id}`, payload);
    return res.data;
  },
  deleteAssessment: async (id) => {
    await http.delete(`/admin/assessments/${id}`);
  },


  // ================= QUESTIONS =================

  getQuestions: async (assessmentId) => {
    const res = await http.get(
      `/admin/assessments/${assessmentId}/questions`
    );
    return res.data;
  },

  // ✅ ONLY METHOD NEEDED TO CREATE QUESTION + CHOICES
  createQuestionWithChoices: async (assessmentId, payload) => {
    const res = await http.post(
      `/admin/assessments/${assessmentId}/questions/with-choices`,
      payload
    );
    return res.data;
  },

  updateQuestionWithChoices: async (questionId, payload) => {
    const res = await http.put(
      `/admin/questions/${questionId}/with-choices`,
      payload
    );
    return res.data;
  },

  deleteQuestion: async (questionId) => {
    await http.delete(`/admin/questions/${questionId}`);
  },
  getAssessmentWithQuestions: async (id) => {
    const res = await http.get(`/admin/assessments/${id}/questions`);
    return res.data;
  },
  getAssessmentWithQuestionsChoices: async (id) => {
    const res = await http.get(`/admin/assessments/${id}/questions/with-choices`);
    return res.data;
  },
  getAssessmentById: async (id) => {
    const res = await http.get(`/admin/assessments/${id}`);
    return res.data;
  },
  getQuestionWithChoices: async (id) => {
    const res = await http.get(`/admin/assessments/${id}/questions/with-choices`);
    return res.data;
  },
  getUsers: async () => {
    const res = await http.get("/admin/users");
    return res.data;
  },

  // ================= ASSIGN =================
  assignAssessment: async (payload) => {
    const res = await http.post("/admin/assignments", payload);
    return res.data;
  },
  // ================= ASSIGNMENTS =================
  getUsersWithAssignmentStatus: async (assessmentId) => {
    const res = await http.get(
      `/admin/assignments/users-with-assignment-status`,
      { params: { assessment_id: assessmentId } }
    );
    return res.data;
  },

  removeAssignment: async (payload) => {
    const res = await http.delete("/admin/assignments", {
      data: payload,
    });
    return res.data;
  },
  // ✅ CREATE DESCRIPTIVE QUESTION (NO OPTIONS)
  createQuestion: async (assessmentId, payload) => {
    const res = await http.post(
      `/admin/assessments/${assessmentId}/questions`,
      payload
    );
    return res.data;
  },
  // update descriptive question
  updateQuestion: async (id, payload) => {
    const res = await http.put(`/admin/questions/${id}`, payload);
    return res.data;
  },



  getAssessmentUsersForEvaluation: async (assessmentId) => {
    const res = await http.get(`/admin/results/${assessmentId}/users`);
    return res.data;
  },
  getStudentAnswers: async (assessmentId, userId) => {
    const res = await http.get(
      `/admin/results/${assessmentId}/user/${userId}/answers`
    );
    return res.data;
  },

  // grade answer
  gradeQuestion: async (assessmentId, userId, questionId, payload) => {
    const res = await http.post(
      `/admin/results/${assessmentId}/user/${userId}/question/${questionId}/grade`,
      payload
    );
    return res.data;
  },
  getUserResult: async (assessmentId, userId) => {
    const res = await http.get(
      `/admin/results/${assessmentId}/user/${userId}`
    );
    return res.data;
  },

};

export default AssessmentService;
