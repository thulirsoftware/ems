import { createBrowserRouter } from "react-router-dom";

import Login from "../features/auth/Login";
import ErrorPage from "../routes/ErrorPage";
import ProtectedRoute from "../routes/ProtectedRoute";

import AdminLayout from "../layouts/AdminLayout";
import RootRedirect from "./RootRedirect";
import Dashboard from "../features/dashboard/dashboard/pages/Dashboard";
import UserList from "../features/users/pages/UserList";
import AssessmentList from "../features/assessments/pages/AssessmentList";
import ReportsPage from "../features/reports/pages/ReportsPage";
import AnalyticsPage from "../features/analytics/pages/AnalyticsPage";
import EvaluateAssessment from "../features/assessments/components/EvaluateAssessment";
import ManualCorrectionResultPage from "../features/assessments/components/ManualCorrectionResultPage";
import BatchListPage from "../features/batches/pages/BatchListPage";

export const router = createBrowserRouter([
  {
    path: "/",
    element: <RootRedirect />,
    errorElement: <ErrorPage />,
  },
  {
    path: "/login",
    element: <Login />,
  },

  {
    path: "/admin",
    element: (
      <ProtectedRoute>
        <AdminLayout />
      </ProtectedRoute>
    ),
    children: [
      { path: "dashboard", element: <Dashboard /> },
      { path: "users", element: <UserList /> },
      { path: "batch", element: <BatchListPage /> },
      { path: "assesments", element: <AssessmentList /> },
      { path: "reports", element: <ReportsPage /> },
      { path: "analytics", element: <AnalyticsPage /> },
      { path: "evaluate/:assessmentId/:userId", element: <EvaluateAssessment /> },
      { path: "result/:assessmentId/:userId", element: <ManualCorrectionResultPage /> },
    ],
  },

  { path: "*", element: <ErrorPage /> },
]);
