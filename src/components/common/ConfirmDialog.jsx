import { AnimatePresence, motion } from "framer-motion";

export default function ConfirmDialog({
  open,
  title,
  description,
  confirmLabel = "Confirm",
  cancelLabel = "Cancel",
  confirmClass = "bg-blue-600 hover:bg-blue-700",
  loading = false,
  onConfirm,
  onCancel,
}) {
  return (
    <AnimatePresence>
      {open && (
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          className="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 p-4"
          onClick={onCancel}
        >
          <motion.div
            initial={{ opacity: 0, scale: 0.95, y: 10 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={{ opacity: 0, scale: 0.95, y: 10 }}
            transition={{ duration: 0.15 }}
            onClick={(e) => e.stopPropagation()}
            className="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl"
          >
            <h3 className="text-lg font-semibold text-gray-800">{title}</h3>
            {description && (
              <p className="mt-2 text-sm text-gray-600">{description}</p>
            )}

            <div className="mt-6 flex justify-end gap-3">
              <button
                onClick={onCancel}
                disabled={loading}
                className="px-4 py-2 rounded-lg font-medium text-gray-600 hover:bg-gray-100 transition disabled:opacity-60"
              >
                {cancelLabel}
              </button>
              <button
                onClick={onConfirm}
                disabled={loading}
                className={`px-4 py-2 rounded-lg font-medium text-white transition disabled:opacity-60 ${confirmClass}`}
              >
                {loading ? "Please wait..." : confirmLabel}
              </button>
            </div>
          </motion.div>
        </motion.div>
      )}
    </AnimatePresence>
  );
}
