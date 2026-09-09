import { useEffect, useState } from "react";
import { useAuthStore } from "../../store/authStore";
import authService from "../../services/auth.service";

export default function AuthProvider({ children }) {
  const token = useAuthStore((s) => s.token);
  const admin = useAuthStore((s) => s.admin);
  const login = useAuthStore((s) => s.login);
  const logout = useAuthStore((s) => s.logout);

  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const validate = async () => {
      if (!token) {
        setLoading(false);
        return;
      }

      // only sync if admin is missing
      if (admin) {
        setLoading(false);
        return;
      }

      try {
        const res = await authService.me();
        login(token, res);
      } catch (err) {
        // Only drop the session on a real auth failure — a network error
        // (backend down, offline) shouldn't log out a still-valid token.
        if (err?.response?.status === 401) {
          logout();
        }
      } finally {
        setLoading(false);
      }
    };

    validate();
  }, []); // ✅ run ONCE only

  if (loading) {
    return (
      <div className="w-full h-screen flex items-center justify-center">
        Loading...
      </div>
    );
  }

  return children;
}
