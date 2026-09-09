import RunningAssessments from "../components/RunningAssessments";
import UpcomingAssessments from "../components/UpcomingAssessments";
import TodaysAssessments from "../components/TodaysAssessments";
import PageHeader from "../../../components/common/PageHeader";

export default function AssessmentList() {
  return (
    <>
      <div className="page-head">
        <PageHeader title={"Assessment List"} subtitle={"All your assessment list shows here"} />
      </div>
      <div className="space-y-12">
        <RunningAssessments />
        <TodaysAssessments />
        <UpcomingAssessments />
      </div>
    </>
  );
}
