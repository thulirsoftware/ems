import http from "../lib/axios";

const BatchService = {
    // Get all batches
    getBatches: async () => {
        const res = await http.get("/admin/batches");
        return res.data;
    },

    // Create batch
    createBatch: async (data) => {
        const res = await http.post("/admin/batches", data);
        return res.data;
    },

    // Get batch by ID
    getBatchById: async (id) => {
        const res = await http.get(`/admin/batches/${id}`);
        return res.data;
    },

    // Update batch
    updateBatch: async (id, data) => {
        const res = await http.put(`/admin/batches/${id}`, data);
        return res.data;
    },

    // Delete batch
    deleteBatch: async (id) => {
        const res = await http.delete(`/admin/batches/${id}`);
        return res.data;
    },

    // Get batches by assessment
    getBatchesByAssessment: async (assessmentId) => {
        const res = await http.get(
            `/admin/batches/assessment/${assessmentId}`
        );
        return res.data;
    },

    // Get users from batch
    getBatchUsers: async (id) => {
        const res = await http.get(`/admin/batches/${id}/users`);
        return res.data;
    },

    // Add users to batch
    addUsersToBatch: async (id, data) => {
        const res = await http.post(
            `/admin/batches/${id}/users`,
            data
        );
        return res.data;
    },

    // Remove users from batch
    removeUsersFromBatch: async (id, data) => {
        const res = await http.delete(
            `/admin/batches/${id}/users`,
            {
                data,
            }
        );
        return res.data;
    },
};

export default BatchService;