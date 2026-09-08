import http from "../lib/axios";

const NotificationService = {
    getNotificationList: async () => {
        const res = await http.get("/user/notifications");
        return res.data;
    },
    MarkNotification: async () => {
        const res = await http.post("/user/notifications/read-all");
        return res.data;
    },



};

export default NotificationService;
