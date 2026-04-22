"use client";

import { useEffect, useMemo, useState } from "react";
import { AnimatePresence, motion } from "framer-motion";
import { ArrowUpDown, Eye, Pencil, Plus, Trash2 } from "lucide-react";
import { useRouter } from "next/navigation";

import api from "@/lib/api";
import {
    ApiListResponse,
    OrderPenawaran,
    SppgOption,
    extractErrorMessage,
} from "@/lib/transaksiPembelian";

type FormType = {
    tanggal_pesan: string;
    tanggal_dikirim: string;
    nama_pembeli: string;
    keterangan: string;
};

type SortField = "tanggal_pesan" | "tanggal_dikirim" | "nama_pembeli";

const initialForm: FormType = {
    tanggal_pesan: "",
    tanggal_dikirim: "",
    nama_pembeli: "",
    keterangan: "",
};

export default function Page() {
    const router = useRouter();

    const [data, setData] = useState<OrderPenawaran[]>([]);
    const [sppgOptions, setSppgOptions] = useState<SppgOption[]>([]);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState("");
    const [success, setSuccess] = useState("");

    const [form, setForm] = useState<FormType>(initialForm);
    const [editId, setEditId] = useState<number | null>(null);
    const [openForm, setOpenForm] = useState(false);
    const [deleteTarget, setDeleteTarget] = useState<OrderPenawaran | null>(null);

    const [search, setSearch] = useState("");
    const [sortField, setSortField] = useState<SortField>("tanggal_pesan");
    const [sortOrder, setSortOrder] = useState<"asc" | "desc">("asc");
    const [currentPage, setCurrentPage] = useState(1);
    const perPage = 10;

    async function fetchData() {
        try {
            setLoading(true);
            setError("");

            const [ordersResponse, sppgResponse] = await Promise.all([
                api.get<ApiListResponse<OrderPenawaran>>("/order-penawaran", {
                    params: { per_page: 100 },
                }),
                api.get<ApiListResponse<SppgOption>>("/sppg", {
                    params: { per_page: 100 },
                }),
            ]);

            setData(ordersResponse.data.data ?? []);
            setSppgOptions(sppgResponse.data.data ?? []);
        } catch (err) {
            setError(extractErrorMessage(err));
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => {
        void fetchData();
    }, []);

    useEffect(() => {
        setCurrentPage(1);
    }, [search]);

    function resetForm() {
        setForm(initialForm);
        setEditId(null);
        setOpenForm(false);
    }

    function handleSort(field: SortField) {
        if (sortField === field) {
            setSortOrder((prev) => (prev === "asc" ? "desc" : "asc"));
            return;
        }

        setSortField(field);
        setSortOrder("asc");
    }

    function handleEdit(item: OrderPenawaran) {
        setForm({
            tanggal_pesan: item.tanggal_pesan ?? "",
            tanggal_dikirim: item.tanggal_dikirim ?? "",
            nama_pembeli: item.nama_pembeli ?? "",
            keterangan: item.keterangan ?? "",
        });
        setEditId(item.id);
        setOpenForm(true);
    }

    async function handleSubmit() {
        try {
            setSubmitting(true);
            setError("");
            setSuccess("");

            const payload = {
                tanggal_pesan: form.tanggal_pesan,
                tanggal_dikirim: form.tanggal_dikirim || null,
                nama_pembeli: form.nama_pembeli,
                keterangan: form.keterangan || null,
            };

            if (editId) {
                await api.put(`/order-penawaran/${editId}`, payload);
                setSuccess("Order penawaran berhasil diperbarui.");
            } else {
                await api.post("/order-penawaran", payload);
                setSuccess("Order penawaran berhasil ditambahkan.");
            }

            resetForm();
            await fetchData();
        } catch (err) {
            setError(extractErrorMessage(err));
        } finally {
            setSubmitting(false);
        }
    }

    async function handleDelete() {
        if (!deleteTarget) {
            return;
        }

        try {
            setSubmitting(true);
            setError("");
            setSuccess("");

            await api.delete(`/order-penawaran/${deleteTarget.id}`);
            setSuccess("Order penawaran berhasil dihapus.");
            setDeleteTarget(null);
            await fetchData();
        } catch (err) {
            setError(extractErrorMessage(err));
        } finally {
            setSubmitting(false);
        }
    }

    const filteredData = useMemo(() => {
        const normalizedSearch = search.trim().toLowerCase();

        const result = data.filter((item) => {
            if (!normalizedSearch) {
                return true;
            }

            return (
                item.nama_pembeli.toLowerCase().includes(normalizedSearch) ||
                (item.keterangan ?? "").toLowerCase().includes(normalizedSearch)
            );
        });

        result.sort((a, b) => {
            const first = String(a[sortField] ?? "").toLowerCase();
            const second = String(b[sortField] ?? "").toLowerCase();
            const comparison = first.localeCompare(second);
            return sortOrder === "asc" ? comparison : comparison * -1;
        });

        return result;
    }, [data, search, sortField, sortOrder]);

    const totalPages = Math.max(1, Math.ceil(filteredData.length / perPage));
    const paginatedData = filteredData.slice(
        (currentPage - 1) * perPage,
        currentPage * perPage
    );

    useEffect(() => {
        if (currentPage > totalPages) {
            setCurrentPage(1);
        }
    }, [currentPage, totalPages]);

    return (
        <div className="p-6 space-y-6">
            <div className="flex justify-between items-center">
                <h1 className="text-xl font-bold">List Order dan Penawaran</h1>
            </div>

            {error ? (
                <div className="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {error}
                </div>
            ) : null}

            {success ? (
                <div className="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {success}
                </div>
            ) : null}

            <div className="flex items-center justify-between gap-4">
                <input
                    placeholder="Cari nama pembeli / keterangan..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="border p-2 rounded-md w-1/4 min-w-60 bg-white shadow"
                />

                <button
                    onClick={() => {
                        resetForm();
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
                                <button
                                    onClick={() => handleSort("tanggal_pesan")}
                                    className="flex items-center gap-2"
                                >
                                    Tgl Pesan <ArrowUpDown size={14} />
                                </button>
                            </th>
                            <th className="p-3">
                                <button
                                    onClick={() => handleSort("tanggal_dikirim")}
                                    className="flex items-center gap-2"
                                >
                                    Tgl Kirim <ArrowUpDown size={14} />
                                </button>
                            </th>
                            <th className="p-3">
                                <button
                                    onClick={() => handleSort("nama_pembeli")}
                                    className="flex items-center gap-2"
                                >
                                    Nama <ArrowUpDown size={14} />
                                </button>
                            </th>
                            <th className="p-3 text-left">Keterangan</th>
                            <th className="p-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? (
                            <tr>
                                <td colSpan={6} className="p-6 text-center text-gray-500">
                                    Memuat data...
                                </td>
                            </tr>
                        ) : paginatedData.length === 0 ? (
                            <tr>
                                <td colSpan={6} className="p-6 text-center text-gray-500">
                                    Belum ada data order penawaran.
                                </td>
                            </tr>
                        ) : (
                            paginatedData.map((item, index) => (
                                <tr
                                    key={item.id}
                                    className="border-t border-primary/20 hover:bg-white/50"
                                >
                                    <td className="p-3 text-center">
                                        {(currentPage - 1) * perPage + index + 1}
                                    </td>
                                    <td className="p-3">{item.tanggal_pesan}</td>
                                    <td className="p-3">{item.tanggal_dikirim ?? "-"}</td>
                                    <td className="p-3">{item.nama_pembeli}</td>
                                    <td className="p-3">{item.keterangan ?? "-"}</td>
                                    <td className="p-3">
                                        <div className="flex justify-center gap-2">
                                            <button
                                                onClick={() =>
                                                    router.push(
                                                        `/admin/transaksiPembelian/listorderpenawaran/detail/${item.id}`
                                                    )
                                                }
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
                                        </div>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            <div className="flex justify-end gap-2">
                <button
                    disabled={currentPage === 1}
                    onClick={() => setCurrentPage((prev) => prev - 1)}
                    className="px-3 py-1 border rounded-md disabled:opacity-50"
                >
                    Prev
                </button>

                {Array.from({ length: totalPages }, (_, index) => (
                    <button
                        key={index}
                        onClick={() => setCurrentPage(index + 1)}
                        className={`px-3 py-1 border rounded-md ${currentPage === index + 1 ? "bg-primary text-white" : ""}`}
                    >
                        {index + 1}
                    </button>
                ))}

                <button
                    disabled={currentPage === totalPages}
                    onClick={() => setCurrentPage((prev) => prev + 1)}
                    className="px-3 py-1 border rounded-md disabled:opacity-50"
                >
                    Next
                </button>
            </div>

            <AnimatePresence>
                {openForm ? (
                    <Modal onClose={resetForm}>
                        <motion.div className="bg-white rounded-lg p-6 w-full max-w-md space-y-4">
                            <h2 className="text-lg font-semibold">
                                {editId ? "Edit Data" : "Tambah Data"}
                            </h2>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">Tanggal Pesan</label>
                                <input
                                    type="date"
                                    value={form.tanggal_pesan}
                                    onChange={(e) =>
                                        setForm((prev) => ({ ...prev, tanggal_pesan: e.target.value }))
                                    }
                                    className="w-full border p-2 rounded-md"
                                />
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">Tanggal Dikirim</label>
                                <input
                                    type="date"
                                    value={form.tanggal_dikirim}
                                    onChange={(e) =>
                                        setForm((prev) => ({ ...prev, tanggal_dikirim: e.target.value }))
                                    }
                                    className="w-full border p-2 rounded-md"
                                />
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">Nama Pembeli</label>
                                <select
                                    value={form.nama_pembeli}
                                    onChange={(e) =>
                                        setForm((prev) => ({ ...prev, nama_pembeli: e.target.value }))
                                    }
                                    className="w-full border p-2 rounded-md"
                                >
                                    <option value="">Pilih Nama Pembeli</option>
                                    {sppgOptions.map((option) => (
                                        <option key={option.id} value={option.nama_sppg}>
                                            {option.nama_sppg}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">Keterangan</label>
                                <input
                                    placeholder="Keterangan"
                                    value={form.keterangan}
                                    onChange={(e) =>
                                        setForm((prev) => ({ ...prev, keterangan: e.target.value }))
                                    }
                                    className="w-full border p-2 rounded-md"
                                />
                            </div>

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
                ) : null}
            </AnimatePresence>

            <AnimatePresence>
                {deleteTarget ? (
                    <Modal onClose={() => setDeleteTarget(null)}>
                        <motion.div className="bg-white rounded-lg p-6 w-full max-w-sm text-center space-y-4">
                            <h2 className="text-lg font-semibold">Hapus Data?</h2>
                            <p className="text-sm text-gray-600">
                                Order penawaran untuk <strong>{deleteTarget.nama_pembeli}</strong> akan dihapus.
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
                ) : null}
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
            className="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4"
            onClick={onClose}
        >
            <div onClick={(e) => e.stopPropagation()}>{children}</div>
        </motion.div>
    );
}
