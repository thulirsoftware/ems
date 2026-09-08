import http from "../lib/axios";

const AIService = {
    chat: async (message) => {
        const res = await http.post("/admin/ai/chat", {
            message,
        });

        return res.data;
    },

    test: async (message) => {
        const res = await http.post("/admin/ai/test", {
            message,
        });

        return res.data;
    },

    status: async () => {
        const res = await http.get("/admin/ai/status");
        return res.data;
    },
};

export default AIService;