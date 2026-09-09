import { useEffect, useState, useCallback } from "react";

// Shared data-fetching for the assessment/result list sections (running,
// today, upcoming, missed, completed). They all normalize the same
// list-or-{data:list} response shape and need the same loading/error split —
// centralizing it means a real failure (error) is never mistaken for "no
// exams" (empty), which every ad-hoc copy of this used to conflate.
export function useAssessmentList(fetcher, { pollInterval } = {}) {
  const [data, setData] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const load = useCallback(async () => {
    try {
      const res = await fetcher();
      const list = Array.isArray(res?.data) ? res.data : Array.isArray(res) ? res : [];
      setData(list.filter(Boolean));
      setError(null);
    } catch (err) {
      setError(err);
    } finally {
      setLoading(false);
    }
  }, [fetcher]);

  useEffect(() => {
    let cancelled = false;

    const run = async () => {
      if (cancelled) return;
      await load();
    };
    run();

    let interval;
    if (pollInterval) {
      interval = setInterval(() => {
        if (!document.hidden) run();
      }, pollInterval);
    }

    return () => {
      cancelled = true;
      if (interval) clearInterval(interval);
    };
  }, [load, pollInterval]);

  return { data, loading, error, reload: load };
}
