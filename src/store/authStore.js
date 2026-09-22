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
      localStorage.removeItem("token");
      localStorage.removeItem("admin");

      // Clean up any abandoned exam-attempt deadline anchors (see
      // RunningAssessmentQuestions.jsx) left behind by attempts that were
      // never submitted.
      Object.keys(localStorage)
        .filter((key) => key.startsWith("attempt_start_"))
        .forEach((key) => localStorage.removeItem(key));

      set({ token: null, admin: null, isAuthenticated: false });
    },
  };
});
