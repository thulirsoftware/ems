import http from "../lib/axios";

const AIService = {
  getConversations: async () => {
    const res = await http.get("/admin/ai/conversations");
    return res.data;
  },
  getConversation: async (id) => {
    const res = await http.get(`/admin/ai/conversations/${id}`);
    return res.data;
  },
  createConversation: async () => {
    const res = await http.post("/admin/ai/conversations");
    return res.data;
  },
  sendMessage: async (conversationId, message) => {
    const res = await http.post(`/admin/ai/conversations/${conversationId}/chat`, { message });
    return res.data;
  },
  deleteConversation: async (id) => {
    const res = await http.delete(`/admin/ai/conversations/${id}`);
    return res.data;
  },
};

export default AIService;
