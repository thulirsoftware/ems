import { useNavigate } from "react-router-dom";
import AssessmentService from "../../../services/assesment.service";
import { useAssessmentList } from "../../../hooks/useAssessmentList";
import AssessmentSection from "../../../components/common/AssessmentSection";
import AssessmentCard from "./AssessmentCard";

export default function UpcomingAssessments() {
  const navigate = useNavigate();
  const { data, loading, error, reload } = useAssessmentList(
    AssessmentService.UpcomingAssessmentList
  );

  return (
    <AssessmentSection
      title="Upcoming Assessments"
      titleColor="text-indigo-600"
      loading={loading}
      error={error}
      onRetry={reload}
      isEmpty={data.length === 0}
      emptyMessage="No upcoming exams"
    >
      {data.map((exam) => (
        <AssessmentCard
          key={exam.id}
          exam={exam}
          variant="upcoming"
          onAction={() => navigate(`/assesments/${exam.id}`)}
        />
      ))}
    </AssessmentSection>
  );
}
