import http from "../lib/axios";

const AIService = {
  getConversations: async () => {
    const res = await http.get("/user/ai/conversations");
    return res.data;
  },
  getConversation: async (id) => {
    const res = await http.get(`/user/ai/conversations/${id}`);
    return res.data;
  },
  createConversation: async () => {
    const res = await http.post("/user/ai/conversations");
    return res.data;
  },
  sendMessage: async (conversationId, message) => {
    const res = await http.post(`/user/ai/conversations/${conversationId}/chat`, { message });
    return res.data;
  },
  deleteConversation: async (id) => {
    const res = await http.delete(`/user/ai/conversations/${id}`);
    return res.data;
  },
};

export default AIService;
