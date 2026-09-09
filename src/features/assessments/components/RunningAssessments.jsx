import { useNavigate } from "react-router-dom";
import AssessmentService from "../../../services/assesment.service";
import { useAssessmentList } from "../../../hooks/useAssessmentList";
import AssessmentSection from "../../../components/common/AssessmentSection";
import AssessmentCard from "./AssessmentCard";

export default function RunningAssessments() {
  const navigate = useNavigate();
  const { data, loading, error, reload } = useAssessmentList(
    AssessmentService.RunningAssessmentList,
    { pollInterval: 10000 }
  );

  return (
    <AssessmentSection
      title="Running Assessments"
      titleColor="text-red-600"
      loading={loading}
      error={error}
      onRetry={reload}
      isEmpty={data.length === 0}
      emptyMessage="No running exams right now"
    >
      {data.map((exam) => (
        <AssessmentCard
          key={exam.id}
          exam={exam}
          variant="running"
          onAction={() => navigate(`/assesments/${exam.id}`)}
        />
      ))}
    </AssessmentSection>
  );
}
