import { useNavigate } from "react-router-dom";
import { ArrowLeft } from "lucide-react";

export default function BackButton({ label = "Back", to = -1 }) {
    const navigate = useNavigate();

    return (
        <button
            onClick={() => navigate(to)}
            className="
                flex items-center gap-2 
                text-black hover:text-gray-600 
                font-medium my-4
            "
        >
            <ArrowLeft size={20} />
            <span>{label}</span>
        </button>
    );
}
