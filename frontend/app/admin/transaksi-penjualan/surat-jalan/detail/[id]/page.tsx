"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import { AnimatePresence, motion } from "framer-motion";
import { Pencil, Plus, Trash2 } from "lucide-react";
import { useParams, useRouter } from "next/navigation";
import api from "@/lib/api";
import { extractErrorMessage } from "@/lib/transaksiPembelian";
import axios from "axios";

type SuratJalanItem = {
    id: number;
    penjualan_item_id: number | null;
    nama_barang: string;
    qty: number | string;
    satuan: string | null;
    keterangan: string | null;
};

type PenjualanItemOption = {
    id: number;
    nama_barang: string;
    qty: number | string;
    satuan: string;
};

type SuratJalanDetail = {
    id: number;
    nomor_surat_jalan: string;
    no_po: string | null;
    tanggal: string;
    status: "draft" | "selesai" | "batal";
    sppg?: { nama_sppg: string } | null;
    armadaRef?: { nama_unit: string; no_pol: string } | null;
    driver?: { nama: string } | null;
};

type FormType = {
    penjualan_item_id: number | null;
    nama_barang: string;
    qty: string;
    satuan: string;
    keterangan: string;
};

type FieldErrors = Partial<Record<keyof FormType, string>>;

const initialForm: FormType = {
    penjualan_item_id: null,
    nama_barang: "",
    qty: "",
    satuan: "",
    keterangan: "",
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
    const params = useParams<{ id: string }>();
    const router = useRouter();
    const suratJalanId = Number(params.id);

    const [detail, setDetail] = useState<SuratJalanDetail | null>(null);
    const [items, setItems] = useState<SuratJalanItem[]>([]);
    const [barangOptions, setBarangOptions] = useState<PenjualanItemOption[]>([]);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [errorMessage, setErrorMessage] = useState("");
    const [successMessage, setSuccessMessage] = useState("");
    const [fieldErrors, setFieldErrors] = useState<FieldErrors>({});

    const [search, setSearch] = useState("");
    const [editTarget, setEditTarget] = useState<SuratJalanItem | null>(null);
    const [form, setForm] = useState<FormType>(initialForm);
    const [deleteTarget, setDeleteTarget] = useState<SuratJalanItem | null>(null);
    const [openForm, setOpenForm] = useState(false);
    const [currentPage, setCurrentPage] = useState(1);
    const perPage = 10;

    const fetchData = useCallback(async () => {
        try {
            setLoading(true);
            setErrorMessage("");

            const [detailResponse, itemsResponse, opsiResponse] = await Promise.all([
                api.get(`/surat-jalan/${suratJalanId}`),
                api.get(`/surat-jalan/${suratJalanId}/items`),
                api.get(`/surat-jalan/${suratJalanId}/opsi-barang`),
            ]);

            setDetail(detailResponse.data.data);
            setItems(itemsResponse.data.data ?? []);
            setBarangOptions(opsiResponse.data.data ?? []);
        } catch (error) {
            setErrorMessage(extractErrorMessage(error));
        } finally {
            setLoading(false);
        }
    }, [suratJalanId]);

    useEffect(() => {
        if (!Number.isNaN(suratJalanId)) {
            // eslint-disable-next-line react-hooks/set-state-in-effect
            void fetchData();
        }
    }, [fetchData, suratJalanId]);

    const resetForm = () => {
        setForm(initialForm);
        setFieldErrors({});
        setErrorMessage("");
        setEditTarget(null);
        setOpenForm(false);
    };

    const clearFieldError = (field: keyof FormType) => {
        setFieldErrors((prev) => ({ ...prev, [field]: undefined }));
        setErrorMessage("");
    };

    const openCreateForm = () => {
        setForm(initialForm);
        setFieldErrors({});
        setErrorMessage("");
        setSuccessMessage("");
        setEditTarget(null);
        setOpenForm(true);
    };

    const handleEdit = (item: SuratJalanItem) => {
        setEditTarget(item);
        setForm({
            penjualan_item_id: item.penjualan_item_id,
            nama_barang: item.nama_barang,
            qty: String(Number(item.qty)),
            satuan: item.satuan ?? "",
            keterangan: item.keterangan ?? "",
        });
        setFieldErrors({});
        setErrorMessage("");
        setOpenForm(true);
    };

    const handleSubmit = async () => {
        const nextFieldErrors: FieldErrors = {};

        if (!form.penjualan_item_id && !form.nama_barang.trim()) {
            nextFieldErrors.nama_barang = "Nama barang wajib diisi.";
        }
        if (!form.penjualan_item_id) {
            if (!form.qty.trim()) {
                nextFieldErrors.qty = "Qty wajib diisi.";
            } else if (Number(form.qty) <= 0) {
                nextFieldErrors.qty = "Qty harus lebih dari 0.";
            }
        }

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

            const payload = form.penjualan_item_id
                ? {
                    penjualan_item_id: form.penjualan_item_id,
                    keterangan: form.keterangan || null,
                }
                : {
                    nama_barang: form.nama_barang,
                    qty: Number(form.qty),
                    satuan: form.satuan || null,
                    keterangan: form.keterangan || null,
                };

            if (editTarget) {
                await api.put(`/surat-jalan/${suratJalanId}/items/${editTarget.id}`, payload);
                setSuccessMessage("Item surat jalan berhasil diperbarui.");
            } else {
                await api.post(`/surat-jalan/${suratJalanId}/items`, payload);
                setSuccessMessage("Item surat jalan berhasil ditambahkan.");
            }

            resetForm();
            await fetchData();
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
            await api.delete(`/surat-jalan/${suratJalanId}/items/${deleteTarget.id}`);
            setDeleteTarget(null);
            setErrorMessage("");
            setSuccessMessage("Item surat jalan berhasil dihapus.");
            await fetchData();
        } catch (error) {
            setErrorMessage(extractErrorMessage(error));
            setSuccessMessage("");
        } finally {
            setSubmitting(false);
        }
    };

    const selectedBarang = useMemo(
        () => barangOptions.find((item) => item.id === form.penjualan_item_id) ?? null,
        [barangOptions, form.penjualan_item_id]
    );

    const filteredItems = useMemo(() => {
        const normalizedSearch = search.trim().toLowerCase();
        return items.filter((item) => {
            if (!normalizedSearch) return true;
            return (
                item.nama_barang.toLowerCase().includes(normalizedSearch) ||
                (item.keterangan ?? "").toLowerCase().includes(normalizedSearch)
            );
        });
    }, [items, search]);

    const totalPages = Math.max(1, Math.ceil(filteredItems.length / perPage));
    const normalizedCurrentPage = Math.min(currentPage, totalPages);
    const paginatedItems = filteredItems.slice(
        (normalizedCurrentPage - 1) * perPage,
        normalizedCurrentPage * perPage
    );

    return (
        <div className="p-6 space-y-6">
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-xl font-bold">Detail Surat Jalan #{suratJalanId}</h1>
                    {detail ? (
                        <p className="text-sm text-gray-600 mt-1">
                            {detail.nomor_surat_jalan} | {detail.sppg?.nama_sppg ?? "-"} | {formatTanggal(detail.tanggal)}
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
                    placeholder="Cari barang..."
                    value={search}
                    onChange={(e) => {
                        setSearch(e.target.value);
                        setCurrentPage(1);
                    }}
                    className="border p-2 rounded-md w-1/4 min-w-60 bg-white shadow"
                />

                <button
                    onClick={openCreateForm}
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
                                    Belum ada item surat jalan.
                                </td>
                            </tr>
                        ) : (
                            paginatedItems.map((item, index) => (
                                <tr key={item.id} className="border-t border-primary/20 hover:bg-white/50">
                                    <td className="p-3 text-center">
                                        {(normalizedCurrentPage - 1) * perPage + index + 1}
                                    </td>
                                    <td className="p-3">{item.nama_barang}</td>
                                    <td className="p-3 text-center">{Number(item.qty)}</td>
                                    <td className="p-3 text-center">{item.satuan ?? "-"}</td>
                                    <td className="p-3">{item.keterangan || "-"}</td>
                                    <td className="p-3">
                                        <div className="flex justify-center gap-2">
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
                    disabled={normalizedCurrentPage === 1}
                    onClick={() => setCurrentPage((prev) => prev - 1)}
                    className="px-3 py-1 border rounded-md disabled:opacity-50"
                >
                    Prev
                </button>

                {Array.from({ length: totalPages }, (_, index) => (
                    <button
                        key={index}
                        onClick={() => setCurrentPage(index + 1)}
                        className={`px-3 py-1 border rounded-md ${normalizedCurrentPage === index + 1 ? "bg-primary text-white" : ""}`}
                    >
                        {index + 1}
                    </button>
                ))}

                <button
                    disabled={normalizedCurrentPage === totalPages}
                    onClick={() => setCurrentPage((prev) => prev + 1)}
                    className="px-3 py-1 border rounded-md disabled:opacity-50"
                >
                    Next
                </button>
            </div>

            <AnimatePresence>
                {openForm ? (
                    <Modal onClose={resetForm}>
                        <motion.div className="bg-white rounded-lg p-6 w-full max-w-lg space-y-4">
                            <h2 className="text-lg font-semibold">
                                {editTarget ? "Edit Item" : "Tambah Item"}
                            </h2>

                            {errorMessage ? (
                                <div className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                                    {errorMessage}
                                </div>
                            ) : null}

                            <div className="space-y-2">
                                <label className="text-sm font-medium">Barang dari Penjualan</label>
                                <select
                                    value={form.penjualan_item_id ?? ""}
                                    onChange={(e) => {
                                        const option = barangOptions.find((item) => item.id === Number(e.target.value));
                                        setForm({
                                            penjualan_item_id: e.target.value ? Number(e.target.value) : null,
                                            nama_barang: option?.nama_barang ?? "",
                                            qty: option ? String(Number(option.qty)) : "",
                                            satuan: option?.satuan ?? "",
                                            keterangan: form.keterangan,
                                        });
                                        clearFieldError("penjualan_item_id");
                                        clearFieldError("nama_barang");
                                    }}
                                    className="w-full border p-2 rounded-md"
                                >
                                    <option value="">Pilih Barang Penjualan</option>
                                    {barangOptions.map((item) => (
                                        <option key={item.id} value={item.id}>
                                            {item.nama_barang} - {Number(item.qty)} {item.satuan}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {!form.penjualan_item_id ? (
                                <>
                                    <div className="space-y-2">
                                        <label className="text-sm font-medium">Nama Barang</label>
                                        <input
                                            value={form.nama_barang}
                                            onChange={(e) => {
                                                setForm({ ...form, nama_barang: e.target.value });
                                                clearFieldError("nama_barang");
                                            }}
                                            className={`w-full border p-2 rounded-md ${fieldErrors.nama_barang ? "border-red-500 focus:outline-red-500" : ""}`}
                                            placeholder="Masukkan nama barang"
                                        />
                                        {fieldErrors.nama_barang ? <p className="text-xs text-red-600">{fieldErrors.nama_barang}</p> : null}
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div className="space-y-2">
                                            <label className="text-sm font-medium">Qty</label>
                                            <input
                                                type="number"
                                                min="1"
                                                value={form.qty}
                                                onChange={(e) => {
                                                    setForm({ ...form, qty: e.target.value });
                                                    clearFieldError("qty");
                                                }}
                                                className={`w-full border p-2 rounded-md ${fieldErrors.qty ? "border-red-500 focus:outline-red-500" : ""}`}
                                                placeholder="Qty"
                                            />
                                            {fieldErrors.qty ? <p className="text-xs text-red-600">{fieldErrors.qty}</p> : null}
                                        </div>

                                        <div className="space-y-2">
                                            <label className="text-sm font-medium">Satuan</label>
                                            <input
                                                value={form.satuan}
                                                onChange={(e) => setForm({ ...form, satuan: e.target.value })}
                                                className="w-full border p-2 rounded-md"
                                                placeholder="Satuan"
                                            />
                                        </div>
                                    </div>
                                </>
                            ) : (
                                <div className="rounded-md bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                    Qty: <span className="font-semibold">{selectedBarang ? Number(selectedBarang.qty) : 0}</span>
                                    {" | "}
                                    Satuan: <span className="font-semibold">{selectedBarang?.satuan ?? "-"}</span>
                                </div>
                            )}

                            <div className="space-y-2">
                                <label className="text-sm font-medium">Keterangan</label>
                                <input
                                    value={form.keterangan}
                                    onChange={(e) => setForm({ ...form, keterangan: e.target.value })}
                                    className="w-full border p-2 rounded-md"
                                    placeholder="Masukkan keterangan"
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
                            <h2 className="text-lg font-semibold">Hapus Item?</h2>
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
