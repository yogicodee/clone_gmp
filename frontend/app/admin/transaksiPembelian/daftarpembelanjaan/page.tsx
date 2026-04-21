"use client";

import { useState, useMemo, useEffect } from "react";
import { ArrowUpDown, Eye } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { useRouter } from "next/navigation";
import { useFetch } from "@/hooks/useFetch";
import api from "@/lib/api";

/* ================= TYPE ================= */
type Order = {
    id: number;
    tanggal_pesan: string;
};

type FormType = Omit<Order, "id">;

export default function Page() {
    const router = useRouter();

    const { data, loading, refetch } = useFetch<Order>("/order-penawaran"); // Get Data via useFetch

    const [form, setForm] = useState<FormType>({
        tanggal_pesan: "",
    });

    const [editId, setEditId] = useState<number | null>(null);
    const [openForm, setOpenForm] = useState(false);
    const [deleteId, setDeleteId] = useState<number | null>(null);

    /* ================= FILTER ================= */
    const [filterTanggal, setFilterTanggal] = useState("");

    /* ================= SORT ================= */
    const [sortField, setSortField] = useState<keyof Order>("tanggal_pesan");
    const [sortOrder, setSortOrder] = useState<"asc" | "desc">("asc");

    /* ================= PAGINATION ================= */
    const [currentPage, setCurrentPage] = useState(1);
    const perPage = 10;

    /* ================= HANDLE ================= */

    const resetForm = () => {
        setForm({ tanggal_pesan: "" });
        setEditId(null);
        setOpenForm(false);
    };

    const handleSort = (field: keyof Order) => {
        if (sortField === field) {
            setSortOrder(sortOrder === "asc" ? "desc" : "asc");
        } else {
            setSortField(field);
            setSortOrder("asc");
        }
    };

    /* ================= FILTER + SORT ================= */

    const filteredData = useMemo(() => {
        let result = [...data];

        if (filterTanggal) {
            result = result.filter(
                (item) => item.tanggal_pesan === filterTanggal
            );
        }

        result.sort((a, b) => {
            const aVal = a[sortField];
            const bVal = b[sortField];

            if (sortOrder === "asc") return aVal.localeCompare(bVal);
            return bVal.localeCompare(aVal);
        });

        return result;
    }, [data, filterTanggal, sortField, sortOrder]);

    const groupedData = useMemo(() => {
        const groups: Record<string, Order[]> = {};

        filteredData.forEach((item) => {
            if (!groups[item.tanggal_pesan]) {
                groups[item.tanggal_pesan] = [];
            }
            groups[item.tanggal_pesan].push(item);
        });

        return Object.entries(groups).map(([tanggal, items]) => ({
            tanggal,
            items,
        }));
    }, [filteredData]);

    /* ================= PAGINATION ================= */

    const totalPages = Math.ceil(groupedData.length / perPage);

    const paginatedData = groupedData.slice(
        (currentPage - 1) * perPage,
        currentPage * perPage
    );

    useEffect(() => {
        setCurrentPage(1);
    }, [filterTanggal]);

    useEffect(() => {
        if (currentPage > totalPages) {
            setCurrentPage(1);
        }
    }, [filteredData]);

    return (
        <div className="p-6 space-y-6">
            <div className="flex justify-between items-center">
                <h1 className="text-3xl font-bold">Daftar Pembelanjaan</h1>
            </div>

            {/* FILTER */}
            <div className="flex items-center justify-between">
                <input
                    type="date"
                    value={filterTanggal}
                    onChange={(e) => setFilterTanggal(e.target.value)}
                    className="border p-2 rounded-md bg-white shadow"
                />


            </div>

            {/* TABLE */}
            <div className="bg-white/70 backdrop-blur-lg rounded-lg shadow overflow-auto">
                <table className="w-full text-sm">
                    <thead className="bg-white shadow-lg">
                        <tr>
                            <th className="p-3">No</th>
                            <th className="p-3">
                                <button
                                    onClick={() => handleSort("tanggal_pesan")}
                                    className="flex gap-2"
                                >
                                    Tgl Pesan <ArrowUpDown size={14} />
                                </button>
                            </th>
                            <th className="p-3 text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        {paginatedData.map((group, index) => (
                            <tr
                                key={group.tanggal}
                                className="border-t border-primary/20 hover:bg-white/50"
                            >
                                <td className="p-3 text-center">
                                    {(currentPage - 1) * perPage + index + 1}
                                </td>

                                {/* tampilkan tanggal sekali */}
                                <td className="p-3">{group.tanggal}</td>

                                <td className="p-3 flex justify-center gap-2">
                                    <button
                                        onClick={() =>
                                            router.push(
                                                `/admin/transaksiPembelian/daftarpembelanjaan/detail/${group.tanggal}`
                                            )
                                        }
                                        className="p-2 bg-green-500/30 text-green-700 rounded-md"
                                    >
                                        <Eye size={14} />
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* PAGINATION */}
            <div className="flex justify-end gap-2">
                <button
                    disabled={currentPage === 1}
                    onClick={() => setCurrentPage((p) => p - 1)}
                    className="px-3 py-1 border rounded-md"
                >
                    Prev
                </button>

                {Array.from({ length: totalPages }, (_, i) => (
                    <button
                        key={i}
                        onClick={() => setCurrentPage(i + 1)}
                        className={`px-3 py-1 border rounded-md ${currentPage === i + 1 ? "bg-primary text-white" : ""
                            }`}
                    >
                        {i + 1}
                    </button>
                ))}

                <button
                    disabled={currentPage === totalPages}
                    onClick={() => setCurrentPage((p) => p + 1)}
                    className="px-3 py-1 border rounded-md"
                >
                    Next
                </button>
            </div>




        </div>
    );
}

