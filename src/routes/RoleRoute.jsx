import { Navigate } from "react-router-dom";
import { useAuthStore } from "../store/authStore";

export default function RoleRoute({ children, allowed }) {
  const role = useAuthStore((s) => s.role);

  if (!role) {
    return <Navigate to="/login" replace />;
  }

  if (!allowed.includes(role)) {
    return <Navigate to="/not-authorized" replace />;
  }

  return children;
}
