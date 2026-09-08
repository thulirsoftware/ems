import { create } from "zustand";

export const useAuthStore = create((set) => {
  let admin = null;

  try {
    admin = JSON.parse(localStorage.getItem("admin"));
  } catch {
    localStorage.removeItem("admin");
  }

  return {
    token: localStorage.getItem("token"),
    admin,
    isAuthenticated: !!localStorage.getItem("token"),

    login: (token, admin) => {
      localStorage.setItem("token", token);
      localStorage.setItem("admin", JSON.stringify(admin));
      set({ token, admin, isAuthenticated: true });
    },

    logout: () => {
      localStorage.clear();
      set({ token: null, admin: null, isAuthenticated: false });
    },
  };
});
