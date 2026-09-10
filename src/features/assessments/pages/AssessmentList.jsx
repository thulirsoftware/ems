import { useEffect, useState, useMemo } from "react";
import { toast } from "sonner";
import PageHeader from "../../../components/common/PageHeader";
import AssessmentService from "../../../services/assesment.service";
import { MoreVertical, Plus, Search } from "lucide-react";
import CreateAssessmentModal from "../components/CreateAssessmentModal";
import ViewAssessmentModal from "../components/ViewAssessmentModal";
import EditAssessmentModal from "../components/EditAssessmentModal";
import AssignAssessmentModal from "../components/AssignAssessmentModal";
import EvaluateStudentsModal from "../components/EvaluateStudentsModal";
import RankListModal from "../components/RankListModal";
import ReExamModal from "../components/ReExamModal";
import BatchService from "../../../services/batch.service";
import { PageLoader } from "../../../components/common/Spinner";
import ErrorState from "../../../components/common/ErrorState";
import { EmptyTableRow } from "../../../components/common/EmptyState";
import { useConfirm } from "../../../hooks/useConfirm";

const PER_PAGE = 10;

const VIEWS = [
  { key: "all", label: "All" },
  { key: "library", label: "Library" },
  { key: "upcoming", label: "Upcoming" },
  { key: "running", label: "Running" },
  { key: "finished", label: "Finished" },
];

export default function AssessmentList() {
  const [assessments, setAssessments] = useState([]);
  const [currentPage, setCurrentPage] = useState(1);
  const [openMenu, setOpenMenu] = useState(null);
  const [showModal, setShowModal] = useState(false);
  const [viewAssessment, setViewAssessment] = useState(null);
  const [editId, setEditId] = useState(null);
  const [assignId, setAssignId] = useState(null);
  const [search, setSearch] = useState("");
  const [view, setView] = useState("all");
  const [assessmentTypes, setAssessmentTypes] = useState([]);
  const [evaluateId, setEvaluateId] = useState(null);
  const [rankListItem, setRankListItem] = useState(null);
  const [reExamItem, setReExamItem] = useState(null);
  const [batches, setBatches] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);
  const [confirm, confirmDialog] = useConfirm();

  useEffect(() => {
    AssessmentService.getAssessmentTypes()
      .then((data) => setAssessmentTypes(data || []))
      .catch(() => toast.error("Failed to load assessment types"));
    BatchService.getBatches()
      .then((data) => setBatches(data || []))
      .catch(() => toast.error("Failed to load batches"));

  }, []);

  useEffect(() => {
    fetchAssessments();
    setCurrentPage(1);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [view]);

  const typeMap = useMemo(() => {
    const map = {};
    assessmentTypes.forEach((t) => {
      map[t.id] = t.name;
    });
    return map;
  }, [assessmentTypes]);
  const batchMap = useMemo(() => {
    const map = {};

    batches.forEach((batch) => {
      map[batch.id] = batch.name;
    });

    return map;
  }, [batches]);

  const fetchAssessments = async () => {
    setLoading(true);
    setError(false);
    try {
      let data;

      switch (view) {
        case "library":
          data = await AssessmentService.getLibrary();
          break;
        case "upcoming":
          data = await AssessmentService.getUpcoming();
          break;
        case "running":
          data = await AssessmentService.getRunning();
          break;
        case "finished":
          data = await AssessmentService.getFinishedAssessments();
          break;
        default:
          data = await AssessmentService.AssessmentList();
      }

      setAssessments(data || []);
    } catch {
      setError(true);
      toast.error("Failed to load assessments");
    } finally {
      setLoading(false);
    }
  };

  /* ---------------- SEARCH FILTER ---------------- */
  const filteredData = useMemo(() => {
    return assessments.filter((item) =>
      item.title?.toLowerCase().includes(search.toLowerCase())
    );
  }, [search, assessments]);

  /* ---------------- PAGINATION ---------------- */
  const totalPages = Math.ceil(filteredData.length / PER_PAGE);

  const paginatedData = filteredData.slice(
    (currentPage - 1) * PER_PAGE,
    currentPage * PER_PAGE
  );

  const handleSearch = (e) => {
    setSearch(e.target.value);
    setCurrentPage(1); // reset page on search
  };

  const handlePageChange = (page) => {
    setCurrentPage(page);
  };
  const [menuPosition, setMenuPosition] = useState({
    x: 0,
    y: 0,
  });

  return (
    <>
      <div className="flex items-center justify-between mb-2">

        {/* LEFT SIDE → PAGE HEADER */}
        <PageHeader
          title="Assessments"
          subtitle="Manage assessments list"
        />

        {/* RIGHT SIDE → CONTROLS */}
        <div className="flex items-center gap-4">

          {/* PAGINATION DETAILS */}
          <div className="text-sm text-gray-500 whitespace-nowrap">
            Showing{" "}
            <span className="font-semibold">
              {filteredData.length === 0
                ? 0
                : (currentPage - 1) * PER_PAGE + 1}
            </span>
            {" - "}
            <span className="font-semibold">
              {Math.min(currentPage * PER_PAGE, filteredData.length)}
            </span>{" "}
            of <span className="font-semibold">{filteredData.length}</span>
          </div>

          {/* SEARCH */}
          <div className="relative w-72">
            <Search className="absolute left-3 top-3 w-4 text-gray-400" />
            <input
              type="text"
              placeholder="Search assessments..."
              value={search}
              onChange={handleSearch}
              className="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-400 outline-none transition"
            />
          </div>

          {/* ADD BUTTON */}
          <button
            onClick={() => setShowModal(true)}
            className="flex items-center gap-2 px-5 py-2 rounded-lg 
                 bg-purple-600 hover:bg-purple-700 
                 text-white shadow-sm transition whitespace-nowrap"
          >
            <Plus size={16} />
            Add Assessment
          </button>

        </div>
      </div>

      {/* VIEW TABS */}
      <div className="flex gap-2 border-b mb-4 overflow-x-auto">
        {VIEWS.map((v) => (
          <button
            key={v.key}
            onClick={() => setView(v.key)}
            className={`px-4 py-2 text-sm font-medium whitespace-nowrap border-b-2 transition ${
              view === v.key
                ? "border-purple-600 text-purple-600"
                : "border-transparent text-gray-500 hover:text-gray-700"
            }`}
          >
            {v.label}
          </button>
        ))}
      </div>

      {/* TABLE */}
      <div className="bg-white rounded-xl shadow-md p-2 ">
        <div className="overflow-x-auto">
          {loading ? (
            <PageLoader label="Loading assessments..." />
          ) : error ? (
            <ErrorState
              title="Couldn't load assessments"
              onRetry={fetchAssessments}
            />
          ) : (
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-gray-50">
                <th className="text-left py-3 px-3 text-gray-600">#</th>
                <th className="text-left py-3 px-3 text-gray-600">Title</th>
                <th className="text-left py-3 px-3 text-gray-600">Type</th>
                <th className="text-left py-3 px-3 text-gray-600">
                  Batch
                </th>
                <th className="text-left py-3 px-3 text-gray-600">Publish</th>
                <th className="text-left py-3 px-3 text-gray-600">Time</th>
                <th className="text-left py-3 px-3 text-gray-600">Status</th>
                <th className="text-center py-3 px-3 text-gray-600">Assign</th>
                <th className="text-center py-3 px-3 text-gray-600">Action</th>
              </tr>
            </thead>

            <tbody>
              {paginatedData.map((item, index) => (
                <tr
                  key={item.scheduling_type === "batch_wise" ? `${item.id}-${item.batch_id}` : item.id}
                  className="border-b hover:bg-gray-50 transition"
                >
                  <td className="py-3 px-3">
                    {(currentPage - 1) * PER_PAGE + index + 1}
                  </td>
                  <td className="py-3 px-3 font-medium">
                    <div className="flex items-center gap-2">
                      {item.title}
                      {item.is_library && (
                        <span className="inline-flex items-center rounded-full bg-amber-100 text-amber-700 px-2 py-0.5 text-[10px] font-semibold">
                          Library
                        </span>
                      )}
                    </div>
                  </td>
                  <td className="py-3 px-3 font-medium">
                    {typeMap[item.assessment_type_id] || "Unknown"}
                  </td>
                  <td className="py-3 px-3">
                    {item.scheduling_type === "batch_wise"
                      ? batchMap[item.batch_id] || "-"
                      : "-"}
                  </td>
                  <td className="py-3 px-3">
                    {item.scheduling_type === "flexible"
                      ? `${item.start_date || "-"} → ${item.end_date || "-"}`
                      : item.publish_date || "-"}
                  </td>
                  <td className="py-3 px-3">
                    {item.scheduling_type === "flexible"
                      ? `${item.duration_minutes ?? "-"} min`
                      : `${item.start_time || "-"} - ${item.end_time || "-"}`}
                  </td>
                  <td className="py-3 px-3">
                    {item.is_active ? (
                      <span className="bg-green-100 text-green-600 px-3 py-1 rounded-full text-xs">
                        Active
                      </span>
                    ) : (
                      <span className="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs">
                        Inactive
                      </span>
                    )}
                  </td>

                  <td className="py-3 px-3 text-center">
                    {item.scheduling_type === "batch_wise" ? (
                      <span className="inline-flex items-center rounded-full bg-purple-100 text-purple-700 px-3 py-1 text-xs font-medium">
                        Batch
                      </span>
                    ) : (
                      <button
                        onClick={() => setAssignId(item.id)}
                        className="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700"
                      >
                        Assign
                      </button>
                    )}
                  </td>

                  <td className="py-3 px-3 text-center relative">
                    <button
                      onClick={(e) => {
                        const rect = e.currentTarget.getBoundingClientRect();

                        setMenuPosition({
                          x: rect.right,
                          y: rect.bottom,
                        });

                        setOpenMenu(openMenu === item.id ? null : item.id);
                      }}
                      className="p-2 rounded-full hover:bg-gray-100"
                    >
                      <MoreVertical className="w-5 h-5" />
                    </button>

                    {openMenu === item.id && (
                      <div className="absolute right-8 top-10 w-36 bg-white border rounded-lg shadow-lg z-10">
                        {typeMap[item.assessment_type_id]?.toLowerCase() === "descriptive" && (
                          <button
                            className="block w-full px-4 py-2 text-left hover:bg-gray-100 text-blue-600"
                            onClick={() => {
                              setEvaluateId({
                                assessmentId: item.id,
                                batchId: item.scheduling_type === "batch_wise"
                                  ? item.batch_id
                                  : null,
                              });
                              setOpenMenu(null);
                            }}
                          >
                            Evaluate
                          </button>
                        )}
                        <button
                          className="block w-full px-4 py-2 text-left hover:bg-gray-100"
                          onClick={async () => {
                            const data =
                              await AssessmentService.getAssessmentById(
                                item.id
                              );
                            setViewAssessment(data);
                            setOpenMenu(null);
                          }}
                        >
                          View
                        </button>

                        <button
                          className="block w-full px-4 py-2 text-left hover:bg-gray-100"
                          onClick={() => {
                            setEditId(item.id);
                            setOpenMenu(null);
                          }}
                        >
                          Edit
                        </button>

                        <button
                          className="block w-full px-4 py-2 text-left hover:bg-gray-100"
                          onClick={() => {
                            setRankListItem({
                              assessmentId: item.id,
                              batchId: item.scheduling_type === "batch_wise"
                                ? item.batch_id
                                : null,
                              title: item.title,
                            });
                            setOpenMenu(null);
                          }}
                        >
                          Rank List
                        </button>

                        {item.scheduling_type !== "flexible" && (
                          <button
                            className="block w-full px-4 py-2 text-left hover:bg-gray-100"
                            onClick={() => {
                              setReExamItem(item);
                              setOpenMenu(null);
                            }}
                          >
                            Re-Exam
                          </button>
                        )}

                        <button
                          className="block w-full px-4 py-2 text-left text-red-600 hover:bg-red-50"
                          onClick={async () => {
                            setOpenMenu(null);
                            const ok = await confirm({
                              title: "Delete this assessment?",
                              description: "This action cannot be undone.",
                              confirmLabel: "Delete",
                            });
                            if (!ok) return;
                            try {
                              await AssessmentService.deleteAssessment(item.id);
                              toast.success("Assessment deleted");
                              fetchAssessments();
                            } catch {
                              toast.error("Failed to delete assessment");
                            }
                          }}
                        >
                          Delete
                        </button>
                      </div>
                    )}
                  </td>
                </tr>
              ))}

              {paginatedData.length === 0 && (
                <EmptyTableRow colSpan={9} title="No assessments found" />
              )}
            </tbody>
          </table>
          )}
        </div>
      </div>

      {/* PAGINATION */}
      {!loading && !error && totalPages > 1 && (
        <div className="flex justify-center mt-6 gap-2">
          {[...Array(totalPages)].map((_, index) => (
            <button
              key={index}
              onClick={() => handlePageChange(index + 1)}
              className={`px-4 py-2 rounded-lg text-sm font-medium transition ${currentPage === index + 1
                ? "bg-purple-600 text-white shadow-md"
                : "bg-gray-100 hover:bg-gray-200"
                }`}
            >
              {index + 1}
            </button>
          ))}
        </div>
      )}

      {confirmDialog}

      {/* MODALS */}
      {showModal && (
        <CreateAssessmentModal
          onClose={() => setShowModal(false)}
          onSuccess={() => {
            setShowModal(false);
            fetchAssessments();
          }}
        />
      )}

      {viewAssessment && (
        <ViewAssessmentModal
          assessment={viewAssessment}
          typeName={typeMap[viewAssessment.assessment_type_id]}
          onClose={() => setViewAssessment(null)}
        />
      )}

      {editId && (
        <EditAssessmentModal
          assessmentId={editId}
          onClose={() => setEditId(null)}
          onSuccess={fetchAssessments}
        />
      )}

      {assignId && (
        <AssignAssessmentModal
          assessmentId={assignId}
          onClose={() => setAssignId(null)}
        />
      )}
      {evaluateId && (
        <EvaluateStudentsModal
          assessmentId={evaluateId.assessmentId}
          batchId={evaluateId.batchId}
          onClose={() => setEvaluateId(null)}
        />
      )}

      {rankListItem && (
        <RankListModal
          assessmentId={rankListItem.assessmentId}
          batchId={rankListItem.batchId}
          title={rankListItem.title}
          onClose={() => setRankListItem(null)}
        />
      )}

      {reExamItem && (
        <ReExamModal
          assessment={reExamItem}
          onClose={() => setReExamItem(null)}
          onSuccess={() => {
            setReExamItem(null);
            fetchAssessments();
          }}
        />
      )}
    </>
  );
}