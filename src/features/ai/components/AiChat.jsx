import { useEffect, useRef } from "react";

import AiMessage from "./AiMessage";
import AiTypingIndicator from "./AiTypingIndicator";

export default function AiChat({ messages, loading }) {
  const bottomRef = useRef(null);

  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages, loading]);

  return (
    <div className="flex-1 space-y-6 overflow-y-auto bg-muted/30 px-6 py-6">
      {messages.map((message) => (
        <AiMessage key={message.id} message={message} />
      ))}
      {loading && <AiTypingIndicator />}
      <div ref={bottomRef} />
    </div>
  );
}
