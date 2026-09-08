import { Clock, Construction, ArrowLeft } from "lucide-react";
import { useNavigate } from "react-router-dom";

export default function ComingSoonPage() {

  const navigate = useNavigate();

  return (
    <div className="flex items-center justify-center py-24">

      <div className="bg-white rounded-2xl  p-12 text-center max-w-lg w-full">

        

        {/* TITLE */}
        <h1 className="text-3xl font-semibold text-gray-800 mb-3">
          Coming Soon
        </h1>

        {/* DESCRIPTION */}
        <p className="text-gray-500 mb-8">
          This feature is currently under development.
          We are preparing something great for you.
        </p>

        {/* BACK BUTTON */}
        <button
          onClick={() => navigate(-1)}
          className="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-medium transition shadow-sm hover:shadow-lg"
        >
          <ArrowLeft size={18}/>
          Go Back
        </button>

      </div>

    </div>
  );
}