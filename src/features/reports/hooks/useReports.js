import { useEffect, useState } from "react";
import ReportService from "../../../services/report.service";

export default function useReports() {

    const [loading, setLoading] = useState(true);

    const [reports, setReports] = useState({});

    const loadReports = async () => {

        setLoading(true);

        try {

            const data = await ReportService.getReports();

            setReports(data);

        } finally {

            setLoading(false);

        }

    };

    useEffect(() => {

        loadReports();

    }, []);

    return {

        reports,

        loading,

        reload: loadReports

    };

}