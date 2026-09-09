import React from "react";
import ReactDOM from "react-dom/client";
import AuthProvider from "./app/providers/AuthProvider";
import { RouterProvider } from "react-router-dom";
import { router } from "./routes";
import './index.css';
import './styles/theme.css';
import { Toaster } from "sonner";

ReactDOM.createRoot(document.getElementById("root")).render(
  <AuthProvider>
    <Toaster
      position="top-right"
      expand
      richColors
      closeButton
    />
    <RouterProvider router={router} />
  </AuthProvider>
);
