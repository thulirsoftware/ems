import { useEffect, useState } from "react";
import AssessmentService from "../../../services/assesment.service";
import { useNavigate } from "react-router-dom";

export default function EvaluateStudentsModal({ assessmentId, onClose }) {
    const [students, setStudents] = useState([]);
    const navigate = useNavigate();

    useEffect(() => {
        fetchStudents();
    }, []);

    const fetchStudents = async () => {
        const data = await AssessmentService.getAssessmentUsersForEvaluation(
            assessmentId
        );
        setStudents(data || []);
    };

    return (
        <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
            <div className="bg-white w-[520px] rounded-xl shadow-xl p-6">

                <h2 className="text-lg font-semibold mb-4">
                    Students List
                </h2>

                <div className="max-h-[400px] overflow-y-auto space-y-2">

                    {students.map((student) => (
                        <div
                            key={student.user_id}
                            className="flex items-center justify-between border rounded-lg px-4 py-3 hover:bg-gray-50"
                        >
                            {/* LEFT - NAME */}
                            <div className="flex-1">
                                <p className="font-medium">{student.name}</p>
                            </div>

                            {/* MIDDLE - STATUS */}
                            <div className="flex-1 text-center">
                                {student.attempted && student.grade_pending && (
                                    <span className="text-orange-600 text-xs font-medium">
                                        Pending
                                    </span>
                                )}

                                {!student.attempted && (
                                    <span className="text-gray-400 text-xs">
                                        Not Attempted
                                    </span>
                                )}

                                {student.attempted && !student.grade_pending && (
                                    <span className="text-green-600 text-xs font-medium">
                                        Evaluated
                                    </span>
                                )}
                            </div>

                            {/* RIGHT - BUTTON */}
                            <div className="flex-1 text-right">
                                {student.attempted && student.grade_pending && (
                                    <button
                                        className="px-3 py-1 bg-purple-600 text-white rounded-md text-sm hover:bg-purple-700 transition"
                                        onClick={() =>
                                            navigate(`/admin/evaluate/${assessmentId}/${student.user_id}`)
                                        }
                                    >
                                        Evaluate
                                    </button>
                                )}
                            </div>
                        </div>
                    ))}

                </div>

                <div className="mt-4 text-right">
                    <button
                        onClick={onClose}
                        className="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300 transition"
                    >
                        Close
                    </button>
                </div>

            </div>
        </div>
    );
}