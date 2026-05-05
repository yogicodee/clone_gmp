"use client";

import { useState, useMemo } from "react";
import { Pencil, Trash2, Plus, ArrowUpDown } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { useFetch } from "@/hooks/useFetch";
import api from "@/lib/api";
import { extractErrorMessage } from "@/lib/transaksiPembelian";
import axios from "axios";

/* ================= TYPE ================= */
type Product = {
    id: number;
    kode: string;
    nama_satuan: string;
};

type FormType = Omit<Product, "id">;
type FieldErrors = Partial<Record<keyof FormType, string>>;

export default function Page() {
    const { data, refetch } = useFetch<Product>("/kategori");

    const [form, setForm] = useState<FormType>({
        kode: "",
        nama_satuan: "",
    });

    const [editId, setEditId] = useState<number | null>(null);
    const [openForm, setOpenForm] = useState(false);
    const [deleteId, setDeleteId] = useState<number | null>(null);
    const [fieldErrors, setFieldErrors] = useState<FieldErrors>({});
    const [errorMessage, setErrorMessage] = useState("");
    const [successMessage, setSuccessMessage] = useState("");

    /* ================= FILTER ================= */
    const [search, setSearch] = useState("");

    /* ================= SORT ================= */
    const [sortField, setSortField] = useState<keyof Product>("kode");
    const [sortOrder, setSortOrder] = useState<"asc" | "desc">("asc");

    /* ================= PAGINATION ================= */
    const [currentPage, setCurrentPage] = useState(1);
    const perPage = 10;

    /* ================= HANDLE ================= */
    const handleSubmit = async () => {
        const nextFieldErrors: FieldErrors = {};

        if (!form.kode.trim()) nextFieldErrors.kode = "Kode wajib diisi.";
        if (!form.nama_satuan.trim()) nextFieldErrors.nama_satuan = "Nama satuan wajib diisi.";

        if (Object.keys(nextFieldErrors).length > 0) {
            setFieldErrors(nextFieldErrors);
            setSuccessMessage("");
            return;
        }

        try {
            setFieldErrors({});
            setErrorMessage("");
            setSuccessMessage("");

            if (editId) {
                await api.put(`/kategori/${editId}`, form);
                setSuccessMessage("Kategori berhasil diperbarui.");
            } else {
                await api.post("/kategori", form);
                setSuccessMessage("Kategori berhasil ditambahkan.");
            }

            await refetch();
            resetForm();
        } catch (error) {
            if (axios.isAxiosError(error)) {
                const apiErrors = error.response?.data?.errors;

                if (apiErrors && typeof apiErrors === "object") {
                    const mappedErrors: FieldErrors = {};

                    for (const key of Object.keys(apiErrors)) {
                        const firstMessage = apiErrors[key]?.[0];
                        if (typeof firstMessage === "string") {
                            mappedErrors[key as keyof FormType] = firstMessage;
                        }
                    }

                    if (Object.keys(mappedErrors).length > 0) {
                        setFieldErrors(mappedErrors);
                        setErrorMessage("");
                        setSuccessMessage("");
                        return;
                    }
                }
            }

            setErrorMessage(extractErrorMessage(error));
            setSuccessMessage("");
        }
    };

    const handleEdit = (item: Product) => {
        const { id, ...rest } = item;
        setForm(rest);
        setFieldErrors({});
        setErrorMessage("");
        setEditId(id);
        setOpenForm(true);
    };

    const handleDelete = async () => {
        if (!deleteId) return;

        try {
            await api.delete(`/kategori/${deleteId}`);
            await refetch();
            setDeleteId(null);
            setErrorMessage("");
            setSuccessMessage("Kategori berhasil dihapus.");
        } catch (error) {
            setErrorMessage(extractErrorMessage(error));
            setSuccessMessage("");
        }
    };

    const resetForm = () => {
        setForm({ kode: "", nama_satuan: "" });
        setFieldErrors({});
        setErrorMessage("");
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
                    item.kode.toLowerCase().includes(search.toLowerCase()) ||
                    item.nama_satuan.toLowerCase().includes(search.toLowerCase())
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

    const normalizedCurrentPage = totalPages === 0 ? 1 : Math.min(currentPage, totalPages);

    const paginatedData = filteredData.slice(
        (normalizedCurrentPage - 1) * perPage,
        normalizedCurrentPage * perPage
    );

    return (
        <div className="p-6 space-y-6">
            <div className="flex justify-between items-center">
                <h1 className="text-3xl font-bold">Data Kategori / Satuan</h1>
            </div>

            {errorMessage && !openForm ? (
                <div className="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {errorMessage}
                </div>
            ) : null}

            {successMessage ? (
                <div className="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {successMessage}
                </div>
            ) : null}

            <div className="flex items-center justify-between">
                <input
                    placeholder="Cari kode atau nama satuan..."
                    value={search}
                    onChange={(e) => {
                        setSearch(e.target.value);
                        setCurrentPage(1);
                    }}
                    className="border p-2 rounded-md w-1/4 bg-white shadow"
                />

                <button
                    onClick={() => {
                        setSuccessMessage("");
                        setErrorMessage("");
                        setOpenForm(true);
                    }}
                    className="flex items-center gap-2 bg-linear-to-t from-secondary via-primary to-secondary shadow-lg shadow-black/20 text-white px-4 py-2 rounded-lg hover:-translate-y-1 transition cursor-pointer"
                >
                    <Plus size={16} />
                    Tambah Data
                </button>
            </div>

            <div className="bg-white/70 backdrop-blur-lg rounded-lg shadow overflow-auto">
                <table className="w-full text-sm">
                    <thead className="bg-white shadow-lg">
                        <tr>
                            <th className="p-3">No</th>
                            <th className="p-3">
                                <button onClick={() => handleSort("kode")} className="flex items-center gap-2">
                                    Kode <ArrowUpDown size={14} />
                                </button>
                            </th>
                            <th className="p-3">
                                <button onClick={() => handleSort("nama_satuan")} className="flex items-center gap-2">
                                    Nama Satuan <ArrowUpDown size={14} />
                                </button>
                            </th>
                            <th className="p-3 text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        {paginatedData.map((item, index) => (
                            <tr key={item.id} className="border-t border-primary/20 hover:bg-white/50">
                                <td className="p-3 text-center">
                                    {(normalizedCurrentPage - 1) * perPage + index + 1}
                                </td>
                                <td className="p-3 font-semibold">{item.kode}</td>
                                <td className="p-3">{item.nama_satuan}</td>
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

            <div className="flex justify-end gap-2">
                <button
                    disabled={normalizedCurrentPage === 1}
                    onClick={() => setCurrentPage((p) => p - 1)}
                    className="px-3 py-1 border rounded-md"
                >
                    Prev
                </button>

                {Array.from({ length: totalPages }, (_, i) => (
                    <button
                        key={i}
                        onClick={() => setCurrentPage(i + 1)}
                        className={`px-3 py-1 border rounded-md ${normalizedCurrentPage === i + 1 ? "bg-primary text-white" : ""}`}
                    >
                        {i + 1}
                    </button>
                ))}

                <button
                    disabled={normalizedCurrentPage === totalPages || totalPages === 0}
                    onClick={() => setCurrentPage((p) => p + 1)}
                    className="px-3 py-1 border rounded-md"
                >
                    Next
                </button>
            </div>

            <AnimatePresence>
                {openForm && (
                    <Modal onClose={resetForm}>
                        <motion.div className="bg-white rounded-lg p-6 w-full max-w-md space-y-4">
                            <h2 className="text-lg font-semibold">
                                {editId ? "Edit Data" : "Tambah Data"}
                            </h2>

                            {errorMessage ? (
                                <div className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                                    {errorMessage}
                                </div>
                            ) : null}

                            <input
                                placeholder="Kode (contoh: PCS, KG)"
                                value={form.kode}
                                onChange={(e) => {
                                    setForm({ ...form, kode: e.target.value.toUpperCase() });
                                    setFieldErrors((prev) => ({ ...prev, kode: undefined }));
                                    setErrorMessage("");
                                }}
                                className={`w-full border p-2 rounded-md ${fieldErrors.kode ? "border-red-500 focus:outline-red-500" : ""}`}
                            />
                            {fieldErrors.kode ? (
                                <p className="text-xs text-red-600 -mt-2">{fieldErrors.kode}</p>
                            ) : (
                                <p className="text-xs text-gray-500 -mt-2">
                                    Gunakan huruf kapital, angka, atau tanda minus (-).
                                </p>
                            )}

                            <input
                                placeholder="Nama Satuan"
                                value={form.nama_satuan}
                                onChange={(e) => {
                                    setForm({ ...form, nama_satuan: e.target.value });
                                    setFieldErrors((prev) => ({ ...prev, nama_satuan: undefined }));
                                    setErrorMessage("");
                                }}
                                className={`w-full border p-2 rounded-md ${fieldErrors.nama_satuan ? "border-red-500 focus:outline-red-500" : ""}`}
                            />
                            {fieldErrors.nama_satuan ? <p className="text-xs text-red-600 -mt-2">{fieldErrors.nama_satuan}</p> : null}

                            <div className="flex justify-end gap-2">
                                <button
                                    onClick={resetForm}
                                    className="px-4 py-2 bg-gray-200 rounded-md"
                                >
                                    Batal
                                </button>

                                <button
                                    onClick={handleSubmit}
                                    className="px-4 py-2 bg-blue-700 text-white rounded-md"
                                >
                                    Simpan
                                </button>
                            </div>
                        </motion.div>
                    </Modal>
                )}
            </AnimatePresence>

            <AnimatePresence>
                {deleteId && (
                    <Modal onClose={() => setDeleteId(null)}>
                        <motion.div className="bg-white rounded-lg p-6 w-full max-w-sm text-center space-y-4">
                            <h2 className="text-lg font-semibold">
                                Hapus Data?
                            </h2>

                            <div className="flex justify-center gap-2">
                                <button
                                    onClick={() => setDeleteId(null)}
                                    className="px-4 py-2 bg-gray-200 rounded-md"
                                >
                                    Batal
                                </button>

                                <button
                                    onClick={handleDelete}
                                    className="px-4 py-2 bg-red-600 text-white rounded-md"
                                >
                                    Hapus
                                </button>
                            </div>
                        </motion.div>
                    </Modal>
                )}
            </AnimatePresence>
        </div>
    );
}

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
