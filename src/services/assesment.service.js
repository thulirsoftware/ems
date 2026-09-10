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

  getLibrary: async () => {
    const res = await http.get("/admin/assessments/library");
    return res.data;
  },

  getUpcoming: async () => {
    const res = await http.get("/admin/assessments/upcoming");
    return res.data;
  },

  getRunning: async () => {
    const res = await http.get("/admin/assessments/running");
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
  bulkStoreQuestions: async (assessmentId, file) => {
    const formData = new FormData();
    formData.append("file", file);

    const res = await http.post(
      `/admin/questions/bulk-store/${assessmentId}`,
      formData,
      { headers: { "Content-Type": "multipart/form-data" } }
    );
    return res.data;
  },

  // ================= CHOICES =================
  getChoices: async (questionId) => {
    const res = await http.get(`/admin/questions/${questionId}/choices`);
    return res.data;
  },
  createChoice: async (questionId, payload) => {
    const res = await http.post(
      `/admin/questions/${questionId}/choices`,
      payload
    );
    return res.data;
  },
  updateChoice: async (choiceId, payload) => {
    const res = await http.put(`/admin/choices/${choiceId}`, payload);
    return res.data;
  },
  deleteChoice: async (choiceId) => {
    await http.delete(`/admin/choices/${choiceId}`);
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



  // batchId is required by the backend whenever the assessment is
  // batch-wise (resolve_batch() 422s without it); harmless to omit for
  // non-batch-wise assessments, which resolve to their single implicit batch.
  getAssessmentUsersForEvaluation: async (assessmentId, batchId) => {
    const res = await http.get(`/admin/results/${assessmentId}/users`, {
      params: batchId ? { batch_id: batchId } : {},
    });
    return res.data;
  },
  getStudentAnswers: async (assessmentId, userId, batchId) => {
    const res = await http.get(
      `/admin/results/${assessmentId}/user/${userId}/answers`,
      { params: batchId ? { batch_id: batchId } : {} }
    );
    return res.data;
  },

  // grade answer
  gradeQuestion: async (assessmentId, userId, questionId, payload, batchId) => {
    const res = await http.post(
      `/admin/results/${assessmentId}/user/${userId}/question/${questionId}/grade`,
      payload,
      { params: batchId ? { batch_id: batchId } : {} }
    );
    return res.data;
  },
  getUserResult: async (assessmentId, userId, batchId) => {
    const res = await http.get(
      `/admin/results/${assessmentId}/user/${userId}`,
      { params: batchId ? { batch_id: batchId } : {} }
    );
    return res.data;
  },

  getFinishedAssessments: async () => {
    const res = await http.get("/admin/results/finished");
    return res.data;
  },
  getRankList: async (assessmentId, batchId) => {
    const res = await http.get(`/admin/results/${assessmentId}/rank-list`, {
      params: batchId ? { batch_id: batchId } : {},
    });
    return res.data;
  },

  // ================= RE-EXAM =================
  createReExam: async (payload) => {
    const res = await http.post("/admin/re-exam", payload);
    return res.data;
  },
  createFilteredReExam: async (payload) => {
    const res = await http.post("/admin/re-exam/filtered", payload);
    return res.data;
  },

};

export default AssessmentService;
