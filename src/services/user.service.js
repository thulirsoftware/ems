import http from "../lib/axios";

const UserService = {
    // Get users list
    UserList: async () => {
        const res = await http.get("/admin/users");
        return res.data;
    },

    CreateUser: async (payload) => {
        const res = await http.post("/admin/users", payload);
        return res.data;
    },

    BulkImportUsers: async (file) => {
        const formData = new FormData();
        formData.append("file", file);

        const res = await http.post("/admin/users/bulk-assign", formData, {
            headers: { "Content-Type": "multipart/form-data" },
        });
        return res.data;
    },
};

export default UserService;
