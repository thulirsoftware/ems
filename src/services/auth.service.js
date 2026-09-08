import http from "../lib/axios";

const authService = {
  /* ---------------- AUTH ---------------- */

  login: async (data) => {
    const res = await http.post("/user/login", {
      email: data.email,
      password: data.password,
    });
    return res.data;
  },

  register: async (data) => {
    const res = await http.post("/user/register", {
      name: data.name,
      email: data.email,
      password: data.password,
    });
    return res.data;
  },

  /* ---------------- EMAIL VERIFICATION ---------------- */

  sendVerificationCode: async (email) => {
    const res = await http.post("/user/send-verification-code", {
      email,
    });
    return res.data;
  },

  verifyEmail: async ({ email, code }) => {
    const res = await http.post("/user/verify-email", {
      email,
      code,
    });
    return res.data;
  },

  /* ---------------- USER ---------------- */

  me: async () => {
    const res = await http.get("/user/me");
    return res.data;
  },

  logout: async () => {
    const res = await http.post("/user/logout");
    return res.data;
  },
  verifyEmail: async ({ email, code }) => {
    const res = await http.post("/user/verify-email", { email, code });
    return res.data;
  }
};

export default authService;
