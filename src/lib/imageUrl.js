const UPLOAD_BASE = import.meta.env.VITE_UPLOADS_BASE;

export const getImageUrl = (path) => {
    if (!path) return "/no-image.png";

    if (path.startsWith("http")) return path;

    // Remove possible leading slash
    const clean = path.startsWith("/") ? path.substring(1) : path;

    return `${UPLOAD_BASE}/${clean}`;
};
