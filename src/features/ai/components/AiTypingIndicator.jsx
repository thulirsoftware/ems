import { Bot } from "lucide-react";

export default function AiTypingIndicator() {
  return (
    <div className="flex items-start gap-3">
      <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground">
        <Bot size={18} />
      </div>
      <div>
        <div className="mb-1 text-xs font-semibold text-muted-foreground">EMS AI</div>
        <div className="rounded-2xl bg-card px-4 py-3 shadow-sm">
          <div className="flex items-center gap-1.5">
            <span className="h-2 w-2 animate-bounce rounded-full bg-muted-foreground [animation-delay:-0.3s]" />
            <span className="h-2 w-2 animate-bounce rounded-full bg-muted-foreground [animation-delay:-0.15s]" />
            <span className="h-2 w-2 animate-bounce rounded-full bg-muted-foreground" />
          </div>
        </div>
      </div>
    </div>
  );
}
