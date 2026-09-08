import {
  ClipboardPlus,
  CheckCircle,
  Users,
  FileText,
} from "lucide-react";

export default function ActivityTimeline() {

  const activities = [
    {
      text: "React Assessment created",
      time: "2 mins ago",
      icon: ClipboardPlus,
      color: "text-indigo-600 bg-indigo-50",
    },
    {
      text: "45 students completed Python Test",
      time: "15 mins ago",
      icon: CheckCircle,
      color: "text-green-600 bg-green-50",
    },
    {
      text: "New batch assigned assessment",
      time: "1 hour ago",
      icon: Users,
      color: "text-orange-600 bg-orange-50",
    },
    {
      text: "System report generated",
      time: "Today 10:30 AM",
      icon: FileText,
      color: "text-blue-600 bg-blue-50",
    },
  ];

  return (
    <div className="bg-white rounded-2xl border p-6">

      {/* HEADER */}
      <div className="flex justify-between items-center mb-6">
        <h3 className="font-semibold text-gray-800">
          Recent Activity
        </h3>

        <span className="text-sm text-gray-400">
          Live Updates
        </span>
      </div>

      {/* TIMELINE */}
      <div className="relative">

        {/* vertical line */}
        <div className="absolute left-5 top-0 bottom-0 w-[2px] bg-gray-200" />

        <div className="space-y-6">

          {activities.map((item, i) => {
            const Icon = item.icon;

            return (
              <div key={i} className="flex gap-4 group">

                {/* ICON */}
                <div className={`
                  relative z-10
                  w-10 h-10 flex items-center justify-center
                  rounded-full ${item.color}
                  group-hover:scale-110 transition
                `}>
                  <Icon size={18}/>
                </div>

                {/* CONTENT */}
                <div className="flex-1">
                  <p className="text-sm font-medium text-gray-700">
                    {item.text}
                  </p>

                  <span className="text-xs text-gray-400">
                    {item.time}
                  </span>
                </div>

              </div>
            );
          })}

        </div>
      </div>

    </div>
  );
}