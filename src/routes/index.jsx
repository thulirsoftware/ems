import { createBrowserRouter } from "react-router-dom";

import Login from "../features/auth/Login";
import ErrorPage from "../routes/ErrorPage";
import ProtectedRoute from "../routes/ProtectedRoute";

import AdminLayout from "../layouts/AdminLayout";
import RootRedirect from "./RootRedirect";
import Dashboard from "../features/dashboard/dashboard/pages/Dashboard";
import UserList from "../features/users/pages/UserList";
import AssessmentList from "../features/assessments/pages/AssessmentList";
import ResultList from "../features/results/pages/ResultList";
import RunningAssessmentDetails from "../features/assessments/components/RunningAssessmentDetails";
import RunningAssessmentQuestions from "../features/assessments/components/RunningAssessmentQuestions";
import AssessmentResultPage from "../features/assessments/components/AssessmentResultPage";
import RunningAssessmentResultPage from "../features/assessments/components/RunningAssesmentResult";
import ReportPage from "../features/reports/pages/ReportPage";
import ComingSoonPage from "../features/common/ComingSoonPage";
import Proctoring from "../features/proctoring/Proctoring";

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
    path: "/",
    element: (
      <ProtectedRoute>
        <AdminLayout />
      </ProtectedRoute>
    ),
    children: [
      { path: "dashboard", element: <Dashboard /> },
      { path: "assesments", element: <AssessmentList /> },
      { path: "assesments/:examid", element: < RunningAssessmentDetails /> },
      { path: "assesments/start/:examid/run/:attemptId", element: < RunningAssessmentQuestions /> },
      { path: "assesments/end/:examid/result", element: < RunningAssessmentResultPage /> },
      { path: "results", element: <ResultList /> },
      { path: "results/:examid/show", element: <AssessmentResultPage /> },
      { path: "reports", element: <ReportPage /> },
      { path: "proctoring", element: <Proctoring /> },
      { path: "certificates", element: <ComingSoonPage title="Certificates" /> },


    ],
  },

  { path: "*", element: <ErrorPage /> },
]);
