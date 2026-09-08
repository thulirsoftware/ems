import { useEffect, useState, useMemo } from "react";
import PageHeader from "../../../components/common/PageHeader";
import AssessmentService from "../../../services/assesment.service";
import { MoreVertical, Plus, Search } from "lucide-react";
import CreateAssessmentModal from "../components/CreateAssessmentModal";
import ViewAssessmentModal from "../components/ViewAssessmentModal";
import EditAssessmentModal from "../components/EditAssessmentModal";
import AssignAssessmentModal from "../components/AssignAssessmentModal";
import EvaluateStudentsModal from "../components/EvaluateStudentsModal";
import BatchService from "../../../services/batch.service";

const PER_PAGE = 10;

export default function AssessmentList() {
  const [assessments, setAssessments] = useState([]);
  const [currentPage, setCurrentPage] = useState(1);
  const [openMenu, setOpenMenu] = useState(null);
  const [showModal, setShowModal] = useState(false);
  const [viewAssessment, setViewAssessment] = useState(null);
  const [editId, setEditId] = useState(null);
  const [assignId, setAssignId] = useState(null);
  const [search, setSearch] = useState("");
  const [assessmentTypes, setAssessmentTypes] = useState([]);
  const [evaluateId, setEvaluateId] = useState(null);
  const [batches, setBatches] = useState([]);

  useEffect(() => {
    fetchAssessments();

    AssessmentService.getAssessmentTypes().then((data) => {
      setAssessmentTypes(data || []);
    });
    BatchService.getBatches().then((data) => {
      setBatches(data || []);
    });

  }, []);
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
    const data = await AssessmentService.AssessmentList();
    console.log("data", data);
    setAssessments(data || []);
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



      {/* TABLE */}
      <div className="bg-white rounded-xl shadow-md p-2 ">
        <div className="overflow-x-auto h-screen">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b bg-gray-50">
                <th className="text-left py-3 px-3 text-red-600">#</th>
                <th className="text-left py-3 px-3 text-red-600">Title</th>
                <th className="text-left py-3 px-3 text-red-600">Type</th>
                <th className="text-left py-3 px-3 text-red-600">
                  Batch
                </th>
                <th className="text-left py-3 px-3 text-red-600">Publish</th>
                <th className="text-left py-3 px-3 text-red-600">Time</th>
                <th className="text-left py-3 px-3 text-red-600">Status</th>
                <th className="text-center py-3 px-3 text-red-600">Assign</th>
                <th className="text-center py-3 px-3 text-red-600">Action</th>
              </tr>
            </thead>

            <tbody>
              {paginatedData.map((item, index) => (
                <tr
                  key={item.id}
                  className="border-b hover:bg-red-50 transition"
                >
                  <td className="py-3 px-3">
                    {(currentPage - 1) * PER_PAGE + index + 1}
                  </td>
                  <td className="py-3 px-3 font-medium">{item.title}</td>
                  <td className="py-3 px-3 font-medium">
                    {typeMap[item.assessment_type_id] || "Unknown"}
                  </td>
                  <td className="py-3 px-3">
                    {item.is_batch_wise
                      ? batchMap[item.batch_id] || "-"
                      : "-"}
                  </td>
                  <td className="py-3 px-3">{item.publish_date}</td>
                  <td className="py-3 px-3">
                    {item.start_time} - {item.end_time}
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
                    {item.is_batch_wise ? (
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
                              setEvaluateId(item.id);
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
                          className="block w-full px-4 py-2 text-left text-red-600 hover:bg-red-50"
                          onClick={async () => {
                            if (!confirm("Delete this assessment?")) return;
                            await AssessmentService.deleteAssessment(item.id);
                            fetchAssessments();
                            setOpenMenu(null);
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
                <tr>
                  <td colSpan="7" className="text-center py-8 text-gray-400">
                    No assessments found
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* PAGINATION */}
      {totalPages > 1 && (
        <div className="flex justify-center mt-6 gap-2">
          {[...Array(totalPages)].map((_, index) => (
            <button
              key={index}
              onClick={() => handlePageChange(index + 1)}
              className={`px-4 py-2 rounded-lg text-sm font-medium transition ${currentPage === index + 1
                ? "bg-red-600 text-white shadow-md"
                : "bg-gray-100 hover:bg-gray-200"
                }`}
            >
              {index + 1}
            </button>
          ))}
        </div>
      )}

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
          assessmentId={evaluateId}
          onClose={() => setEvaluateId(null)}
        />
      )}
    </>
  );
}