"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import { AnimatePresence, motion } from "framer-motion";
import { FileDown, Pencil } from "lucide-react";
import { useParams, useRouter } from "next/navigation";
import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";
import api from "@/lib/api";
import { extractErrorMessage } from "@/lib/transaksiPembelian";

type SuratJalanItem = {
    id: number;
    penjualan_item_id: number | null;
    nama_barang: string;
    qty: number | string;
    satuan: string | null;
    keterangan: string | null;
};

type SuratJalanDetail = {
    id: number;
    nomor_surat_jalan: string;
    no_po: string | null;
    tanggal: string;
    status: "draft" | "selesai" | "batal";
    sppg?: { nama_sppg: string } | null;
    armada_ref?: { nama_unit: string; no_pol: string } | null;
    driver?: { nama: string } | null;
};

type FormType = {
    keterangan: string;
};

const initialForm: FormType = {
    keterangan: "",
};

const EXPORT_COMPANY_NAME = "Koperasi Mitra Pangan Berdikari";
const EXPORT_COMPANY_LINES = [
    "Jl. Hos Cokroaminoto No. 56 Jombatan, Kecamatan Jombang,",
    "Kabupaten Jombang",
    "No. Tlp 081803010020",
];

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
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [errorMessage, setErrorMessage] = useState("");
    const [successMessage, setSuccessMessage] = useState("");

    const [search, setSearch] = useState("");
    const [editTarget, setEditTarget] = useState<SuratJalanItem | null>(null);
    const [form, setForm] = useState<FormType>(initialForm);
    const [openForm, setOpenForm] = useState(false);
    const [currentPage, setCurrentPage] = useState(1);
    const perPage = 10;

    const fetchData = useCallback(async () => {
        try {
            setLoading(true);
            setErrorMessage("");

            const [detailResponse, itemsResponse] = await Promise.all([
                api.get(`/surat-jalan/${suratJalanId}`),
                api.get(`/surat-jalan/${suratJalanId}/items`),
            ]);

            setDetail(detailResponse.data.data);
            setItems(itemsResponse.data.data ?? []);
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
        setErrorMessage("");
        setEditTarget(null);
        setOpenForm(false);
    };

    const handleEdit = (item: SuratJalanItem) => {
        setEditTarget(item);
        setForm({
            keterangan: item.keterangan ?? "",
        });
        setErrorMessage("");
        setOpenForm(true);
    };

    const handleSubmit = async () => {
        if (!editTarget) {
            return;
        }

        try {
            setSubmitting(true);
            setErrorMessage("");
            setSuccessMessage("");

            await api.put(`/surat-jalan/${suratJalanId}/items/${editTarget.id}`, {
                penjualan_item_id: editTarget.penjualan_item_id,
                keterangan: form.keterangan || null,
            });
            setSuccessMessage("Keterangan item surat jalan berhasil diperbarui.");

            resetForm();
            await fetchData();
        } catch (error) {
            setErrorMessage(extractErrorMessage(error));
            setSuccessMessage("");
        } finally {
            setSubmitting(false);
        }
    };

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

    const handleExportPdf = () => {
        if (!detail) {
            return;
        }

        const doc = new jsPDF({
            orientation: "portrait",
            unit: "mm",
            format: "a4",
        });

        doc.setFont("times", "normal");

        doc.setFont("times", "bold");
        doc.setFontSize(15);
        doc.text(EXPORT_COMPANY_NAME, 18, 20);

        doc.setFont("times", "normal");
        doc.setFontSize(9);
        doc.text(EXPORT_COMPANY_LINES, 18, 26);

        doc.setFont("times", "bold");
        doc.setFontSize(22);
        doc.text("SURAT", 125, 18);
        doc.text("JALAN", 125, 29);

        doc.setFont("times", "normal");
        doc.setFontSize(10);
        doc.text(`No. ${detail.nomor_surat_jalan || "-"}`, 125, 38);

        doc.setFont("times", "normal");
        doc.setFontSize(10);
        doc.text(`Kepada: ${detail.sppg?.nama_sppg ?? "-"}`, 18, 52);
        doc.text(`Tanggal: ${formatTanggal(detail.tanggal)}`, 125, 52);
        doc.text(`No. PO: ${detail.no_po || "-"}`, 125, 59);
        doc.text(`Armada: ${detail.armada_ref?.nama_unit || "-"}`, 125, 66);
        doc.text(`No. Polisi: ${detail.armada_ref?.no_pol || "-"}`, 125, 73);
        doc.text(`Driver: ${detail.driver?.nama || "-"}`, 125, 80);

        autoTable(doc, {
            startY: 88,
            theme: "grid",
            styles: {
                font: "times",
                fontSize: 10,
                lineColor: [120, 120, 120],
                lineWidth: 0.15,
                cellPadding: 2,
                textColor: [20, 20, 20],
            },
            headStyles: {
                fillColor: [255, 255, 255],
                textColor: [20, 20, 20],
                fontStyle: "bold",
            },
            columnStyles: {
                0: { halign: "center", cellWidth: 12 },
                1: { cellWidth: 70 },
                2: { halign: "center", cellWidth: 22 },
                3: { halign: "center", cellWidth: 28 },
                4: { cellWidth: 45 },
            },
            head: [["No", "Nama Barang", "Satuan", "Jumlah\nBarang", "Keterangan"]],
            body: items.map((item, index) => [
                index + 1,
                item.nama_barang,
                item.satuan ?? "-",
                Number(item.qty),
                item.keterangan || "",
            ]),
        });

        const finalY = (doc as jsPDF & { lastAutoTable?: { finalY?: number } }).lastAutoTable?.finalY ?? 120;

        doc.setFont("times", "normal");
        doc.setFontSize(10);
        doc.text("Tanda terima", 18, finalY + 10);

        doc.setFont("times", "bold");
        doc.text("Sopir", 60, finalY + 20, { align: "center" });
        doc.text("Penerima", 150, finalY + 20, { align: "center" });

        doc.setFont("times", "normal");
        doc.text("(                             )", 60, finalY + 55, { align: "center" });
        doc.text("(                             )", 150, finalY + 55, { align: "center" });

        const safeNumber = (detail.nomor_surat_jalan || `surat-jalan-${suratJalanId}`)
            .replace(/[\\/:*?"<>|]/g, "-")
            .replace(/\s+/g, "-");

        doc.save(`${safeNumber}.pdf`);
    };

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

                <div className="flex items-center gap-3">
                    <button
                        onClick={handleExportPdf}
                        className="flex items-center gap-2 bg-green-600 shadow-lg shadow-black/10 text-white px-4 py-2 rounded-lg hover:-translate-y-1 transition cursor-pointer"
                    >
                        <FileDown size={16} />
                        Export
                    </button>
                </div>
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
                            <h2 className="text-lg font-semibold">Edit Keterangan Item</h2>

                            {errorMessage ? (
                                <div className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                                    {errorMessage}
                                </div>
                            ) : null}

                            <div className="rounded-md bg-slate-50 px-3 py-3 text-sm text-slate-700 space-y-1">
                                <p>
                                    Barang: <span className="font-semibold">{editTarget?.nama_barang ?? "-"}</span>
                                </p>
                                <p>
                                    Qty: <span className="font-semibold">{editTarget ? Number(editTarget.qty) : 0}</span>
                                </p>
                                <p>
                                    Satuan: <span className="font-semibold">{editTarget?.satuan ?? "-"}</span>
                                </p>
                            </div>

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
