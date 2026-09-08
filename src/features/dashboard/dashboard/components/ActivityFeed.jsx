import { ActivitySquare } from "lucide-react";

export default function ActivityFeed() {

  const activities = [
    "New assessment created: React Basics",
    "Student Ravi completed Python Test",
    "Admin updated SQL Evaluation",
    "25 students assigned new exam",
  ];

  return (
    <div className="bg-white border rounded-xl p-6">

      <div className="flex items-center gap-2 mb-4">
        <ActivitySquare size={20} className="text-indigo-600"/>
        <h3 className="font-semibold">
          Recent Platform Activity
        </h3>
      </div>

      <ul className="space-y-3 text-sm text-gray-600">
        {activities.map((a, i) => (
          <li key={i} className="flex gap-3">
            <span className="w-2 h-2 mt-2 bg-indigo-500 rounded-full"/>
            {a}
          </li>
        ))}
      </ul>
    </div>
  );
}