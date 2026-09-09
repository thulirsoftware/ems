import { useEffect, useState, useCallback } from "react";
import { useMemo } from "react";
import { toast } from "sonner";
import PageHeader from "../../../components/common/PageHeader";
import BatchFilter from "../components/BatchFilter";
import BatchTable from "../components/BatchTable";
import BatchService from "../../../services/batch.service";
import BatchFormModal from "../components/BatchFormModal";
import AssessmentService from "../../../services/assesment.service";
import BatchUsersModal from "../components/BatchUsersModal";
import { useConfirm } from "../../../hooks/useConfirm";


export default function BatchListPage() {
    const [batches, setBatches] = useState([]);
    const [loading, setLoading] = useState(false);
    const [manageBatch, setManageBatch] = useState(null);
    const [confirm, confirmDialog] = useConfirm();

    const [filters, setFilters] = useState({
        search: "",
        assessment: "",
        publishDate: "",
    });

    const [selectedBatch, setSelectedBatch] = useState(null);

    const [openForm, setOpenForm] = useState(false);
    const [assessments, setAssessments] = useState([]);
    const loadAssessments = async () => {
        try {
            const res = await AssessmentService.AssessmentList(1, 1000);
            setAssessments(res || []);
        } catch (err) {
            toast.error("Failed to load assessments");
        }
    };


    const loadBatches = useCallback(async () => {
        try {
            setLoading(true);

            const response = await BatchService.getBatches();

            setBatches(Array.isArray(response) ? response : []);
        } catch (error) {
            toast.error("Failed to load batches");
        } finally {
            setLoading(false);
        }
    }, []);
    useEffect(() => {
        loadBatches();
        loadAssessments();
    }, []);
    const assessmentMap = useMemo(() => {
        const map = {};

        assessments.forEach((assessment) => {
            map[assessment.id] = assessment.title;
        });

        return map;
    }, [assessments]);




    const handleCreate = () => {
        setSelectedBatch(null);
        setOpenForm(true);
    };

    const handleEdit = (batch) => {
        setSelectedBatch(batch);
        setOpenForm(true);
    };

    const handleDelete = async (batch) => {
        const ok = await confirm({
            title: `Delete "${batch.name}"?`,
            confirmLabel: "Delete",
            variant: "danger",
        });
        if (!ok) return;

        try {
            await BatchService.deleteBatch(batch.id);
            await loadBatches();
        } catch (error) {
            console.error(error);
            toast.error(error?.response?.data?.message || "Unable to delete batch.");
        }
    };

    const handleManageUsers = (batch) => {
        setManageBatch(batch);
    };

    const filteredBatches = batches.filter((batch) => {
        const searchMatch =
            filters.search === "" ||
            batch.name
                ?.toLowerCase()
                .includes(filters.search.toLowerCase());

        const assessmentMatch =
            filters.assessment === "" ||
            String(batch.assessment_id) === String(filters.assessment);

        const publishDateMatch =
            filters.publishDate === "" ||
            batch.publish_date === filters.publishDate;

        return (
            searchMatch &&
            assessmentMatch &&
            publishDateMatch
        );
    });

    return (
        <div className="space-y-6">
            <PageHeader
                title="Batch Management"
                subtitle="Create and manage assessment batches"
            />

            <BatchFilter
                filters={filters}
                onChange={setFilters}
                assessments={assessments}
            />

            <BatchTable
                batches={filteredBatches}
                loading={loading}
                assessmentMap={assessmentMap}
                onEdit={handleEdit}
                onDelete={handleDelete}
                onUsers={handleManageUsers}
            />

            <BatchFormModal
                open={openForm}
                onClose={() => setOpenForm(false)}
                onSuccess={loadBatches}
                batch={selectedBatch}
                assessments={assessments}
            />
            <BatchUsersModal
                open={!!manageBatch}
                batch={manageBatch}
                onClose={() => setManageBatch(null)}
            />
            {confirmDialog}
        </div>
    );
}