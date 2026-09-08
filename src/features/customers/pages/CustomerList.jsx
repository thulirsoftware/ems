import StatCard from "../../../components/common/StatCard";
import PageHeader from "../../../components/common/PageHeader";
import CustomerService from "../../../services/customer.service";
import { useEffect, useState } from "react";
import { Eye, ChevronLeft, ChevronRight, Search } from "lucide-react";
import { useBranchStore } from "../../../store/branchStore";
import { useNavigate } from "react-router-dom";

export default function CustomerList() {
  const navigate = useNavigate();
  const branch = useBranchStore((s) => s.branch);

  const [customerstats, setCustomerstats] = useState({});
  const [customers, setCustomers] = useState([]);
  const [loading, setLoading] = useState(false);

  // pagination
  const [page, setPage] = useState(1);
  const [meta, setMeta] = useState(null);

  // search & filters
  const [search, setSearch] = useState("");
  const [categories, setCategories] = useState([]);
  const [filters, setFilters] = useState({
    customer_category: "",
  });

  /* ================= LOAD CATEGORIES ================= */
  useEffect(() => {
    loadCategories();
  }, []);

  const loadCategories = async () => {
    try {
      const res = await CustomerService.getCategories();
      setCategories(res.data);
    } catch (e) {
      console.error(e);
    }
  };

  /* ================= RESET PAGE ON BRANCH CHANGE ================= */
  useEffect(() => {
    if (!branch) return;
    setPage(1);
  }, [branch]);

  /* ================= FETCH DATA ================= */
  useEffect(() => {
    if (!branch) return;
    loadData();
  }, [branch, page, search, JSON.stringify(filters)]);

  const loadData = async () => {
    try {
      setLoading(true);

      const [stats, list] = await Promise.all([
        CustomerService.getStats(branch),
        CustomerService.getAll({
          branch,
          page,
          search,
          ...filters,
        }),
      ]);

      setCustomerstats(stats);
      setCustomers(list.data);
      setMeta(list.meta);
    } catch (err) {
      console.error("Customer load error:", err);
    } finally {
      setLoading(false);
    }
  };

  /* ================= FILTER HELPERS ================= */
  const applyFilter = (key, value) => {
    setFilters((prev) => ({ ...prev, [key]: value }));
    setPage(1);
  };

  const resetFilters = () => {
    setFilters({ customer_category: "" });
    setSearch("");
    setPage(1);
  };

  return (
    <section className="space-y-6">
      <div className="">
        <div className="flex flex-col lg:flex-row lg:items-center gap-6 justify-between">
          <div>
            <PageHeader
              title="Customer List"
              subtitle={`Viewing customers for ${branch?.toUpperCase()} (Page ${page})`}
            />

          </div>
          <div className="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
            {/* SEARCH + FILTER BAR */}
            <div className=" p-4 shadow-sm">
              <div className="flex flex-col lg:flex-row lg:items-center gap-4 justify-between">

                {/* SEARCH */}
                <div className="relative w-full lg:w-80">
                  <Search className="absolute left-3 top-2.5 text-gray-400" size={18} />
                  <input
                    placeholder="Search customer name / code / mobile..."
                    value={search}
                    onChange={(e) => {
                      setSearch(e.target.value);
                      setPage(1);
                    }}
                    className="
                w-full pl-10 pr-4 py-2.5
                rounded-lg border border-gray-200 bg-gray-50
                focus:bg-white focus:ring-2 focus:ring-indigo-500/30
                focus:border-indigo-500 outline-none transition
              "
                  />
                </div>

                {/* CATEGORY FILTER */}
                <select
                  value={filters.customer_category}
                  onChange={(e) => applyFilter("customer_category", e.target.value)}
                  className="
              w-full lg:w-64 px-3 py-2.5
              rounded-lg border border-gray-200 bg-gray-50
              focus:bg-white focus:ring-2 focus:ring-indigo-500/30
              focus:border-indigo-500 outline-none transition
            "
                >
                  <option value="">All Categories</option>
                  {categories.map((c) => (
                    <option key={c.category_code} value={c.category_code}>
                      {c.category_name}
                    </option>
                  ))}
                </select>

                {/* RESET */}
                {(search || filters.customer_category) && (
                  <button
                    onClick={resetFilters}
                    className="px-4 py-2.5 rounded-lg border bg-white hover:bg-gray-100 transition"
                  >
                    Reset
                  </button>
                )}
              </div>
            </div>

          </div>

        </div>

      </div>

      {/* HEADER */}




      {/* STAT CARDS */}
      

      {/* TABLE */}
      <div className="rounded-xl shadow border overflow-hidden">
        <div className="overflow-x-auto">
          {loading ? (
            <div className="p-6 text-center text-gray-500">Loading customers...</div>
          ) : (
            <>
              <table className="w-full text-sm text-left">
                <thead className="bg-gray-50 dark:bg-gray-800 text-[hsl(var(--table-head-text))]">
                  <tr>
                    <th className="px-6 py-3">Customer Code</th>
                    <th className="px-6 py-3">Customer Name</th>
                    <th className="px-6 py-3">GSTIN</th>
                    <th className="px-6 py-3">Mobile</th>
                    <th className="px-6 py-3">Category</th>
                    <th className="px-6 py-3">Status</th>
                    <th className="px-6 py-3 text-center">Action</th>
                  </tr>
                </thead>

                <tbody className="divide-y">
                  {customers.map((c) => (
                    <tr key={c.customer_sk} className="bg-gray-50 dark:bg-gray-800 text-[hsl(var(--table-body-text))]">
                      <td className="px-6 py-4 font-medium">{c.customer_code}</td>
                      <td className="px-6 py-4 font-medium">{c.customer_name}</td>
                      <td className="px-6 py-4">{c.gst_number ?? "-"}</td>
                      <td className="px-6 py-4">{c.mobile_number ?? "-"}</td>
                      <td className="px-6 py-4">{c.category?.category_name ?? "-"}</td>
                      <td className="px-6 py-4">
                        <span
                          className={`px-3 py-1 rounded-full text-xs font-medium ${c.status === "Active"
                            ? "bg-green-100 text-green-700"
                            : "bg-red-100 text-red-700"
                            }`}
                        >
                          {c.status}
                        </span>
                      </td>
                      <td className="px-6 py-4 text-center">
                        <button
                          onClick={() => navigate(`/admin/customers/${c.customer_sk}`)}
                          className="p-2 rounded-lg hover:bg-gray-200 transition"
                        >
                          <Eye className="w-5 h-5" />
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>

              {/* PAGINATION */}
              {meta && (
                <div className="flex items-center justify-between p-4 border-t bg-gray-50">
                  <span className="text-sm text-gray-600">
                    Page {meta.current_page} of {Math.ceil(meta.total / meta.per_page)}
                  </span>

                  <div className="flex gap-2">
                    <button
                      disabled={page === 1}
                      onClick={() => setPage((p) => p - 1)}
                      className="px-3 py-1 rounded border disabled:opacity-50"
                    >
                      <ChevronLeft size={16} />
                    </button>

                    <button
                      disabled={meta.current_page >= Math.ceil(meta.total / meta.per_page)}
                      onClick={() => setPage((p) => p + 1)}
                      className="px-3 py-1 rounded border disabled:opacity-50"
                    >
                      <ChevronRight size={16} />
                    </button>
                  </div>
                </div>
              )}
            </>
          )}
        </div>
      </div>
    </section>
  );
}
