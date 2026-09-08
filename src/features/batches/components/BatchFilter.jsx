import { Search, RotateCcw } from "lucide-react";

export default function BatchFilter({
    filters,
    onChange,
    assessments = [],
}) {
    const handleChange = (field, value) => {
        onChange({
            ...filters,
            [field]: value,
        });
    };

    const handleReset = () => {
        onChange({
            search: "",
            assessment: "",
            publishDate: "",
        });
    };

    return (
        <div className="bg-white border rounded-lg p-4 mb-6">
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

                {/* Search */}
                <div className="relative">
                    <Search
                        size={18}
                        className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
                    />

                    <input
                        type="text"
                        placeholder="Search Batch..."
                        value={filters.search}
                        onChange={(e) =>
                            handleChange("search", e.target.value)
                        }
                        className="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                </div>

                {/* Assessment */}
                <select
                    value={filters.assessment}
                    onChange={(e) =>
                        handleChange("assessment", e.target.value)
                    }
                    className="w-full px-4 py-2 border rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                >
                    <option value="">All Assessments</option>

                    {assessments.map((assessment) => (
                        <option
                            key={assessment.id}
                            value={assessment.id}
                        >
                            {assessment.name}
                        </option>
                    ))}
                </select>

                {/* Publish Date */}
                <input
                    type="date"
                    value={filters.publishDate}
                    onChange={(e) =>
                        handleChange("publishDate", e.target.value)
                    }
                    className="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                />

                {/* Reset */}
                <button
                    onClick={handleReset}
                    className="flex items-center justify-center gap-2 border rounded-lg px-4 py-2 hover:bg-gray-100 transition"
                >
                    <RotateCcw size={16} />
                    Reset
                </button>
            </div>
        </div>
    );
}