import { create } from "zustand";

export const useSidebarStore = create((set) => ({
  isOpen: true,
  toggleSidebar: () => set((s) => ({ isOpen: !s.isOpen })),
  closeSidebar: () => set({ isOpen: false }),
  openSidebar: () => set({ isOpen: true }),
}));
