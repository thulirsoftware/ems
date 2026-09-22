import { useEffect, useRef } from "react";
import { Send } from "lucide-react";

export default function AiInput({ value, onChange, onSend, loading, placeholder }) {
  const textareaRef = useRef(null);

  useEffect(() => {
    textareaRef.current?.focus();
  }, []);

  useEffect(() => {
    const textarea = textareaRef.current;
    if (!textarea) return;
    textarea.style.height = "auto";
    textarea.style.height = `${Math.min(textarea.scrollHeight, 160)}px`;
  }, [value]);

  const send = () => {
    if (!value.trim() || loading) return;
    onSend(value.trim());
    onChange("");
    if (textareaRef.current) textareaRef.current.style.height = "auto";
  };

  const handleKeyDown = (e) => {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      send();
    }
  };

  return (
    <div className="flex items-end gap-3 border-t border-border p-4">
      <textarea
        ref={textareaRef}
        rows={1}
        value={value}
        disabled={loading}
        placeholder={placeholder || "Ask anything about the Exam Management System..."}
        onChange={(e) => onChange(e.target.value)}
        onKeyDown={handleKeyDown}
        className="max-h-40 min-h-[48px] flex-1 resize-none rounded-xl border border-input bg-background px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-ring disabled:opacity-60"
      />
      <button
        onClick={send}
        disabled={loading || !value.trim()}
        className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground transition hover:opacity-90 disabled:opacity-40"
      >
        <Send size={20} />
      </button>
    </div>
  );
}
