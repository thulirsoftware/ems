export default function AiConversationListItem({ conversation, active, onClick }) {
  const formatDate = (date) => {
    const now = new Date();
    const updated = new Date(date);
    const diffMs = now - updated;
    const minutes = Math.floor(diffMs / 60000);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (minutes < 1) return "Just now";
    if (minutes < 60) return `${minutes} min ago`;
    if (hours < 24) return `${hours} hr${hours > 1 ? "s" : ""} ago`;
    if (days === 1) return "Yesterday";
    return updated.toLocaleDateString("en-GB");
  };

  return (
    <button
      onClick={onClick}
      className={`mb-1 w-full rounded-lg px-3 py-2.5 text-left transition ${
        active ? "bg-primary/10 ring-1 ring-primary/30" : "hover:bg-muted"
      }`}
    >
      <div className="truncate text-sm font-medium text-foreground">
        {conversation.title || "New Chat"}
      </div>
      <div className="mt-0.5 text-xs text-muted-foreground">{formatDate(conversation.updated_at)}</div>
    </button>
  );
}
