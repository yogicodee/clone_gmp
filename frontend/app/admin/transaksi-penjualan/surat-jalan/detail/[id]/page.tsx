"use client";

import { useEffect, useMemo, useState } from "react";
import { AnimatePresence, motion } from "framer-motion";
import { Pencil } from "lucide-react";
import { useParams, useRouter } from "next/navigation";

import api from "@/lib/api";
import {
    ApiDetailResponse,
    ApiListResponse,
    DaftarPembelanjaan,
    DaftarPembelanjaanItem,
    SupplierOption,
    extractErrorMessage,
} from "@/lib/transaksiPembelian";

type EditForm = {
    keterangan: string;
};

export default function Page() {
    const params = useParams<{ id: string }>();
    const router = useRouter();
    const daftarPembelanjaanId = Number(params.id);

    const [detail, setDetail] = useState<DaftarPembelanjaan | null>(null);
    const [items, setItems] = useState<DaftarPembelanjaanItem[]>([]);
    const [supplierOptions, setSupplierOptions] = useState<SupplierOption[]>([]);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState("");
    const [success, setSuccess] = useState("");

    const [search, setSearch] = useState("");
    const [editTarget, setEditTarget] = useState<DaftarPembelanjaanItem | null>(null);
    const [form, setForm] = useState<EditForm>({
        keterangan: ""
    });
    const [currentPage, setCurrentPage] = useState(1);
    const perPage = 10;

    async function fetchData() {
        try {
            setLoading(true);
            setError("");

            const [detailResponse, supplierResponse] = await Promise.all([
                api.get<ApiDetailResponse<DaftarPembelanjaan>>(
                    `/daftar-pembelanjaan/${daftarPembelanjaanId}`
                ),
                api.get<ApiListResponse<SupplierOption>>("/supplier", {
                    params: { per_page: 100 },
                }),
            ]);

            const detailData = detailResponse.data.data;
            setDetail(detailData);
            setItems(detailData.items ?? []);
            setSupplierOptions(supplierResponse.data.data ?? []);
        } catch (err) {
            setError(extractErrorMessage(err));
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => {
        if (!Number.isNaN(daftarPembelanjaanId)) {
            void fetchData();
        }
    }, [daftarPembelanjaanId]);

    useEffect(() => {
        setCurrentPage(1);
    }, [search]);

    const groupedItems = useMemo(() => {
        const normalizedSearch = search.trim().toLowerCase();

        const map = new Map<string, DaftarPembelanjaanItem>();

        for (const item of items) {
            const key = item.nama_barang.toLowerCase();

            if (normalizedSearch) {
                const match =
                    item.nama_barang
                        .toLowerCase()
                        .includes(normalizedSearch) ||
                            (item.nama_supplier ?? "").toLowerCase().includes(normalizedSearch);

                if (!match) continue;
            }

            if (!map.has(key)) {
                map.set(key, {
                    ...item,
                    qty: Number(item.qty),
                    stok: Number(item.stok),
                    kebutuhan: Number(item.kebutuhan),
                });
            } else {
                const existing = map.get(key)!;

                map.set(key, {
                    ...existing,
                    qty: Number(existing.qty) + Number(item.qty),
                    stok: Number(existing.stok) + Number(item.stok),
                    kebutuhan: Number(existing.kebutuhan) + Number(item.kebutuhan),
                });
            }
        }

        return Array.from(map.values());
    }, [items, search]);

    const totalPages = Math.max(1, Math.ceil(groupedItems.length / perPage));

    const paginatedItems = groupedItems.slice(
        (currentPage - 1) * perPage,
        currentPage * perPage
    );

    useEffect(() => {
        if (currentPage > totalPages) {
            setCurrentPage(1);
        }
    }, [currentPage, totalPages]);

    function openEditModal(item: DaftarPembelanjaanItem) {
        setEditTarget(item);
        setForm({
            keterangan: (item as any).keterangan || ""
        });
    }

    async function handleSubmit() {
        if (!editTarget) {
            return;
        }

        try {
            setSubmitting(true);
            setError("");
            setSuccess("");

            const selectedSupplier =
                supplierOptions.find((item) => item.id === Number(form.supplier_id)) ?? null;

            await api.put(
                `/daftar-pembelanjaan/${daftarPembelanjaanId}/items/${editTarget.id}`,
                {
                    produk_id: editTarget.produk_id,
                    kategori_id: editTarget.kategori_id,
                    supplier_id: form.supplier_id === "" ? null : Number(form.supplier_id),
                    nama_barang: editTarget.nama_barang,
                    qty: editTarget.qty,
                    satuan: editTarget.satuan,
                    stok: editTarget.stok,
                    kebutuhan: editTarget.kebutuhan,
                    nama_supplier: selectedSupplier?.nama ?? "",
                }
            );

            setSuccess("Keterangan berhasil diperbarui.");
            setEditTarget(null);
            setForm({ keterangan: "" });
            await fetchData();
        } catch (err) {
            setError(extractErrorMessage(err));
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <div className="p-6 space-y-6">
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-xl font-bold">Detail Surat Jalan #{daftarPembelanjaanId}</h1>
                    {detail ? (
                        <p className="text-sm text-gray-600 mt-1">{detail.tanggal_pesan}</p>
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

            <div className="flex items-center justify-between">

                <input
                    placeholder="Cari barang..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="border p-2 rounded-md w-1/4 min-w-60 bg-white shadow"
                />

                <button

                    className="flex items-center gap-2 bg-linear-to-t from-secondary via-primary to-secondary shadow-lg shadow-black/20 text-white px-4 py-2 rounded-lg hover:-translate-y-1 transition cursor-pointer"
                >
                    Export PDF
                </button>

            </div>

            <div className="bg-white/70 backdrop-blur-lg rounded-lg shadow overflow-auto">
                <table className="w-full text-sm">
                    <thead className="bg-white shadow-lg">
                        <tr>
                            <th className="p-3">No</th>
                            <th className="p-3 text-left">Nama Barang</th>
                            <th className="p-3">Qty</th>
                            <th className="p-3">Satuan</th>
                            <th className="p-3">Keterangan</th>
                            <th className="p-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? (
                            <tr>
                                <td colSpan={6} className="p-6 text-center text-gray-500">
                                    Memuat data...
                                </td>
                            </tr>
                        ) : paginatedItems.length === 0 ? (
                            <tr>
                                <td colSpan={6} className="p-6 text-center text-gray-500">
                                    Belum ada item daftar pembelanjaan.
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
                                    <td className="p-3 text-center">{item.qty}</td>
                                    <td className="p-3 text-center">{item.satuan}</td>
                                    <td className="p-3">
                                        {(item as any).keterangan || "-"}
                                    </td>

                                    <td className="p-3">
                                        <div className="flex justify-center">
                                            <button
                                                onClick={() => openEditModal(item)}
                                                className="p-2 bg-blue-500/30 text-blue-700 rounded-md"
                                            >
                                                <Pencil size={14} />
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
                {editTarget ? (
                    <Modal
                        onClose={() => {
                            setEditTarget(null);
                            setForm({ supplier_id: "" });
                        }}
                    >
                        <motion.div className="bg-white rounded-lg p-6 w-full max-w-lg space-y-4">

                            <h2 className="text-lg font-semibold">
                                Edit Keterangan Barang
                            </h2>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                                <div>
                                    <p className="font-medium text-gray-600">Barang</p>
                                    <p>{editTarget.nama_barang}</p>
                                </div>

                                <div>
                                    <p className="font-medium text-gray-600">Qty</p>
                                    <p>{editTarget.qty}</p>
                                </div>

                                <div>
                                    <p className="font-medium text-gray-600">Satuan</p>
                                    <p>{editTarget.satuan}</p>
                                </div>

                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">
                                    Keterangan
                                </label>

                                <input
                                    value={form.keterangan}
                                    onChange={(e) =>
                                        setForm({
                                            keterangan: e.target.value
                                        })
                                    }
                                    className="w-full border p-2 rounded-md"
                                    placeholder="Masukkan keterangan"
                                />

                            </div>

                            <div className="flex justify-end gap-2">
                                <button
                                    onClick={() => {
                                        setEditTarget(null);
                                        setForm({ keterangan: "" });
                                    }}
                                    className="px-4 py-2 bg-gray-200 rounded-md"
                                >
                                    Batal
                                </button>

                                <button
                                    onClick={() => {
                                        setItems(prev =>
                                            prev.map(it =>
                                                it.id === editTarget.id
                                                    ? { ...it, keterangan: form.keterangan } as any
                                                    : it
                                            )
                                        );
                                        setEditTarget(null);
                                    }}
                                    className="px-4 py-2 bg-blue-700 text-white rounded-md"
                                >
                                    Simpan
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
