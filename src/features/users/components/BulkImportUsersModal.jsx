import { useState } from "react";
import { X, UploadCloud } from "lucide-react";
import UserService from "../../../services/user.service";

export default function BulkImportUsersModal({ onClose, onSuccess }) {
  const [file, setFile] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [result, setResult] = useState(null);

  const handleFileChange = (e) => {
    setFile(e.target.files?.[0] || null);
    setError("");
    setResult(null);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!file) {
      setError("Please choose a CSV or XLSX file.");
      return;
    }

    setLoading(true);
    setError("");

    try {
      const data = await UserService.BulkImportUsers(file);
      setResult(data);
      onSuccess?.();
    } catch (err) {
      if (err.response?.status === 422) {
        const backendErrors = err.response.data.errors || {};
        const firstMessage =
          Object.values(backendErrors)[0]?.[0] ||
          err.response.data.message ||
          "Invalid file.";
        setError(firstMessage);
      } else {
        setError(err.response?.data?.message || "Bulk import failed.");
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
      <div className="bg-white w-full max-w-lg rounded-xl shadow-lg animate-scaleIn">
        <div className="flex items-center justify-between px-6 py-4 border-b">
          <h2 className="text-lg font-semibold">Bulk Import Users</h2>
          <button
            onClick={onClose}
            className="hover:bg-gray-100 p-2 rounded-full"
          >
            <X size={18} />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          <p className="text-sm text-gray-500">
            Upload a CSV or XLSX file with <code>name</code>,{" "}
            <code>email</code> columns and an optional <code>password</code>{" "}
            column. Rows without a password get a randomly generated
            temporary one.
          </p>

          <label className="flex flex-col items-center justify-center gap-2 border-2 border-dashed border-gray-300 rounded-lg py-8 cursor-pointer hover:border-purple-400 transition">
            <UploadCloud size={28} className="text-gray-400" />
            <span className="text-sm text-gray-600">
              {file ? file.name : "Click to select a .csv or .xlsx file"}
            </span>
            <input
              type="file"
              accept=".csv,.xlsx"
              className="hidden"
              onChange={handleFileChange}
            />
          </label>

          {error && <p className="text-sm text-red-500">{error}</p>}

          {result && (
            <div className="rounded-md bg-gray-50 border p-4 text-sm space-y-2">
              <p>
                {result.message} — <strong>{result.inserted}</strong>{" "}
                inserted.
              </p>

              {result.generated_passwords?.length > 0 && (
                <div>
                  <p className="font-medium mb-1">
                    Temporary passwords (share with these users):
                  </p>
                  <div className="max-h-32 overflow-y-auto border rounded">
                    <table className="w-full text-xs">
                      <tbody>
                        {result.generated_passwords.map((row) => (
                          <tr key={row.email} className="border-b last:border-none">
                            <td className="px-2 py-1">{row.email}</td>
                            <td className="px-2 py-1 font-mono">
                              {row.temporary_password}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}

              {result.errors?.length > 0 && (
                <div>
                  <p className="font-medium text-red-600 mb-1">
                    Skipped rows:
                  </p>
                  <div className="max-h-32 overflow-y-auto border rounded">
                    <table className="w-full text-xs">
                      <tbody>
                        {result.errors.map((row, i) => (
                          <tr key={i} className="border-b last:border-none">
                            <td className="px-2 py-1">Row {row.row}</td>
                            <td className="px-2 py-1 text-red-500">
                              {row.error}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}
            </div>
          )}

          <div className="flex justify-end gap-3 pt-2">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 rounded-md border hover:bg-gray-50"
            >
              {result ? "Close" : "Cancel"}
            </button>

            {!result && (
              <button
                type="submit"
                disabled={loading}
                className="px-5 py-2 rounded-md bg-purple-600 hover:bg-purple-700 text-white disabled:opacity-50"
              >
                {loading ? "Importing..." : "Import"}
              </button>
            )}
          </div>
        </form>
      </div>
    </div>
  );
}
