import PageHeader from "../../../components/common/PageHeader";
import CompletedAssessments from "../components/CompletedAssesments";
import MissedAssessments from "../components/MissedAssesments";

export default function ResultList(){
    return(
        <>
        <div className="page-head">
            <PageHeader title={"Result Section"} subtitle={" List of completed and missed asessment"}/>
        </div>
        <CompletedAssessments/>
        <br />
        <MissedAssessments/>
        </>
    )
}