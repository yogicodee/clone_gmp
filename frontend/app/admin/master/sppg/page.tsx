"use client";

import { useState, useMemo, useEffect } from "react";
import { Pencil, Trash2, Plus, ArrowUpDown } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { useFetch } from "@/hooks/useFetch";
import api from "@/lib/api";
import { extractErrorMessage } from "@/lib/transaksiPembelian";
import axios from "axios";

/* ================= TYPE ================= */
type Product = {
    id: number;
    nama_sppg: string;
    alamat: string;
    nama_yayasan: string;
    nama_penanggungjawab: string;
    no_penanggungjawab: string;
};

type FormType = Omit<Product, "id">;
type FieldErrors = Partial<Record<keyof FormType, string>>;

export default function Page() {
    const { data, refetch } = useFetch<Product>("/sppg");
    const { data: mitraData } = useFetch<any>("/mitra");

    const [form, setForm] = useState<FormType>({
        nama_sppg: "",
        alamat: "",
        nama_yayasan: "",
        nama_penanggungjawab: "",
        no_penanggungjawab: "",
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
    const [sortField, setSortField] = useState<keyof Product>("nama_sppg");
    const [sortOrder, setSortOrder] = useState<"asc" | "desc">("asc");

    /* ================= PAGINATION ================= */
    const [currentPage, setCurrentPage] = useState(1);
    const perPage = 10;

    /* ================= HANDLE ================= */
    const handleSubmit = async () => {
        const nextFieldErrors: FieldErrors = {};

        if (!form.nama_sppg.trim()) nextFieldErrors.nama_sppg = "Nama SPPG wajib diisi.";
        if (!form.alamat.trim()) nextFieldErrors.alamat = "Alamat wajib diisi.";
        if (!form.nama_yayasan.trim()) nextFieldErrors.nama_yayasan = "Nama yayasan wajib dipilih.";
        if (!form.nama_penanggungjawab.trim()) {
            nextFieldErrors.nama_penanggungjawab = "Nama penanggung jawab wajib diisi.";
        }
        if (!form.no_penanggungjawab.trim()) {
            nextFieldErrors.no_penanggungjawab = "No HP wajib diisi.";
        } else if (form.no_penanggungjawab.trim().length < 10) {
            nextFieldErrors.no_penanggungjawab = "No HP minimal 10 karakter.";
        }

        if (Object.keys(nextFieldErrors).length > 0) {
            setFieldErrors(nextFieldErrors);
            setErrorMessage("");
            setSuccessMessage("");
            return;
        }

        try {
            setFieldErrors({});
            setErrorMessage("");
            setSuccessMessage("");

            if (editId) {
                await api.put(`/sppg/${editId}`, form);
                setSuccessMessage("SPPG berhasil diperbarui.");
            } else {
                await api.post("/sppg", form);
                setSuccessMessage("SPPG berhasil ditambahkan.");
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
        setEditId(id);
        setOpenForm(true);
    };

    const handleDelete = async () => {
        if (!deleteId) return;

        try {
            await api.delete(`/sppg/${deleteId}`);
            await refetch();
            setDeleteId(null);
            setErrorMessage("");
            setSuccessMessage("SPPG berhasil dihapus.");
        } catch (error) {
            setErrorMessage(extractErrorMessage(error));
            setSuccessMessage("");
        }
    };

    const resetForm = () => {
        setForm({
            nama_sppg: "",
            alamat: "",
            nama_yayasan: "",
            nama_penanggungjawab: "",
            no_penanggungjawab: "",
        });
        setFieldErrors({});
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
                    item.nama_sppg.toLowerCase().includes(search.toLowerCase()) ||
                    item.alamat.toLowerCase().includes(search.toLowerCase())
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
                <h1 className="text-3xl font-bold">Data SPPG</h1>
            </div>

            {successMessage ? (
                <div className="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {successMessage}
                </div>
            ) : null}

            <div className="flex items-center justify-between">
                <input
                    placeholder="Cari nama SPPG atau alamat..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="border p-2 rounded-md w-1/4 bg-white shadow"
                />

                <button
                    onClick={() => setOpenForm(true)}
                    className="flex items-center gap-2 bg-linear-to-t from-secondary via-primary to-secondary shadow-lg shadow-black/20 text-white px-4 py-2 rounded-lg hover:-translate-y-1 transition cursor-pointer"
                >
                    <Plus size={16} />
                    Tambah Data
                </button>
            </div>

            {/* TABLE */}
            <div className="bg-white/70 backdrop-blur-lg rounded-lg shadow overflow-auto">
                <table className="w-full text-sm">
                    <thead className="bg-white shadow-lg">
                        <tr>
                            <th className="p-3">No</th>

                            <th className="p-3">
                                <button onClick={() => handleSort("nama_sppg")} className="flex items-center gap-2">
                                    Nama SPPG <ArrowUpDown size={14} />
                                </button>
                            </th>

                            <th className="p-3">
                                <button onClick={() => handleSort("alamat")} className="flex items-center gap-2">
                                    Alamat <ArrowUpDown size={14} />
                                </button>
                            </th>

                            <th className="p-3">
                                <button onClick={() => handleSort("nama_yayasan")} className="flex items-center gap-2">
                                    Yayasan <ArrowUpDown size={14} />
                                </button>
                            </th>

                            <th className="p-3">
                                <button onClick={() => handleSort("nama_penanggungjawab")} className="flex items-center gap-2">
                                    Penanggung Jawab <ArrowUpDown size={14} />
                                </button>
                            </th>

                            <th className="p-3">
                                <button onClick={() => handleSort("no_penanggungjawab")} className="flex items-center gap-2">
                                    No HP <ArrowUpDown size={14} />
                                </button>
                            </th>

                            <th className="p-3 text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        {paginatedData.map((item, index) => (
                            <tr key={item.id} className="border-t border-primary/20 hover:bg-white/50">
                                <td className="p-3 text-center">
                                    {(currentPage - 1) * perPage + index + 1}
                                </td>
                                <td className="p-3">{item.nama_sppg}</td>
                                <td className="p-3">{item.alamat}</td>
                                <td className="p-3">{item.nama_yayasan}</td>
                                <td className="p-3">{item.nama_penanggungjawab}</td>
                                <td className="p-3">{item.no_penanggungjawab}</td>

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
                                placeholder="Nama SPPG"
                                value={form.nama_sppg}
                                onChange={(e) => {
                                    setForm({ ...form, nama_sppg: e.target.value });
                                    setFieldErrors((prev) => ({ ...prev, nama_sppg: undefined }));
                                }}
                                className={`w-full border p-2 rounded-md ${fieldErrors.nama_sppg ? "border-red-500 focus:outline-red-500" : ""}`}
                            />
                            {fieldErrors.nama_sppg ? (
                                <p className="text-xs text-red-600 -mt-2">{fieldErrors.nama_sppg}</p>
                            ) : null}

                            <input
                                placeholder="Alamat"
                                value={form.alamat}
                                onChange={(e) => {
                                    setForm({ ...form, alamat: e.target.value });
                                    setFieldErrors((prev) => ({ ...prev, alamat: undefined }));
                                }}
                                className={`w-full border p-2 rounded-md ${fieldErrors.alamat ? "border-red-500 focus:outline-red-500" : ""}`}
                            />
                            {fieldErrors.alamat ? (
                                <p className="text-xs text-red-600 -mt-2">{fieldErrors.alamat}</p>
                            ) : null}

                            <select
                                value={form.nama_yayasan}
                                onChange={(e) => {
                                    setForm({ ...form, nama_yayasan: e.target.value });
                                    setFieldErrors((prev) => ({ ...prev, nama_yayasan: undefined }));
                                }}
                                className={`w-full border p-2 rounded-md ${fieldErrors.nama_yayasan ? "border-red-500 focus:outline-red-500" : ""}`}
                            >
                                <option value="">Pilih Nama Yayasan</option>
                                {mitraData.map((item: any) => (
                                    <option key={item.id} value={item.nama_yayasan}>
                                        {item.nama_yayasan}
                                    </option>
                                ))}
                            </select>
                            {fieldErrors.nama_yayasan ? (
                                <p className="text-xs text-red-600 -mt-2">{fieldErrors.nama_yayasan}</p>
                            ) : null}


                            <input
                                placeholder="Nama Penanggungjawab"
                                value={form.nama_penanggungjawab}
                                onChange={(e) => {
                                    setForm({ ...form, nama_penanggungjawab: e.target.value });
                                    setFieldErrors((prev) => ({ ...prev, nama_penanggungjawab: undefined }));
                                }}
                                className={`w-full border p-2 rounded-md ${fieldErrors.nama_penanggungjawab ? "border-red-500 focus:outline-red-500" : ""}`}
                            />
                            {fieldErrors.nama_penanggungjawab ? (
                                <p className="text-xs text-red-600 -mt-2">{fieldErrors.nama_penanggungjawab}</p>
                            ) : null}

                            <input
                                placeholder="Nomor HP"
                                value={form.no_penanggungjawab}
                                onChange={(e) => {
                                    setForm({ ...form, no_penanggungjawab: e.target.value });
                                    setFieldErrors((prev) => ({ ...prev, no_penanggungjawab: undefined }));
                                }}
                                className={`w-full border p-2 rounded-md ${fieldErrors.no_penanggungjawab ? "border-red-500 focus:outline-red-500" : ""}`}
                            />

                            {fieldErrors.no_penanggungjawab ? (
                                <p className="text-xs text-red-600 -mt-2">{fieldErrors.no_penanggungjawab}</p>
                            ) : (
                                <p className="text-xs text-gray-500 -mt-2">
                                    Minimal 10 karakter. Boleh angka, spasi, tanda kurung, `+`, dan `-`.
                                </p>
                            )}

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

            {/* MODAL DELETE */}
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
