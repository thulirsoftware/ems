import { Button } from "../../components/ui/button";
import { Plus } from "lucide-react";

export default function PageHeader({
    title,
    subtitle,
    actionLabel,
    onAction,
    icon: Icon = Plus,
}) {
    // Determine layout based on button presence
    const hasAction = actionLabel && onAction;

    return (
        <div
            className={`flex items-center w-full mb-6 ${hasAction ? "justify-between" : "justify-start"
                }`}
        >
            {/* Left: Title + Subtitle */}
            <div>
                <h1 className="text-2xl text-[hsl(var(--page-header-text))]  font-semibold leading-tight">{title}</h1>
                {subtitle && (
                    <p className="text-sm text-gray-500 mt-1">{subtitle}</p>
                )}
            </div>

            {/* Right: Optional Button */}
            {hasAction && (
                <Button
                    onClick={onAction}
                    className="flex items-center gap-2 px-4 py-2 rounded-md bg-purple-600 hover:bg-purple-700 text-white"
                >
                    <Icon size={16} />
                    {actionLabel}
                </Button>
            )}
        </div>
    );
}
