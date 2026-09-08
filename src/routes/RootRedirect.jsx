// routes/RootRedirect.jsx
import { Navigate } from "react-router-dom";

export default function RootRedirect() {
  const token = localStorage.getItem("token"); // or auth context

  if (token) {
    return <Navigate to="/dashboard" replace />;
  }

  return <Navigate to="/login" replace />;
}
