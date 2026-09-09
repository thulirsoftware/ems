import { Bell, CheckCheck } from "lucide-react";
import { useEffect, useRef, useState } from "react";
import { toast } from "sonner";
import NotificationService from "../../../services/notification.service";

export default function NotificationDropdown() {

  const [open, setOpen] = useState(false);
  const [notifications, setNotifications] = useState([]);
  const ref = useRef(null);

  const unreadCount = notifications.filter(n => !n.is_read).length;

  // fetch notifications — backend only ever returns unread ones
  const loadNotifications = async () => {
    try {
      const data = await NotificationService.getNotificationList();
      setNotifications(Array.isArray(data) ? data : []);
    } catch (err) {
      console.error("Failed to load notifications", err);
    }
  };

  useEffect(() => {
    loadNotifications();
  }, []);

  // outside click close
  useEffect(() => {
    const handler = (e) => {
      if (ref.current && !ref.current.contains(e.target)) {
        setOpen(false);
      }
    };
    document.addEventListener("mousedown", handler);
    return () => document.removeEventListener("mousedown", handler);
  }, []);

  // mark all read
  const markAllRead = async () => {
    try {
      await NotificationService.MarkNotification();
      loadNotifications();
    } catch (err) {
      toast.error(err.response?.data?.message || "Failed to update notifications.");
    }
  };

  return (
    <div className="relative" ref={ref}>

      {/* BELL ICON */}
      <button
        onClick={() => setOpen(!open)}
        className="relative p-2 rounded-lg hover:bg-gray-100 transition"
      >
        <Bell size={22} />

        {unreadCount > 0 && (
          <span className="absolute -top-1 -right-1 bg-red-500 text-white text-xs w-5 h-5 flex items-center justify-center rounded-full">
            {unreadCount}
          </span>
        )}
      </button>

      {/* DROPDOWN */}
      {open && (
        <div className="
          absolute right-0 mt-3 w-[360px]
          bg-white border rounded-2xl shadow-xl
          overflow-hidden z-50
        ">

          {/* HEADER */}
          <div className="flex justify-between items-center px-4 py-3 border-b">
            <h4 className="font-semibold">Notifications</h4>

            {unreadCount > 0 && (
              <button
                onClick={markAllRead}
                className="flex items-center gap-1 text-sm text-blue-600 hover:underline"
              >
                <CheckCheck size={16}/>
                Mark all
              </button>
            )}
          </div>

          {/* LIST */}
          <div className="max-h-[400px] overflow-y-auto">

            {notifications.length === 0 && (
              <div className="flex flex-col items-center gap-2 text-gray-400 py-10">
                <Bell size={28} className="opacity-50" />
                <p className="text-sm text-gray-500">You're all caught up</p>
              </div>
            )}

            {notifications.map((n) => (
              <div
                key={n.id}
                className={`
                  px-4 py-3 border-b cursor-pointer
                  hover:bg-gray-50 transition
                  ${!n.is_read ? "bg-blue-50" : ""}
                `}
              >
                <p className="font-medium text-sm">
                  {n.title}
                </p>

                <p className="text-gray-600 text-sm mt-1">
                  {n.message}
                </p>

                <span className="text-xs text-gray-400 mt-2 block">
                  {new Date(n.created_at).toLocaleString()}
                </span>
              </div>
            ))}

          </div>

        </div>
      )}
    </div>
  );
}