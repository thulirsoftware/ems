import { useEffect, useRef, useState } from "react";
import { toast } from "sonner";

import AIService from "../../../services/ai.service";
import { useAuthStore } from "../../../store/authStore";
import { useConfirm } from "../../../hooks/useConfirm";

import AiConversationSidebar from "../components/AiConversationSidebar";
import AiHeader from "../components/AiHeader";
import AiChat from "../components/AiChat";
import AiInput from "../components/AiInput";
import AiWelcome from "../components/AiWelcome";

import "../ai.css";

export default function AIAssistantPage() {
  const admin = useAuthStore((state) => state.admin);
  const [confirm, confirmDialog] = useConfirm();

  const [conversations, setConversations] = useState([]);
  const [selectedConversation, setSelectedConversation] = useState(null);
  const [messages, setMessages] = useState([]);
  const [loading, setLoading] = useState(true);
  const [sending, setSending] = useState(false);
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [input, setInput] = useState("");

  // Mirrors selectedConversation?.id, updated synchronously at every point
  // selectedConversation changes, so an in-flight send/open that resolves
  // after the admin has switched to a different conversation can tell it's
  // stale and skip overwriting the now-visible chat.
  const selectedConversationIdRef = useRef(null);

  const loadConversations = async () => {
    const data = await AIService.getConversations();
    setConversations(data);
    return data;
  };

  useEffect(() => {
    (async () => {
      try {
        setLoading(true);
        await loadConversations();
      } catch (error) {
        toast.error(error?.response?.data?.message || "Failed to load conversations.");
      } finally {
        setLoading(false);
      }
    })();
  }, []);

  const openConversation = async (id, showLoader = true) => {
    try {
      if (showLoader) setLoading(true);

      const data = await AIService.getConversation(id);

      selectedConversationIdRef.current = data.id;
      setSelectedConversation(data);
      setMessages(
        data.messages.map((message) => ({
          id: message.id,
          type: message.role === "assistant" ? "ai" : "user",
          message: message.message,
          created_at: message.created_at,
        }))
      );
    } catch (error) {
      toast.error(error?.response?.data?.message || "Failed to open conversation.");
    } finally {
      setLoading(false);
    }
  };

  const handleCreateConversation = async () => {
    try {
      const conversation = await AIService.createConversation();
      await loadConversations();
      await openConversation(conversation.id);
      setSidebarOpen(true);
    } catch (error) {
      toast.error(error?.response?.data?.message || "Failed to start a new conversation.");
    }
  };

  const handleSend = async (text) => {
    try {
      setSending(true);

      let conversation = selectedConversation;

      if (!conversation) {
        conversation = await AIService.createConversation();
        await loadConversations();
        selectedConversationIdRef.current = conversation.id;
        setSelectedConversation(conversation);
      }

      const optimisticMessage = {
        id: `temp-${Date.now()}`,
        type: "user",
        message: text,
        created_at: new Date().toISOString(),
      };

      // Only touch the visible chat if the admin is still looking at this
      // conversation — they may switch to a different one while this send
      // is in flight, and that view must not be yanked back.
      const stillSelected = () => selectedConversationIdRef.current === conversation.id;

      if (stillSelected()) {
        setMessages((prev) => [...prev, optimisticMessage]);
      }

      try {
        await AIService.sendMessage(conversation.id, text);
        await loadConversations();

        if (stillSelected()) {
          await openConversation(conversation.id, false);
        }
      } catch (error) {
        if (stillSelected()) {
          setMessages((prev) => prev.filter((message) => message.id !== optimisticMessage.id));
          setInput(text);
        }
        toast.error(error?.response?.data?.message || "The AI assistant could not respond. Please try again.");
      }
    } catch (error) {
      toast.error(error?.response?.data?.message || "Failed to start a new conversation.");
    } finally {
      setSending(false);
    }
  };

  const handleDeleteConversation = async () => {
    if (!selectedConversation) return;

    const confirmed = await confirm({
      title: "Delete this conversation?",
      description: "All messages in this conversation will be removed permanently and cannot be recovered.",
      confirmLabel: "Delete",
      variant: "danger",
    });

    if (!confirmed) return;

    try {
      await AIService.deleteConversation(selectedConversation.id);
      await loadConversations();
      selectedConversationIdRef.current = null;
      setSelectedConversation(null);
      setMessages([]);
    } catch (error) {
      toast.error(error?.response?.data?.message || "Failed to delete conversation.");
    }
  };

  return (
    <div className="flex h-full gap-4">
      {sidebarOpen && (
        <AiConversationSidebar
          conversations={conversations}
          selectedConversation={selectedConversation}
          loading={loading}
          onSelect={(id) => openConversation(id)}
          onCreate={handleCreateConversation}
        />
      )}

      <div className="flex flex-1 flex-col overflow-hidden rounded-xl border border-border bg-card shadow-sm">
        <AiHeader
          conversation={selectedConversation}
          sidebarOpen={sidebarOpen}
          onOpenSidebar={() => setSidebarOpen(true)}
          onToggleSidebar={() => setSidebarOpen(false)}
          onDelete={handleDeleteConversation}
          backTo="/admin/dashboard"
          subtitle="Ask anything about the Exam Management System"
        />

        {selectedConversation ? (
          <>
            <AiChat messages={messages} loading={sending} />
            <AiInput value={input} onChange={setInput} loading={loading || sending} onSend={handleSend} />
          </>
        ) : (
          <>
            <AiWelcome name={admin?.name} onSuggestionClick={setInput} />
            <AiInput value={input} onChange={setInput} loading={loading || sending} onSend={handleSend} />
          </>
        )}
      </div>

      {confirmDialog}
    </div>
  );
}
