import { useEffect } from "react";
import { useThemeStore } from "../../store/themeStore";

const ThemeProvider = ({ children }) => {
    const { theme } = useThemeStore();

    useEffect(() => {
        const root = document.documentElement;
        if (theme === "dark") root.classList.add("dark");
        else root.classList.remove("dark");
    }, [theme]);

    return  <>{children}</>;
};

export default ThemeProvider;
