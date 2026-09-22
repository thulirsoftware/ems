import { ClipboardList, GraduationCap, FileBarChart2, LayoutDashboard, Sparkles } from "lucide-react";

const SUGGESTIONS = [
  { icon: ClipboardList, label: "Assessments", prompt: "Show me all my active assessments." },
  { icon: GraduationCap, label: "Batches", prompt: "List my upcoming batches." },
  { icon: FileBarChart2, label: "Reports", prompt: "Give me a summary report across all my assessments." },
  { icon: LayoutDashboard, label: "Dashboard", prompt: "Give me a quick summary of my dashboard." },
];

export default function AiWelcome({ name, onSuggestionClick }) {
  return (
    <div className="flex flex-1 flex-col items-center justify-center overflow-y-auto p-10 text-center">
      <div className="mb-6 flex h-20 w-20 items-center justify-center rounded-2xl bg-primary text-primary-foreground">
        <Sparkles size={36} />
      </div>
      <h2 className="text-2xl font-bold text-foreground">
        {name ? `Hi ${name}, I'm the EMS AI` : "AI Assistant"}
      </h2>
      <p className="mt-3 max-w-md text-sm text-muted-foreground">
        Ask me anything about your assessments, batches, candidates, results or reports.
      </p>

      <div className="mt-8 grid w-full max-w-lg grid-cols-2 gap-3">
        {SUGGESTIONS.map((item) => (
          <button
            key={item.label}
            onClick={() => onSuggestionClick(item.prompt)}
            className="flex items-center gap-3 rounded-xl border border-border bg-card p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-md"
          >
            <item.icon size={20} className="shrink-0 text-primary" />
            <span className="text-sm font-medium text-foreground">{item.label}</span>
          </button>
        ))}
      </div>
    </div>
  );
}
