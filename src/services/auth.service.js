import http from "../lib/axios";

const authService = {
  login: async (data) => {
    const res = await http.post("/admin/login", {
      email: data.email,
      password: data.password,
    });

    return res.data;
  },

  me: async () => {
    const res = await http.get("/admin/me");
    console.log("me data", res.data)
    return res.data;
  },
  logout: async () => {
    const res = await http.post("/logout");
    return res.data;
  },
};

export default authService;
