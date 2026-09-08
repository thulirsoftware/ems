import { create } from "zustand";

export const useBranchStore = create((set) => ({
  branch: localStorage.getItem("branch") || "chennai", // ✅ default

  setBranch: (branch) => {
    localStorage.setItem("branch", branch);
    set({ branch });
  },
}));