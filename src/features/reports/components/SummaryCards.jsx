import {
  Users,
  ClipboardList,
  BookOpen,
  FileCheck,
  CheckCircle,
  TrendingUp,
  Trophy,
  Award,
  AlertTriangle,
  Layers,
} from "lucide-react";

const icons = {
  Students: Users,
  Assessments: ClipboardList,
  Assignments: BookOpen,
  Attempts: FileCheck,
  Completed: CheckCircle,
  "Completion %": TrendingUp,
  "Average Score": Award,
  "Highest Score": Trophy,
  "Lowest Score": AlertTriangle,
  Batches: Layers,
};

const gradients = {
  indigo: "from-indigo-500 to-indigo-700",
  emerald: "from-emerald-500 to-emerald-700",
  orange: "from-orange-500 to-orange-700",
  sky: "from-sky-500 to-sky-700",
  green: "from-green-500 to-green-700",
  rose: "from-rose-500 to-pink-600",
  violet: "from-violet-500 to-purple-700",
  amber: "from-amber-500 to-orange-600",
  red: "from-red-500 to-red-700",
  cyan: "from-cyan-500 to-blue-700",
};

export default function SummaryCards({ data = [] }) {
  return (
    <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
      {data.map((item, index) => {
        const Icon = icons[item.title] || Users;

        return (
          <div
            key={index}
            className="group relative overflow-hidden rounded-2xl bg-white shadow-sm border border-gray-100 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
          >
            {/* Background Glow */}
            <div
              className={`absolute -right-8 -top-8 h-32 w-32 rounded-full bg-gradient-to-br ${
                gradients[item.color]
              } opacity-10 blur-3xl`}
            />

            {/* Card Body */}
            <div className="relative p-6">
              <div className="flex items-start justify-between">
                <div>
                  <p className="text-sm text-gray-500 font-medium">
                    {item.title}
                  </p>

                  <h2 className="mt-3 text-3xl font-bold text-gray-900">
                    {item.value}
                  </h2>
                </div>

                <div
                  className={`flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br ${
                    gradients[item.color]
                  } text-white shadow-lg`}
                >
                  <Icon size={26} />
                </div>
              </div>

              {/* Bottom Accent */}
              <div
                className={`mt-6 h-1 rounded-full bg-gradient-to-r ${
                  gradients[item.color]
                }`}
              />
            </div>
          </div>
        );
      })}
    </div>
  );
}