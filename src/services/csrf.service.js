// services/csrf.service.js
import http from "../lib/axios";

export const getCsrfToken = async () => {
  const res = await http.get("/csrf-token");
  return res.data.token;
};
