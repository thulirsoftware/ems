import axios from "axios";
import { useAuthStore } from "../store/authStore";

// The backend issues a Bearer token on login (Passport personal access
// token) — every /api/v1/user/* call authenticates with that header, so
// this instance never needs cookies/CSRF.
const http = axios.create({
  baseURL: import.meta.env.VITE_API_URL || "http://127.0.0.1:8000/api/v1",
});

http.interceptors.request.use((config) => {
  const token = useAuthStore.getState().token;

  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  return config;
});

http.interceptors.response.use(
  (response) => response,
  (error) => {
    // Only treat this as "session expired" when we actually had a token —
    // a bad-password 401 on /user/login must not trigger a logout loop.
    if (error.response?.status === 401 && useAuthStore.getState().token) {
      useAuthStore.getState().logout();
      if (window.location.pathname !== "/login") {
        window.location.assign("/login");
      }
    }
    return Promise.reject(error);
  }
);

export default http;
