import { Inbox } from "lucide-react";

export default function EmptyState({
  title = "Nothing here yet",
  description,
  icon: Icon = Inbox,
  action,
  className = "",
}) {
  return (
    <div className={`flex flex-col items-center justify-center gap-2 py-12 text-center ${className}`}>
      <Icon className="w-9 h-9 mb-1 text-gray-300" />
      <p className="text-sm font-medium text-gray-500">{title}</p>
      {description && <p className="text-xs text-gray-400 max-w-xs">{description}</p>}
      {action && <div className="mt-2">{action}</div>}
    </div>
  );
}

export function EmptyTableRow({ colSpan, title = "No records found", description }) {
  return (
    <tr>
      <td colSpan={colSpan}>
        <EmptyState title={title} description={description} />
      </td>
    </tr>
  );
}
