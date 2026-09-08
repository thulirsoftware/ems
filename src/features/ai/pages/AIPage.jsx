import { useState } from "react";
import AIService from "../../../services/ai.service";
import { Bot, SendHorizontal, User } from "lucide-react";

export default function AIPage() {
    const [message, setMessage] = useState("");
    const [loading, setLoading] = useState(false);

    const [messages, setMessages] = useState([
        {
            role: "assistant",
            text: "Hello 👋 I'm your AI Assistant. How can I help you?",
        },
    ]);

    const sendMessage = async () => {
        if (!message.trim()) return;

        const userMessage = {
            role: "user",
            text: message,
        };

        setMessages((prev) => [...prev, userMessage]);

        const prompt = message;

        setMessage("");
        setLoading(true);

        try {
            const res = await AIService.chat(prompt);

            setMessages((prev) => [
                ...prev,
                {
                    role: "assistant",
                    text: res.reply || JSON.stringify(res),
                },
            ]);
        } catch (err) {
            setMessages((prev) => [
                ...prev,
                {
                    role: "assistant",
                    text: err.response?.data?.message || "Something went wrong.",
                },
            ]);
        }

        setLoading(false);
    };

    return (
        <div className=" bg-gray-100 flex justify-center items-center">
            <div className="bg-white rounded-xl shadow-lg w-full  h-[80vh] flex flex-col">

                <div className="bg-blue-600 text-white p-4 rounded-t-xl flex items-center gap-2">
                    <Bot size={24} />
                    <h2 className="font-semibold text-lg">
                        AI Assistant
                    </h2>
                </div>

                <div className="flex-1 overflow-y-auto p-6 space-y-5">
                    {messages.map((msg, index) => (
                        <div
                            key={index}
                            className={`flex ${
                                msg.role === "user"
                                    ? "justify-end"
                                    : "justify-start"
                            }`}
                        >
                            <div
                                className={`max-w-[70%] rounded-xl p-4 shadow ${
                                    msg.role === "user"
                                        ? "bg-blue-600 text-white"
                                        : "bg-gray-200"
                                }`}
                            >
                                <div className="flex gap-2 items-center mb-2">
                                    {msg.role === "assistant" ? (
                                        <Bot size={18} />
                                    ) : (
                                        <User size={18} />
                                    )}

                                    <span className="font-semibold">
                                        {msg.role === "assistant"
                                            ? "AI"
                                            : "You"}
                                    </span>
                                </div>

                                <div className="whitespace-pre-wrap">
                                    {msg.text}
                                </div>
                            </div>
                        </div>
                    ))}

                    {loading && (
                        <div className="text-gray-500">
                            AI is typing...
                        </div>
                    )}
                </div>

                <div className="border-t p-4 flex gap-3">
                    <input
                        className="flex-1 border rounded-lg px-4 py-3 focus:outline-none"
                        placeholder="Ask anything..."
                        value={message}
                        onChange={(e) => setMessage(e.target.value)}
                        onKeyDown={(e) =>
                            e.key === "Enter" && sendMessage()
                        }
                    />

                    <button
                        onClick={sendMessage}
                        disabled={loading}
                        className="bg-blue-600 hover:bg-blue-700 text-white px-5 rounded-lg flex items-center gap-2"
                    >
                        <SendHorizontal size={18} />
                        Send
                    </button>
                </div>
            </div>
        </div>
    );
}