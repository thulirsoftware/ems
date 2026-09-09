import { useCallback, useState } from "react";
import ConfirmDialog from "../components/common/ConfirmDialog";

/**
 * Promise-based replacement for window.confirm() that renders the app's
 * styled ConfirmDialog instead of the native browser dialog.
 *
 * const [confirm, confirmDialog] = useConfirm();
 * ...
 * if (!(await confirm({ title: "Delete this item?" }))) return;
 * ...
 * return <>{confirmDialog}</>;
 */
export function useConfirm() {
  const [state, setState] = useState(null);

  const confirm = useCallback((options = {}) => {
    return new Promise((resolve) => {
      setState({ ...options, resolve });
    });
  }, []);

  const respond = (result) => {
    state?.resolve(result);
    setState(null);
  };

  const confirmDialog = (
    <ConfirmDialog
      open={!!state}
      title={state?.title}
      description={state?.description}
      confirmLabel={state?.confirmLabel}
      cancelLabel={state?.cancelLabel}
      variant={state?.variant}
      onConfirm={() => respond(true)}
      onCancel={() => respond(false)}
    />
  );

  return [confirm, confirmDialog];
}
