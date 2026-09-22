import { Link } from "react-router-dom";
import { ArrowLeft, Menu, PanelLeftClose, Sparkles, Trash2 } from "lucide-react";

export default function AiHeader({
  conversation,
  sidebarOpen,
  onOpenSidebar,
  onToggleSidebar,
  onDelete,
  backTo,
  subtitle,
}) {
  return (
    <div className="flex items-center justify-between border-b border-border px-5 py-4">
      <div className="flex items-center gap-3">
        <Link
          to={backTo}
          title="Back to Dashboard"
          className="flex h-10 w-10 items-center justify-center rounded-lg bg-muted text-foreground transition hover:bg-accent"
        >
          <ArrowLeft size={18} />
        </Link>

        <button
          onClick={sidebarOpen ? onToggleSidebar : onOpenSidebar}
          title={sidebarOpen ? "Hide conversations" : "Show conversations"}
          className="flex h-10 w-10 items-center justify-center rounded-lg bg-muted text-foreground transition hover:bg-accent"
        >
          {sidebarOpen ? <PanelLeftClose size={18} /> : <Menu size={18} />}
        </button>

        <div className="flex h-11 w-11 items-center justify-center rounded-lg bg-primary text-primary-foreground">
          <Sparkles size={20} />
        </div>

        <div>
          <h2 className="text-base font-semibold text-foreground">
            {conversation ? conversation.title || "New Chat" : "AI Assistant"}
          </h2>
          {subtitle && <p className="text-xs text-muted-foreground">{subtitle}</p>}
        </div>
      </div>

      {conversation && (
        <button
          onClick={onDelete}
          title="Delete conversation"
          className="flex h-10 w-10 items-center justify-center rounded-lg bg-destructive/10 text-destructive transition hover:bg-destructive/20"
        >
          <Trash2 size={18} />
        </button>
      )}
    </div>
  );
}
