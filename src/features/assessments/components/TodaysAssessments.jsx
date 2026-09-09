import { useNavigate } from "react-router-dom";
import AssessmentService from "../../../services/assesment.service";
import { useAssessmentList } from "../../../hooks/useAssessmentList";
import AssessmentSection from "../../../components/common/AssessmentSection";
import AssessmentCard from "./AssessmentCard";

export default function TodaysAssessments() {
  const navigate = useNavigate();
  const { data, loading, error, reload } = useAssessmentList(
    AssessmentService.TodaysAssessmentList,
    { pollInterval: 10000 }
  );

  return (
    <AssessmentSection
      title="Today's Assessments"
      titleColor="text-blue-600"
      loading={loading}
      error={error}
      onRetry={reload}
      isEmpty={data.length === 0}
      emptyMessage="No exams today"
    >
      {data.map((exam) => (
        <AssessmentCard
          key={exam.id}
          exam={exam}
          variant="today"
          onAction={() => navigate(`/assesments/${exam.id}`)}
        />
      ))}
    </AssessmentSection>
  );
}
