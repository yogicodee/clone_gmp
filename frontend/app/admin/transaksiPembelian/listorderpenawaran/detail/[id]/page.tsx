"use client";

import { useEffect, useMemo, useState } from "react";
import { AnimatePresence, motion } from "framer-motion";
import { ArrowUpDown, Pencil, Plus, Trash2 } from "lucide-react";
import { useParams, useRouter } from "next/navigation";

import api from "@/lib/api";
import {
    ApiDetailResponse,
    ApiListResponse,
    KategoriOption,
    OrderPenawaran,
    OrderPenawaranItem,
    ProdukOption,
    extractErrorMessage,
    formatCurrency,
} from "@/lib/transaksiPembelian";

type FormType = {
    produk_id: number | "";
    kategori_id: number | "";
    nama_barang: string;
    qty: string;
    satuan: string;
    harga_satuan: string;
    keterangan: string;
};

type SortField = "nama_barang" | "qty" | "satuan" | "harga_satuan";

const initialForm: FormType = {
    produk_id: "",
    kategori_id: "",
    nama_barang: "",
    qty: "",
    satuan: "",
    harga_satuan: "",
    keterangan: "",
};

export default function Page() {
    const params = useParams<{ id: string }>();
    const router = useRouter();
    const orderId = Number(params.id);

    const [order, setOrder] = useState<OrderPenawaran | null>(null);
    const [items, setItems] = useState<OrderPenawaranItem[]>([]);
    const [produkOptions, setProdukOptions] = useState<ProdukOption[]>([]);
    const [kategoriOptions, setKategoriOptions] = useState<KategoriOption[]>([]);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState("");
    const [success, setSuccess] = useState("");

    const [form, setForm] = useState<FormType>(initialForm);
    const [editTarget, setEditTarget] = useState<OrderPenawaranItem | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<OrderPenawaranItem | null>(null);
    const [openForm, setOpenForm] = useState(false);

    const [search, setSearch] = useState("");
    const [sortField, setSortField] = useState<SortField>("nama_barang");
    const [sortOrder, setSortOrder] = useState<"asc" | "desc">("asc");
    const [currentPage, setCurrentPage] = useState(1);
    const perPage = 10;

    async function fetchData() {
        try {
            setLoading(true);
            setError("");

            const [detailResponse, itemsResponse, produkResponse, kategoriResponse] =
                await Promise.all([
                    api.get<ApiDetailResponse<OrderPenawaran>>(`/order-penawaran/${orderId}`),
                    api.get<ApiListResponse<OrderPenawaranItem>>(`/order-penawaran/${orderId}/items`, {
                        params: { per_page: 100 },
                    }),
                    api.get<ApiListResponse<ProdukOption>>("/produk", {
                        params: { per_page: 100 },
                    }),
                    api.get<ApiListResponse<KategoriOption>>("/kategori", {
                        params: { per_page: 100 },
                    }),
                ]);

            setOrder(detailResponse.data.data);
            setItems(itemsResponse.data.data ?? []);
            setProdukOptions(produkResponse.data.data ?? []);
            setKategoriOptions(kategoriResponse.data.data ?? []);
        } catch (err) {
            setError(extractErrorMessage(err));
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => {
        if (!Number.isNaN(orderId)) {
            void fetchData();
        }
    }, [orderId]);

    useEffect(() => {
        setCurrentPage(1);
    }, [search]);

    function resetForm() {
        setForm(initialForm);
        setEditTarget(null);
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

    function handleProdukChange(value: string) {
        const produkId = Number(value);
        const selectedProduk = produkOptions.find((item) => item.id === produkId);

        setForm((prev) => ({
            ...prev,
            produk_id: Number.isNaN(produkId) ? "" : produkId,
            nama_barang: selectedProduk?.nama ?? "",
            satuan: prev.kategori_id
                ? kategoriOptions.find((item) => item.id === prev.kategori_id)?.nama_satuan ?? prev.satuan
                : selectedProduk?.satuan ?? "",
        }));
    }

    function handleKategoriChange(value: string) {
        const kategoriId = Number(value);
        const selectedKategori = kategoriOptions.find((item) => item.id === kategoriId);

        setForm((prev) => ({
            ...prev,
            kategori_id: Number.isNaN(kategoriId) ? "" : kategoriId,
            satuan: selectedKategori?.nama_satuan ?? prev.satuan,
        }));
    }

    function handleEdit(item: OrderPenawaranItem) {
        setEditTarget(item);
        setForm({
            produk_id: item.produk_id ?? "",
            kategori_id: item.kategori_id ?? "",
            nama_barang: item.nama_barang,
            qty: String(item.qty),
            satuan: item.satuan,
            harga_satuan: String(item.harga_satuan),
            keterangan: item.keterangan ?? "",
        });
        setOpenForm(true);
    }

    async function handleSubmit() {
        try {
            setSubmitting(true);
            setError("");
            setSuccess("");

            const payload = {
                produk_id: form.produk_id === "" ? null : Number(form.produk_id),
                kategori_id: form.kategori_id === "" ? null : Number(form.kategori_id),
                nama_barang: form.nama_barang,
                qty: Number(form.qty),
                satuan: form.satuan,
                harga_satuan: Number(form.harga_satuan),
                keterangan: form.keterangan || null,
            };

            if (editTarget) {
                await api.put(
                    `/order-penawaran/${orderId}/items/${editTarget.id}`,
                    payload
                );
                setSuccess("Detail order penawaran berhasil diperbarui.");
            } else {
                await api.post(`/order-penawaran/${orderId}/items`, payload);
                setSuccess("Barang berhasil ditambahkan ke order penawaran.");
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

            await api.delete(`/order-penawaran/${orderId}/items/${deleteTarget.id}`);
            setSuccess("Barang berhasil dihapus dari order penawaran.");
            setDeleteTarget(null);
            await fetchData();
        } catch (err) {
            setError(extractErrorMessage(err));
        } finally {
            setSubmitting(false);
        }
    }

    const filteredItems = useMemo(() => {
        const normalizedSearch = search.trim().toLowerCase();

        const result = items.filter((item) => {
            if (!normalizedSearch) {
                return true;
            }

            return item.nama_barang.toLowerCase().includes(normalizedSearch);
        });

        result.sort((a, b) => {
            const first = String(a[sortField] ?? "").toLowerCase();
            const second = String(b[sortField] ?? "").toLowerCase();
            const comparison = first.localeCompare(second, "id", { numeric: true });
            return sortOrder === "asc" ? comparison : comparison * -1;
        });

        return result;
    }, [items, search, sortField, sortOrder]);

    const totalPages = Math.max(1, Math.ceil(filteredItems.length / perPage));
    const paginatedItems = filteredItems.slice(
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
                <div>
                    <h1 className="text-xl font-bold">Detail Order #{orderId}</h1>
                    {order ? (
                        <p className="text-sm text-gray-600 mt-1">
                            {order.nama_pembeli} | {order.tanggal_pesan}
                        </p>
                    ) : null}
                </div>

                <button
                    onClick={() => router.back()}
                    className="px-4 py-2 bg-gray-100 rounded-lg shadow"
                >
                    Kembali
                </button>
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
                    placeholder="Cari barang..."
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
                    Tambah Barang
                </button>
            </div>

            <div className="bg-white/70 backdrop-blur-lg rounded-lg shadow overflow-auto">
                <table className="w-full text-sm">
                    <thead className="bg-white shadow-lg">
                        <tr>
                            <th className="p-3">No</th>
                            <th className="p-3">
                                <button
                                    onClick={() => handleSort("nama_barang")}
                                    className="flex items-center gap-2"
                                >
                                    Nama Barang <ArrowUpDown size={14} />
                                </button>
                            </th>
                            <th className="p-3">
                                <button
                                    onClick={() => handleSort("qty")}
                                    className="flex items-center gap-2"
                                >
                                    Qty <ArrowUpDown size={14} />
                                </button>
                            </th>
                            <th className="p-3">
                                <button
                                    onClick={() => handleSort("satuan")}
                                    className="flex items-center gap-2"
                                >
                                    Satuan <ArrowUpDown size={14} />
                                </button>
                            </th>
                            <th className="p-3">
                                <button
                                    onClick={() => handleSort("harga_satuan")}
                                    className="flex items-center gap-2"
                                >
                                    Harga Penawaran <ArrowUpDown size={14} />
                                </button>
                            </th>
                            <th className="p-3">Keterangan</th>
                            <th className="p-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? (
                            <tr>
                                <td colSpan={7} className="p-6 text-center text-gray-500">
                                    Memuat data...
                                </td>
                            </tr>
                        ) : paginatedItems.length === 0 ? (
                            <tr>
                                <td colSpan={7} className="p-6 text-center text-gray-500">
                                    Belum ada barang pada order ini.
                                </td>
                            </tr>
                        ) : (
                            paginatedItems.map((item, index) => (
                                <tr
                                    key={item.id}
                                    className="border-t border-primary/20 hover:bg-white/50"
                                >
                                    <td className="p-3 text-center">
                                        {(currentPage - 1) * perPage + index + 1}
                                    </td>
                                    <td className="p-3">{item.nama_barang}</td>
                                    <td className="p-3">{item.qty}</td>
                                    <td className="p-3">{item.satuan}</td>
                                    <td className="p-3">{formatCurrency(item.harga_satuan)}</td>
                                    <td className="p-3">{item.keterangan ?? "-"}</td>
                                    <td className="p-3">
                                        <div className="flex gap-2 justify-center">
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
                        <motion.div className="bg-white rounded-lg p-6 w-full max-w-xl space-y-4">
                            <h2 className="text-lg font-semibold">
                                {editTarget ? "Edit Barang" : "Tambah Barang"}
                            </h2>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">Pilih Nama Barang</label>
                                <select
                                    value={form.produk_id}
                                    onChange={(e) => handleProdukChange(e.target.value)}
                                    className="w-full border p-2 rounded-md"
                                >
                                    <option value="">Pilih Nama Barang</option>
                                    {produkOptions.map((item) => (
                                        <option key={item.id} value={item.id}>
                                            {item.nama}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Qty</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={form.qty}
                                        onChange={(e) =>
                                            setForm((prev) => ({ ...prev, qty: e.target.value }))
                                        }
                                        className="w-full border p-2 rounded-md"
                                        placeholder="Qty"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Pilih Nama Satuan</label>
                                    <select
                                        value={form.kategori_id}
                                        onChange={(e) => handleKategoriChange(e.target.value)}
                                        className="w-full border p-2 rounded-md"
                                    >
                                        <option value="">Pilih Nama Satuan</option>
                                        {kategoriOptions.map((item) => (
                                            <option key={item.id} value={item.id}>
                                                {item.nama_satuan}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Satuan</label>
                                    <input
                                        value={form.satuan}
                                        onChange={(e) =>
                                            setForm((prev) => ({ ...prev, satuan: e.target.value }))
                                        }
                                        className="w-full border p-2 rounded-md"
                                        placeholder="Satuan"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Harga Penawaran</label>
                                    <input
                                        type="number"
                                        min="0"
                                        value={form.harga_satuan}
                                        onChange={(e) =>
                                            setForm((prev) => ({
                                                ...prev,
                                                harga_satuan: e.target.value,
                                            }))
                                        }
                                        className="w-full border p-2 rounded-md"
                                        placeholder="Harga Penawaran"
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">Keterangan</label>
                                <input
                                    value={form.keterangan}
                                    onChange={(e) =>
                                        setForm((prev) => ({ ...prev, keterangan: e.target.value }))
                                    }
                                    className="w-full border p-2 rounded-md"
                                    placeholder="Keterangan"
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
                            <h2 className="text-lg font-semibold">Hapus Barang?</h2>
                            <p className="text-sm text-gray-600">
                                Barang <strong>{deleteTarget.nama_barang}</strong> akan dihapus dari order ini.
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
