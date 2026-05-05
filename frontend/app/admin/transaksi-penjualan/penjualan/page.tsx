"use client";

import { useMemo, useState } from "react";
import { Pencil, Trash2, Plus, ArrowUpDown, Eye } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { useRouter } from "next/navigation";
import api from "@/lib/api";
import { useFetch } from "@/hooks/useFetch";
import { extractErrorMessage } from "@/lib/transaksiPembelian";
import axios from "axios";

type OrderPenawaranSource = {
    id: number;
    tanggal_dikirim: string | null;
    nama_pembeli: string;
    keterangan: string | null;
};

type Penjualan = {
    id: number;
    order_penawaran_id: number | null;
    kode_penjualan: string;
    tanggal: string;
    total_harga: string | number;
    status: "draft" | "selesai" | "batal";
    orderPenawaran?: OrderPenawaranSource | null;
};

type FormType = {
    order_penawaran_id: number | null;
    kode_penjualan: string;
    tanggal: string;
    status: "draft" | "selesai" | "batal";
};

type FieldErrors = Partial<Record<keyof FormType, string>>;

const initialForm: FormType = {
    order_penawaran_id: null,
    kode_penjualan: "",
    tanggal: "",
    status: "draft",
};

const formatRupiah = (value: string | number) => {
    const number = Number(value || 0);
    return new Intl.NumberFormat("id-ID").format(number);
};

const formatTanggal = (value: string) => {
    if (!value) return "-";

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;

    return date.toLocaleDateString("id-ID", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
    });
};

export default function Page() {
    const router = useRouter();
    const { data, refetch } = useFetch<Penjualan>("/penjualan");

    const [form, setForm] = useState<FormType>(initialForm);
    const [editTarget, setEditTarget] = useState<Penjualan | null>(null);
    const [openForm, setOpenForm] = useState(false);
    const [deleteTarget, setDeleteTarget] = useState<Penjualan | null>(null);
    const [fieldErrors, setFieldErrors] = useState<FieldErrors>({});
    const [errorMessage, setErrorMessage] = useState("");
    const [successMessage, setSuccessMessage] = useState("");
    const [submitting, setSubmitting] = useState(false);

    const [search, setSearch] = useState("");
    const [sortField, setSortField] = useState<keyof Penjualan>("tanggal");
    const [sortOrder, setSortOrder] = useState<"asc" | "desc">("desc");
    const [currentPage, setCurrentPage] = useState(1);
    const perPage = 10;

    const resetForm = () => {
        setForm(initialForm);
        setEditTarget(null);
        setFieldErrors({});
        setErrorMessage("");
        setOpenForm(false);
    };

    const openCreateForm = () => {
        setForm(initialForm);
        setEditTarget(null);
        setFieldErrors({});
        setErrorMessage("");
        setSuccessMessage("");
        setOpenForm(true);
    };

    const clearFieldError = (field: keyof FormType) => {
        setFieldErrors((prev) => ({ ...prev, [field]: undefined }));
        setErrorMessage("");
    };

    const handleEdit = (item: Penjualan) => {
        setEditTarget(item);
        setForm({
            order_penawaran_id: item.order_penawaran_id,
            kode_penjualan: item.kode_penjualan ?? "",
            tanggal: item.tanggal ?? "",
            status: item.status,
        });
        setFieldErrors({});
        setErrorMessage("");
        setOpenForm(true);
    };

    const handleSubmit = async () => {
        const nextFieldErrors: FieldErrors = {};
        const isLinkedOrder = form.order_penawaran_id !== null;

        if (!isLinkedOrder) {
            if (!form.kode_penjualan.trim()) nextFieldErrors.kode_penjualan = "Kode penjualan wajib diisi.";
            if (!form.tanggal) nextFieldErrors.tanggal = "Tanggal wajib diisi.";
        }

        if (!form.status) nextFieldErrors.status = "Status wajib dipilih.";

        if (Object.keys(nextFieldErrors).length > 0) {
            setFieldErrors(nextFieldErrors);
            setSuccessMessage("");
            return;
        }

        try {
            setSubmitting(true);
            setFieldErrors({});
            setErrorMessage("");
            setSuccessMessage("");

            const payload = isLinkedOrder
                ? {
                    order_penawaran_id: form.order_penawaran_id,
                    status: form.status,
                }
                : {
                    kode_penjualan: form.kode_penjualan,
                    tanggal: form.tanggal,
                    status: form.status,
                };

            if (editTarget) {
                await api.put(`/penjualan/${editTarget.id}`, payload);
                setSuccessMessage("Penjualan berhasil diperbarui.");
            } else {
                await api.post("/penjualan", payload);
                setSuccessMessage("Penjualan manual berhasil ditambahkan.");
            }

            resetForm();
            await refetch();
        } catch (error) {
            if (axios.isAxiosError(error)) {
                const apiErrors = error.response?.data?.errors;

                if (apiErrors && typeof apiErrors === "object") {
                    const mappedErrors: FieldErrors = {};

                    for (const key of Object.keys(apiErrors)) {
                        const firstMessage = apiErrors[key]?.[0];
                        if (typeof firstMessage === "string" && key in initialForm) {
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
        } finally {
            setSubmitting(false);
        }
    };

    const handleDelete = async () => {
        if (!deleteTarget) return;

        try {
            setSubmitting(true);
            await api.delete(`/penjualan/${deleteTarget.id}`);
            setDeleteTarget(null);
            setErrorMessage("");
            setSuccessMessage("Penjualan berhasil dihapus.");
            await refetch();
        } catch (error) {
            setErrorMessage(extractErrorMessage(error));
            setSuccessMessage("");
        } finally {
            setSubmitting(false);
        }
    };

    const handleSort = (field: keyof Penjualan) => {
        if (sortField === field) {
            setSortOrder((prev) => (prev === "asc" ? "desc" : "asc"));
            return;
        }

        setSortField(field);
        setSortOrder("asc");
    };

    const filteredData = useMemo(() => {
        const normalizedSearch = search.trim().toLowerCase();
        const result = data.filter((item) => {
            if (!normalizedSearch) return true;

            return (
                item.kode_penjualan.toLowerCase().includes(normalizedSearch) ||
                item.status.toLowerCase().includes(normalizedSearch) ||
                (item.orderPenawaran?.nama_pembeli ?? "").toLowerCase().includes(normalizedSearch) ||
                (item.orderPenawaran?.keterangan ?? "").toLowerCase().includes(normalizedSearch)
            );
        });

        result.sort((a, b) => {
            const aVal = String(a[sortField] ?? "").toLowerCase();
            const bVal = String(b[sortField] ?? "").toLowerCase();
            const comparison = aVal.localeCompare(bVal, "id", { numeric: true });
            return sortOrder === "asc" ? comparison : comparison * -1;
        });

        return result;
    }, [data, search, sortField, sortOrder]);

    const totalPages = Math.ceil(filteredData.length / perPage);
    const normalizedCurrentPage = totalPages === 0 ? 1 : Math.min(currentPage, totalPages);
    const paginatedData = filteredData.slice(
        (normalizedCurrentPage - 1) * perPage,
        normalizedCurrentPage * perPage
    );

    return (
        <div className="p-6 space-y-6">
            <div className="flex justify-between items-center">
                <h1 className="text-3xl font-bold">List Penjualan</h1>
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
                    placeholder="Cari kode / status / pembeli..."
                    value={search}
                    onChange={(e) => {
                        setSearch(e.target.value);
                        setCurrentPage(1);
                    }}
                    className="border p-2 rounded-md w-1/4 bg-white shadow"
                />

                <button
                    onClick={openCreateForm}
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
                                <button onClick={() => handleSort("kode_penjualan")} className="flex gap-2">
                                    Kode Penjualan <ArrowUpDown size={14} />
                                </button>
                            </th>
                            <th className="p-3">
                                <button onClick={() => handleSort("tanggal")} className="flex gap-2">
                                    Tanggal <ArrowUpDown size={14} />
                                </button>
                            </th>
                            <th className="p-3 text-left">Pembeli</th>
                            <th className="p-3">
                                <button onClick={() => handleSort("total_harga")} className="flex gap-2">
                                    Total Harga <ArrowUpDown size={14} />
                                </button>
                            </th>
                            <th className="p-3">
                                <button onClick={() => handleSort("status")} className="flex gap-2">
                                    Status <ArrowUpDown size={14} />
                                </button>
                            </th>
                            <th className="p-3 text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        {paginatedData.length > 0 ? (
                            paginatedData.map((item, index) => (
                                <tr key={item.id} className="border-t border-primary/20 hover:bg-white/50">
                                    <td className="p-3 text-center">
                                        {(normalizedCurrentPage - 1) * perPage + index + 1}
                                    </td>
                                    <td className="p-3">{item.kode_penjualan}</td>
                                    <td className="p-3">{formatTanggal(item.tanggal)}</td>
                                    <td className="p-3">{item.orderPenawaran?.nama_pembeli ?? "-"}</td>
                                    <td className="p-3">Rp {formatRupiah(item.total_harga)}</td>
                                    <td className="p-3 capitalize">{item.status}</td>
                                    <td className="p-3 flex justify-center gap-2">
                                        <button
                                            onClick={() => router.push(`/admin/transaksi-penjualan/penjualan/detail/${item.id}`)}
                                            className="p-2 bg-green-500/30 text-green-700 rounded-md"
                                        >
                                            <Eye size={14} />
                                        </button>

                                        <button
                                            onClick={() => handleEdit(item)}
                                            className="p-2 bg-blue-500/30 text-blue-700 rounded-md"
                                        >
                                            <Pencil size={14} />
                                        </button>

                                        <button
                                            onClick={() => setDeleteTarget(item)}
                                            className="p-2 bg-red-500/30 text-red-700 rounded-md"
                                        >
                                            <Trash2 size={14} />
                                        </button>
                                    </td>
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td colSpan={7} className="p-6 text-center text-gray-500">
                                    Belum ada data penjualan.
                                </td>
                            </tr>
                        )}
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
                                {editTarget ? "Edit Data" : "Tambah Data Manual"}
                            </h2>

                            {errorMessage ? (
                                <div className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                                    {errorMessage}
                                </div>
                            ) : null}

                            {form.order_penawaran_id ? (
                                <div className="rounded-md bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                    Penjualan ini terhubung ke order penawaran. Kode, tanggal, dan total harga mengikuti order sumber.
                                </div>
                            ) : null}

                            <input
                                placeholder="Kode Penjualan"
                                value={form.kode_penjualan}
                                onChange={(e) => {
                                    setForm({ ...form, kode_penjualan: e.target.value });
                                    clearFieldError("kode_penjualan");
                                }}
                                disabled={form.order_penawaran_id !== null}
                                className={`w-full border p-2 rounded-md ${fieldErrors.kode_penjualan ? "border-red-500 focus:outline-red-500" : ""} ${form.order_penawaran_id ? "bg-slate-50 text-slate-500" : ""}`}
                            />
                            {fieldErrors.kode_penjualan ? <p className="text-xs text-red-600 -mt-2">{fieldErrors.kode_penjualan}</p> : null}

                            <input
                                type="date"
                                value={form.tanggal}
                                onChange={(e) => {
                                    setForm({ ...form, tanggal: e.target.value });
                                    clearFieldError("tanggal");
                                }}
                                disabled={form.order_penawaran_id !== null}
                                className={`w-full border p-2 rounded-md ${fieldErrors.tanggal ? "border-red-500 focus:outline-red-500" : ""} ${form.order_penawaran_id ? "bg-slate-50 text-slate-500" : ""}`}
                            />
                            {fieldErrors.tanggal ? <p className="text-xs text-red-600 -mt-2">{fieldErrors.tanggal}</p> : null}

                            <select
                                value={form.status}
                                onChange={(e) => {
                                    setForm({ ...form, status: e.target.value as FormType["status"] });
                                    clearFieldError("status");
                                }}
                                className={`w-full border p-2 rounded-md ${fieldErrors.status ? "border-red-500 focus:outline-red-500" : ""}`}
                            >
                                <option value="draft">Draft</option>
                                <option value="selesai">Selesai</option>
                                <option value="batal">Batal</option>
                            </select>
                            {fieldErrors.status ? <p className="text-xs text-red-600 -mt-2">{fieldErrors.status}</p> : null}

                            <div className="flex justify-end gap-2">
                                <button
                                    onClick={resetForm}
                                    className="px-4 py-2 bg-gray-200 rounded-md"
                                >
                                    Batal
                                </button>

                                <button
                                    onClick={() => void handleSubmit()}
                                    disabled={submitting}
                                    className="px-4 py-2 bg-blue-700 text-white rounded-md disabled:opacity-50"
                                >
                                    {submitting ? "Menyimpan..." : "Simpan"}
                                </button>
                            </div>
                        </motion.div>
                    </Modal>
                )}
            </AnimatePresence>

            <AnimatePresence>
                {deleteTarget && (
                    <Modal onClose={() => setDeleteTarget(null)}>
                        <motion.div className="bg-white rounded-lg p-6 w-full max-w-sm text-center space-y-4">
                            <h2 className="text-lg font-semibold">Hapus Data?</h2>
                            <p className="text-sm text-gray-600">
                                Penjualan <strong>{deleteTarget.kode_penjualan}</strong> akan dihapus.
                            </p>

                            <div className="flex justify-center gap-2">
                                <button
                                    onClick={() => setDeleteTarget(null)}
                                    className="px-4 py-2 bg-gray-200 rounded-md"
                                >
                                    Batal
                                </button>

                                <button
                                    onClick={() => void handleDelete()}
                                    disabled={submitting}
                                    className="px-4 py-2 bg-red-600 text-white rounded-md disabled:opacity-50"
                                >
                                    {submitting ? "Menghapus..." : "Hapus"}
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
            <div onClick={(e) => e.stopPropagation()}>{children}</div>
        </motion.div>
    );
}
