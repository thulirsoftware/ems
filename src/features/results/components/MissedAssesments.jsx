import AssessmentService from "../../../services/assesment.service";
import { useAssessmentList } from "../../../hooks/useAssessmentList";
import AssessmentSection from "../../../components/common/AssessmentSection";
import AssessmentCard from "../../assessments/components/AssessmentCard";

export default function MissedAssessments() {
  const { data, loading, error, reload } = useAssessmentList(
    AssessmentService.MissedAssessmentList
  );

  return (
    <AssessmentSection
      title="Missed Assessments"
      titleColor="text-gray-500"
      loading={loading}
      error={error}
      onRetry={reload}
      isEmpty={data.length === 0}
      emptyMessage="You haven't missed any exams"
    >
      {data.map((exam) => (
        <AssessmentCard key={exam.id} exam={exam} variant="missed" />
      ))}
    </AssessmentSection>
  );
}
