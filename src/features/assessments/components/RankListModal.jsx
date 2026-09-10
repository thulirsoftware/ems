import { useEffect, useState } from "react";
import { toast } from "sonner";
import { Trophy } from "lucide-react";
import AssessmentService from "../../../services/assesment.service";
import { PageLoader } from "../../../components/common/Spinner";

export default function RankListModal({ assessmentId, batchId, title, onClose }) {
  const [loading, setLoading] = useState(true);
  const [rankList, setRankList] = useState([]);

  useEffect(() => {
    let cancelled = false;

    (async () => {
      try {
        const data = await AssessmentService.getRankList(assessmentId, batchId);

        if (!cancelled) {
          setRankList(data?.rank_list || []);
        }
      } catch (err) {
        if (!cancelled) {
          toast.error(
            err?.response?.data?.message || "Failed to load rank list."
          );
        }
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [assessmentId, batchId]);

  return (
    <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center">
      <div className="bg-white w-[560px] max-h-[80vh] rounded-xl shadow-xl flex flex-col">

        {/* Header */}
        <div className="border-b px-6 py-4 flex justify-between items-center">
          <div>
            <h2 className="font-semibold flex items-center gap-2">
              <Trophy size={18} className="text-yellow-500" />
              Rank List
            </h2>
            {title && (
              <p className="text-xs text-gray-500 mt-0.5">{title}</p>
            )}
          </div>
          <button onClick={onClose}>✕</button>
        </div>

        {/* Body */}
        <div className="p-6 overflow-y-auto flex-1">
          {loading ? (
            <PageLoader label="Loading rank list..." />
          ) : rankList.length === 0 ? (
            <p className="text-sm text-gray-400 text-center py-8">
              No evaluated attempts yet
            </p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b text-left text-gray-500">
                  <th className="py-2 pr-2">Rank</th>
                  <th className="py-2 pr-2">Candidate</th>
                  <th className="py-2 pr-2 text-center">Score</th>
                  <th className="py-2 text-center">Percentage</th>
                </tr>
              </thead>
              <tbody>
                {rankList.map((row) => (
                  <tr key={row.user_id} className="border-b last:border-none hover:bg-gray-50">
                    <td className="py-2 pr-2 font-semibold">
                      {row.rank <= 3 ? (
                        <span className="inline-flex items-center gap-1 text-yellow-600">
                          <Trophy size={14} />
                          {row.rank}
                        </span>
                      ) : (
                        row.rank
                      )}
                    </td>
                    <td className="py-2 pr-2">{row.name || "-"}</td>
                    <td className="py-2 pr-2 text-center">
                      {row.score}/{row.total_marks}
                    </td>
                    <td className="py-2 text-center font-semibold text-indigo-600">
                      {row.percentage}%
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>

        {/* Footer */}
        <div className="border-t px-6 py-4 flex justify-end">
          <button
            onClick={onClose}
            className="px-4 py-2 border rounded-md"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  );
}
