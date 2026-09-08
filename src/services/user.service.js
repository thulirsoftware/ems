import http from "../lib/axios";

const UserService = {
    // Get users list
    UserList: async () => {
        const res = await http.get("/users");
        return res.data;
    },

    CreateUser: async (payload) => {
        const res = await http.post("/users", payload);
        return res.data;
    },
};

export default UserService;
