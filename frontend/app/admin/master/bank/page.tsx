"use client";

import { useState, useMemo, useEffect } from "react";
import { Pencil, Trash2, Plus, ArrowUpDown } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";

/* ================= TYPE ================= */
type Product = {
    id: number;
    nama_bank: string;
    no_rek: string;
    atas_nama: string;
    cabang: string;
};

type FormType = Omit<Product, "id">;

export default function Page() {
    const [data, setData] = useState<Product[]>([
        {
            id: 1,
            nama_bank: "BCA",
            no_rek: "1234567890",
            atas_nama: "PT Maju Jaya",
            cabang: "Bandung",
        },
        {
            id: 2,
            nama_bank: "BRI",
            no_rek: "9876543210",
            atas_nama: "CV Aulia",
            cabang: "Jakarta",
        },
    ]);

    const [form, setForm] = useState<FormType>({
        nama_bank: "",
        no_rek: "",
        atas_nama: "",
        cabang: "",
    });

    const [editId, setEditId] = useState<number | null>(null);
    const [openForm, setOpenForm] = useState(false);
    const [deleteId, setDeleteId] = useState<number | null>(null);

    /* ================= FILTER ================= */
    const [search, setSearch] = useState("");

    /* ================= SORT ================= */
    const [sortField, setSortField] = useState<keyof Product>("nama_bank");
    const [sortOrder, setSortOrder] = useState<"asc" | "desc">("asc");

    /* ================= PAGINATION ================= */
    const [currentPage, setCurrentPage] = useState(1);
    const perPage = 10;

    /* ================= HANDLE ================= */

    const handleSubmit = () => {
        if (!form.nama_bank || !form.no_rek || !form.atas_nama || !form.cabang) return;

        if (editId) {
            setData((prev) =>
                prev.map((item) =>
                    item.id === editId ? { ...item, ...form } : item
                )
            );
        } else {
            setData((prev) => [
                ...prev,
                { id: Date.now(), ...form },
            ]);
        }

        resetForm();
    };

    const handleEdit = (item: Product) => {
        const { id, ...rest } = item;
        setForm(rest);
        setEditId(id);
        setOpenForm(true);
    };

    const handleDelete = () => {
        if (deleteId) {
            setData((prev) => prev.filter((item) => item.id !== deleteId));
            setDeleteId(null);
        }
    };

    const resetForm = () => {
        setForm({ nama_bank: "", no_rek: "", atas_nama: "", cabang: "" });
        setEditId(null);
        setOpenForm(false);
    };

    const handleSort = (field: keyof Product) => {
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

        if (search) {
            result = result.filter(
                (item) =>
                    item.nama_bank.toLowerCase().includes(search.toLowerCase()) ||
                    item.atas_nama.toLowerCase().includes(search.toLowerCase()) ||
                    item.no_rek.includes(search)
            );
        }

        result.sort((a, b) => {
            const aVal = String(a[sortField]).toLowerCase();
            const bVal = String(b[sortField]).toLowerCase();

            if (sortOrder === "asc") return aVal.localeCompare(bVal);
            return bVal.localeCompare(aVal);
        });

        return result;
    }, [data, search, sortField, sortOrder]);

    /* ================= PAGINATION ================= */

    const totalPages = Math.ceil(filteredData.length / perPage);

    const paginatedData = filteredData.slice(
        (currentPage - 1) * perPage,
        currentPage * perPage
    );

    useEffect(() => {
        setCurrentPage(1);
    }, [search]);

    useEffect(() => {
        if (currentPage > totalPages) {
            setCurrentPage(1);
        }
    }, [filteredData]);

    return (
        <div className="p-6 space-y-6">
            <div className="flex justify-between items-center">
                <h1 className="text-xl font-bold">Data Bank & Rekening</h1>
            </div>

            <div className="flex items-center justify-between">
                <input
                    placeholder="Cari bank / no rekening / atas nama..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="border p-2 rounded-md w-1/4 bg-white shadow"
                />

                <button
                    onClick={() => setOpenForm(true)}
                    className="flex items-center gap-2 bg-primary text-white px-4 py-2 rounded-lg"
                >
                    <Plus size={16} />
                    Tambah Data
                </button>
            </div>

            {/* TABLE */}
            <div className="bg-white rounded-lg shadow overflow-auto">
                <table className="w-full text-sm">
                    <thead className="bg-gray-100">
                        <tr>
                            <th className="p-3">No</th>

                            <th className="p-3">
                                <button onClick={() => handleSort("nama_bank")} className="flex items-center gap-2">
                                    Nama Bank <ArrowUpDown size={14} />
                                </button>
                            </th>

                            <th className="p-3">
                                <button onClick={() => handleSort("no_rek")} className="flex items-center gap-2">
                                    No Rekening <ArrowUpDown size={14} />
                                </button>
                            </th>

                            <th className="p-3">
                                <button onClick={() => handleSort("atas_nama")} className="flex items-center gap-2">
                                    A.N Rekening <ArrowUpDown size={14} />
                                </button>
                            </th>

                            <th className="p-3">
                                <button onClick={() => handleSort("cabang")} className="flex items-center gap-2">
                                    Cabang <ArrowUpDown size={14} />
                                </button>
                            </th>

                            <th className="p-3 text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        {paginatedData.map((item, index) => (
                            <tr key={item.id} className="border-t">
                                <td className="p-3 text-center">
                                    {(currentPage - 1) * perPage + index + 1}
                                </td>
                                <td className="p-3">{item.nama_bank}</td>
                                <td className="p-3">{item.no_rek}</td>
                                <td className="p-3">{item.atas_nama}</td>
                                <td className="p-3">{item.cabang}</td>

                                <td className="p-3 flex justify-center gap-2">
                                    <button
                                        onClick={() => handleEdit(item)}
                                        className="p-2 bg-blue-500/30 text-blue-700 rounded-md"
                                    >
                                        <Pencil size={14} />
                                    </button>

                                    <button
                                        onClick={() => setDeleteId(item.id)}
                                        className="p-2 bg-red-500/30 text-red-700 rounded-md"
                                    >
                                        <Trash2 size={14} />
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

            {/* FORM MODAL */}
            <AnimatePresence>
                {openForm && (
                    <Modal onClose={resetForm}>
                        <motion.div className="bg-white rounded-lg p-6 w-full max-w-md space-y-4">
                            <h2 className="text-lg font-semibold">
                                {editId ? "Edit Data" : "Tambah Data"}
                            </h2>

                            <input
                                placeholder="Nama Bank"
                                value={form.nama_bank}
                                onChange={(e) => setForm({ ...form, nama_bank: e.target.value })}
                                className="w-full border p-2 rounded-md"
                            />

                            <input
                                placeholder="No Rekening"
                                value={form.no_rek}
                                onChange={(e) => setForm({ ...form, no_rek: e.target.value })}
                                className="w-full border p-2 rounded-md"
                            />

                            <input
                                placeholder="Atas Nama Rekening"
                                value={form.atas_nama}
                                onChange={(e) => setForm({ ...form, atas_nama: e.target.value })}
                                className="w-full border p-2 rounded-md"
                            />

                            <input
                                placeholder="Cabang"
                                value={form.cabang}
                                onChange={(e) => setForm({ ...form, cabang: e.target.value })}
                                className="w-full border p-2 rounded-md"
                            />

                            <div className="flex justify-end gap-2">
                                <button onClick={resetForm} className="px-4 py-2 bg-gray-200 rounded-md">
                                    Batal
                                </button>

                                <button onClick={handleSubmit} className="px-4 py-2 bg-blue-700 text-white rounded-md">
                                    Simpan
                                </button>
                            </div>
                        </motion.div>
                    </Modal>
                )}
            </AnimatePresence>

            {/* DELETE MODAL tetap sama */}
        </div>
    );
}

/* ================= MODAL ================= */
function Modal({
    children,
    onClose,
}: {
    children: React.ReactNode;
    onClose: () => void;
}) {
    return (
        <motion.div
            className="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
            onClick={onClose}
        >
            <div onClick={(e) => e.stopPropagation()}>
                {children}
            </div>
        </motion.div>
    );
}