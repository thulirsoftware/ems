import { useEffect, useState } from "react";
import PageHeader from "../../../components/common/PageHeader";
import UserService from "../../../services/user.service";
import AddUserModal from "../components/AddUserModal";
import BulkImportUsersModal from "../components/BulkImportUsersModal";
import { toast } from "sonner";
import { UploadCloud } from "lucide-react";
import { PageLoader } from "../../../components/common/Spinner";
import { EmptyTableRow } from "../../../components/common/EmptyState";

export default function UserList() {
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [showBulkModal, setShowBulkModal] = useState(false);

  useEffect(() => {
    fetchUsers();
  }, []);

  const fetchUsers = async () => {
    setLoading(true);

    try {
      const data = await UserService.UserList();
      setUsers(Array.isArray(data) ? data : []);
    } catch (error) {
      toast.error(
        error?.response?.data?.message || "Failed to load users."
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      <PageHeader
        title="Users List"
        subtitle="Manage your users list"
        actionLabel="Add User"
        onAction={() => setShowModal(true)}
      />

      <div className="flex justify-end mb-4">
        <button
          onClick={() => setShowBulkModal(true)}
          className="flex items-center gap-2 px-4 py-2 rounded-md border border-purple-600 text-purple-600 hover:bg-purple-50"
        >
          <UploadCloud size={16} />
          Bulk Import
        </button>
      </div>

      {showModal && (
        <AddUserModal
          onClose={() => setShowModal(false)}
          onSuccess={() => {
            setShowModal(false);
            fetchUsers();
          }}
        />
      )}

      {showBulkModal && (
        <BulkImportUsersModal
          onClose={() => setShowBulkModal(false)}
          onSuccess={fetchUsers}
        />
      )}

      <div className="bg-white rounded-xl shadow-md p-6">
        {loading ? (
          <PageLoader label="Loading users..." />
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full border-collapse">
              <thead>
                <tr className="border-b">
                  <th className="text-left text-gray-600 font-semibold py-4 px-3">
                    #
                  </th>
                  <th className="text-left text-gray-600 font-semibold py-4 px-3">
                    Name
                  </th>
                  <th className="text-left text-gray-600 font-semibold py-4 px-3">
                    Email
                  </th>
                </tr>
              </thead>

              <tbody>
                {users.length === 0 ? (
                  <EmptyTableRow colSpan={3} title="No users found" />
                ) : (
                  users.map((user, index) => (
                    <tr
                      key={user.id}
                      className="border-b last:border-none hover:bg-gray-50 transition"
                    >
                      <td className="py-4 px-3">
                        {index + 1}
                      </td>

                      <td className="py-4 px-3">
                        <div className="flex items-center gap-3">
                          {user.avatar ? (
                            <img
                              src={user.avatar}
                              alt={user.name}
                              className="w-9 h-9 rounded-full object-cover"
                            />
                          ) : (
                            <div className="w-9 h-9 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-semibold">
                              {user.name?.charAt(0)?.toUpperCase() || "?"}
                            </div>
                          )}
                          <span className="font-medium text-gray-800">
                            {user.name}
                          </span>
                        </div>
                      </td>

                      <td className="py-4 px-3 text-gray-600">
                        {user.email}
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </>
  );
}
