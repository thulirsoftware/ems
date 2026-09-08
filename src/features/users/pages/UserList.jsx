import { useEffect, useState } from "react";
import PageHeader from "../../../components/common/PageHeader";
import UserService from "../../../services/user.service";
import { Eye } from "lucide-react";
import AddUserModal from "../components/AddUserModal";

export default function UserList() {
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);

  useEffect(() => {
    fetchUsers();
  }, []);

  const fetchUsers = async () => {
    try {
      const data = await UserService.UserList();
      setUsers(data);
    } catch (error) {
      console.error("Failed to fetch users", error);
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

      {showModal && (
        <AddUserModal
          onClose={() => setShowModal(false)}
          onSuccess={() => {
            setShowModal(false);
            fetchUsers();
          }}
        />
      )}

      <div className="bg-white rounded-xl shadow-md p-6">
        {loading ? (
          <p className="text-center text-gray-500 py-10">
            Loading users...
          </p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full border-collapse">
              <thead>
                <tr className="border-b">
                  <th className="text-left text-red-600 font-semibold py-4 px-3">
                    #
                  </th>
                  <th className="text-left text-red-600 font-semibold py-4 px-3">
                    Name
                  </th>
                  <th className="text-left text-red-600 font-semibold py-4 px-3">
                    Email
                  </th>
                  <th className="text-left text-red-600 font-semibold py-4 px-3">
                    Status
                  </th>
                  <th className="text-center text-red-600 font-semibold py-4 px-3">
                    Action
                  </th>
                </tr>
              </thead>

              <tbody>
                {users.length === 0 ? (
                  <tr>
                    <td
                      colSpan="5"
                      className="text-center py-8 text-gray-400"
                    >
                      No users found
                    </td>
                  </tr>
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
                              {user.name.charAt(0)}
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

                      <td className="py-4 px-3">
                        <span className="bg-green-100 text-green-600 px-4 py-1 rounded-full text-sm font-medium">
                          Active
                        </span>
                      </td>

                      <td className="py-4 px-3 text-center">
                        <button className="p-2 rounded-full hover:bg-gray-100 transition">
                          <Eye className="w-5 h-5 text-gray-600" />
                        </button>
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
