import { useMemo, useState } from "react";
import { Plus, Search } from "lucide-react";

import AiConversationListItem from "./AiConversationListItem";

function Spinner({ size = 24 }) {
  return (
    <div
      role="status"
      aria-label="Loading"
      className="inline-block animate-spin rounded-full border-2 border-current border-t-transparent text-primary"
      style={{ width: size, height: size }}
    />
  );
}

export default function AiConversationSidebar({
  conversations,
  selectedConversation,
  loading,
  onSelect,
  onCreate,
}) {
  const [search, setSearch] = useState("");

  const filtered = useMemo(
    () =>
      conversations.filter((conversation) =>
        (conversation.title || "New Chat").toLowerCase().includes(search.toLowerCase())
      ),
    [conversations, search]
  );

  return (
    <aside className="flex w-72 shrink-0 flex-col rounded-xl border border-border bg-card overflow-hidden shadow-sm">
      <div className="p-4">
        <button
          onClick={onCreate}
          disabled={loading}
          className="flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:opacity-90 disabled:opacity-50"
        >
          <Plus size={18} />
          New Chat
        </button>
      </div>

      <div className="mx-4 mb-4 flex items-center gap-2 rounded-lg border border-input bg-background px-3 py-2">
        <Search size={16} className="text-muted-foreground" />
        <input
          placeholder="Search conversations..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="w-full bg-transparent text-sm outline-none placeholder:text-muted-foreground"
        />
      </div>

      <div className="px-4 pb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
        Conversations
      </div>

      <div className="flex-1 overflow-y-auto px-2 pb-4">
        {loading ? (
          <div className="flex flex-col items-center gap-3 py-16 text-sm text-muted-foreground">
            <Spinner size={24} />
            Loading...
          </div>
        ) : filtered.length === 0 ? (
          <div className="py-16 text-center text-sm text-muted-foreground">No conversations</div>
        ) : (
          filtered.map((conversation) => (
            <AiConversationListItem
              key={conversation.id}
              conversation={conversation}
              active={selectedConversation?.id === conversation.id}
              onClick={() => onSelect(conversation.id)}
            />
          ))
        )}
      </div>
    </aside>
  );
}
