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
    // Backend returns the admin record directly at the top level
    // (auth('admins')->user()), not wrapped in { admin: ... }.
    const res = await http.get("/admin/me");
    return res.data;
  },
  logout: async () => {
    const res = await http.post("/admin/logout");
    return res.data;
  },
};

export default authService;
