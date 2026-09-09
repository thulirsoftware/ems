import { useNavigate } from "react-router-dom";
import AssessmentService from "../../../services/assesment.service";
import { useAssessmentList } from "../../../hooks/useAssessmentList";
import AssessmentSection from "../../../components/common/AssessmentSection";
import AssessmentCard from "../../assessments/components/AssessmentCard";

export default function CompletedAssessments() {
  const navigate = useNavigate();
  const { data, loading, error, reload } = useAssessmentList(
    AssessmentService.CompletedAssessmentList
  );

  return (
    <AssessmentSection
      title="Completed Assessments"
      titleColor="text-emerald-600"
      loading={loading}
      error={error}
      onRetry={reload}
      isEmpty={data.length === 0}
      emptyMessage="No completed exams yet"
    >
      {data.map((exam) => (
        <AssessmentCard
          key={exam.id}
          exam={exam}
          variant="completed"
          onAction={() => navigate(`/results/${exam.id}/show`)}
        />
      ))}
    </AssessmentSection>
  );
}
