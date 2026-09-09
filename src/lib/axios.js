import axios from "axios";
import { useAuthStore } from "../store/authStore";

const http = axios.create({
    baseURL: import.meta.env.PROD
        ? "https://seashell-okapi-169452.hostingersite.com/ems/public/api/v1"
        : "http://127.0.0.1:8000/api/v1",
    withCredentials: true,
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
        // Only force a logout when a previously-authenticated session gets
        // rejected (expired/revoked token) — not when the /admin/login
        // request itself fails, since the store has no token at that point
        // and Login.jsx already shows that error to the user.
        if (error.response?.status === 401) {
            const { token, logout } = useAuthStore.getState();

            if (token) {
                logout();

                if (window.location.pathname !== "/login") {
                    window.location.href = "/login";
                }
            }
        }

        return Promise.reject(error);
    }
);

export default http;