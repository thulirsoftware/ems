import ReactMarkdown from "react-markdown";
import remarkGfm from "remark-gfm";
import { Bot, User } from "lucide-react";

export default function AiMessage({ message }) {
  const isUser = message.type === "user";

  return (
    <div className={`flex items-start gap-3 ${isUser ? "flex-row-reverse" : ""}`}>
      <div
        className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-full ${
          isUser ? "bg-foreground text-background" : "bg-primary text-primary-foreground"
        }`}
      >
        {isUser ? <User size={18} /> : <Bot size={18} />}
      </div>

      <div className={`max-w-[78%] ${isUser ? "flex flex-col items-end" : ""}`}>
        <div className="mb-1 text-xs font-semibold text-muted-foreground">
          {isUser ? "You" : "EMS AI"}
        </div>

        <div
          className={`rounded-2xl px-4 py-3 text-sm leading-relaxed shadow-sm ${
            isUser ? "bg-primary text-primary-foreground" : "bg-card text-foreground"
          }`}
        >
          {isUser ? (
            <span className="whitespace-pre-wrap break-words">{message.message}</span>
          ) : (
            <div className="ai-markdown">
              <ReactMarkdown remarkPlugins={[remarkGfm]}>
                {message.message.replace(/\n{3,}/g, "\n\n").trim()}
              </ReactMarkdown>
            </div>
          )}
        </div>

        <div className="mt-1 text-[11px] text-muted-foreground">
          {new Date(message.created_at).toLocaleTimeString("en-US", {
            hour: "numeric",
            minute: "2-digit",
            hour12: true,
          })}
        </div>
      </div>
    </div>
  );
}
