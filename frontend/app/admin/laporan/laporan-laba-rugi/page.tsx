"use client";

import { useEffect, useMemo, useState } from "react";
import { CalendarRange, HandCoins, ReceiptText, Wallet } from "lucide-react";
import api from "@/lib/api";
import { extractErrorMessage, formatCurrency } from "@/lib/transaksiPembelian";

type SppgOption = {
    id: number;
    nama_sppg: string;
};

type InvoiceRow = {
    id: number;
    tanggal_kirim: string | null;
    tanggal_invoice: string | null;
    nomor_invoice: string;
    no_po: string;
    sppg: string;
    pendapatan: number;
    status_pembayaran: string;
};

type PemasukanRow = {
    id: number;
    tanggal: string | null;
    jenis: string;
    jumlah: number;
    keterangan: string;
};

type PengeluaranRow = {
    id: number;
    tanggal: string | null;
    nama_operasional: string;
    qty: number;
    satuan: string;
    harga_satuan: number;
    total: number;
};

type ReportData = {
    filters: {
        tanggal_awal: string;
        tanggal_akhir: string;
        sppg_id: number | null;
        sppg: string | null;
    };
    summary: {
        total_pendapatan_penjualan: number;
        total_pemasukan_lain: number;
        total_pengeluaran: number;
        laba_bersih: number;
    };
    invoice_rows: InvoiceRow[];
    pemasukan_rows: PemasukanRow[];
    pengeluaran_rows: PengeluaranRow[];
    sppg_options: SppgOption[];
};

type ApiDetailResponse<T> = {
    message: string;
    data: T;
};

type FilterState = {
    tanggal_awal: string;
    tanggal_akhir: string;
    sppg_id: string;
};

const formatInputDate = (date: Date) => {
    const year = date.getFullYear();
    const month = `${date.getMonth() + 1}`.padStart(2, "0");
    const day = `${date.getDate()}`.padStart(2, "0");
    return `${year}-${month}-${day}`;
};

const today = new Date();
const defaultFilters: FilterState = {
    tanggal_awal: formatInputDate(new Date(today.getFullYear(), today.getMonth(), 1)),
    tanggal_akhir: formatInputDate(today),
    sppg_id: "",
};

const formatDate = (value: string | null) => {
    if (!value) return "-";

    return new Intl.DateTimeFormat("id-ID", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
    }).format(new Date(value));
};

const formatStatus = (value: string) =>
    value === "lunas" ? "Lunas" : value === "belum lunas" ? "Belum Lunas" : value;

export default function Page() {
    const [filters, setFilters] = useState<FilterState>(defaultFilters);
    const [appliedFilters, setAppliedFilters] = useState<FilterState>(defaultFilters);
    const [reportData, setReportData] = useState<ReportData | null>(null);
    const [loading, setLoading] = useState(true);
    const [errorMessage, setErrorMessage] = useState("");

    const fetchReport = async () => {
        try {
            setLoading(true);
            setErrorMessage("");

            const response = await api.get<ApiDetailResponse<ReportData>>("/laporan/laba-rugi-transaksional", {
                params: {
                    tanggal_awal: appliedFilters.tanggal_awal || undefined,
                    tanggal_akhir: appliedFilters.tanggal_akhir || undefined,
                    sppg_id: appliedFilters.sppg_id || undefined,
                },
            });

            setReportData(response.data.data);
        } catch (error) {
            setErrorMessage(extractErrorMessage(error));
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        // eslint-disable-next-line react-hooks/set-state-in-effect
        void fetchReport();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [appliedFilters]);

    const summaryCards = useMemo(() => {
        if (!reportData) return [];

        return [
            {
                label: "Pendapatan Penjualan",
                value: formatCurrency(reportData.summary.total_pendapatan_penjualan),
                icon: ReceiptText,
                tone: "text-blue-700 bg-blue-50 border-blue-200",
            },
            {
                label: "Pemasukan Lain",
                value: formatCurrency(reportData.summary.total_pemasukan_lain),
                icon: HandCoins,
                tone: "text-emerald-700 bg-emerald-50 border-emerald-200",
            },
            {
                label: "Pengeluaran Operasional",
                value: formatCurrency(reportData.summary.total_pengeluaran),
                icon: Wallet,
                tone: "text-amber-700 bg-amber-50 border-amber-200",
            },
            {
                label: "Laba Bersih",
                value: formatCurrency(reportData.summary.laba_bersih),
                icon: CalendarRange,
                tone:
                    reportData.summary.laba_bersih >= 0
                        ? "text-green-700 bg-green-50 border-green-200"
                        : "text-red-700 bg-red-50 border-red-200",
            },
        ];
    }, [reportData]);

    return (
        <div className="p-6 space-y-6">
            <div className="flex justify-between items-center">
                <h1 className="text-3xl font-bold">Laporan Laba Rugi Transaksional</h1>
            </div>

            {errorMessage ? (
                <div className="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {errorMessage}
                </div>
            ) : null}

            <div className="rounded-xl bg-white p-4 shadow space-y-4">
                <div className="grid gap-4 md:grid-cols-4">
                    <div className="space-y-1">
                        <label className="text-sm font-medium text-gray-700">Tanggal Awal</label>
                        <input
                            type="date"
                            value={filters.tanggal_awal}
                            onChange={(e) => setFilters((prev) => ({ ...prev, tanggal_awal: e.target.value }))}
                            className="w-full rounded-md border p-2"
                        />
                    </div>

                    <div className="space-y-1">
                        <label className="text-sm font-medium text-gray-700">Tanggal Akhir</label>
                        <input
                            type="date"
                            value={filters.tanggal_akhir}
                            onChange={(e) => setFilters((prev) => ({ ...prev, tanggal_akhir: e.target.value }))}
                            className="w-full rounded-md border p-2"
                        />
                    </div>

                    <div className="space-y-1">
                        <label className="text-sm font-medium text-gray-700">SPPG</label>
                        <select
                            value={filters.sppg_id}
                            onChange={(e) => setFilters((prev) => ({ ...prev, sppg_id: e.target.value }))}
                            className="w-full rounded-md border p-2"
                        >
                            <option value="">Semua SPPG</option>
                            {(reportData?.sppg_options ?? []).map((option) => (
                                <option key={option.id} value={option.id}>
                                    {option.nama_sppg}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="flex items-end gap-2">
                        <button
                            onClick={() => setAppliedFilters(filters)}
                            className="rounded-lg bg-linear-to-t from-secondary via-primary to-secondary px-4 py-2 text-white shadow-lg shadow-black/20 transition hover:-translate-y-1"
                        >
                            Terapkan
                        </button>
                        <button
                            onClick={() => {
                                setFilters(defaultFilters);
                                setAppliedFilters(defaultFilters);
                            }}
                            className="rounded-lg bg-gray-200 px-4 py-2 text-gray-800"
                        >
                            Reset
                        </button>
                    </div>
                </div>

                {appliedFilters.sppg_id ? (
                    <p className="text-sm text-gray-500">
                        Filter SPPG hanya memengaruhi tabel pendapatan penjualan. Pemasukan lain dan pengeluaran tetap ditampilkan global per periode.
                    </p>
                ) : null}
            </div>

            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                {summaryCards.map((card) => {
                    const Icon = card.icon;

                    return (
                        <div key={card.label} className={`rounded-xl border p-4 shadow-sm ${card.tone}`}>
                            <div className="flex items-start justify-between gap-3">
                                <div className="space-y-1">
                                    <p className="text-sm font-medium">{card.label}</p>
                                    <p className="text-2xl font-bold">{card.value}</p>
                                </div>
                                <Icon size={22} />
                            </div>
                        </div>
                    );
                })}
            </div>

            <div className="rounded-xl bg-white shadow overflow-hidden">
                <div className="border-b px-4 py-3">
                    <h2 className="text-lg font-semibold">Pendapatan Penjualan per SPPG</h2>
                    <p className="text-sm text-gray-500">
                        Data pendapatan diambil dari invoice penjualan pada periode yang dipilih.
                    </p>
                </div>

                <div className="overflow-auto">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-100">
                            <tr>
                                <th className="p-3 text-left">No</th>
                                <th className="p-3 text-left">Tanggal Kirim</th>
                                <th className="p-3 text-left">Tanggal Invoice</th>
                                <th className="p-3 text-left">SPPG</th>
                                <th className="p-3 text-left">Nomor Invoice</th>
                                <th className="p-3 text-left">Pendapatan</th>
                                <th className="p-3 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {loading ? (
                                <tr>
                                    <td colSpan={7} className="p-6 text-center text-gray-500">
                                        Memuat laporan...
                                    </td>
                                </tr>
                            ) : (reportData?.invoice_rows.length ?? 0) === 0 ? (
                                <tr>
                                    <td colSpan={7} className="p-6 text-center text-gray-500">
                                        Belum ada data invoice pada periode ini.
                                    </td>
                                </tr>
                            ) : (
                                reportData?.invoice_rows.map((row, index) => (
                                    <tr key={row.id} className="border-t">
                                        <td className="p-3">{index + 1}</td>
                                        <td className="p-3">{formatDate(row.tanggal_kirim)}</td>
                                        <td className="p-3">{formatDate(row.tanggal_invoice)}</td>
                                        <td className="p-3">{row.sppg}</td>
                                        <td className="p-3">{row.nomor_invoice}</td>
                                        <td className="p-3">{formatCurrency(row.pendapatan)}</td>
                                        <td className="p-3">{formatStatus(row.status_pembayaran)}</td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            <div className="grid gap-6 xl:grid-cols-2">
                <div className="rounded-xl bg-white shadow overflow-hidden">
                    <div className="border-b px-4 py-3">
                        <h2 className="text-lg font-semibold">Pemasukan Lain</h2>
                    </div>

                    <div className="overflow-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-gray-100">
                                <tr>
                                    <th className="p-3 text-left">Tanggal</th>
                                    <th className="p-3 text-left">Jenis</th>
                                    <th className="p-3 text-left">Jumlah</th>
                                    <th className="p-3 text-left">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                {loading ? (
                                    <tr>
                                        <td colSpan={4} className="p-6 text-center text-gray-500">
                                            Memuat data...
                                        </td>
                                    </tr>
                                ) : (reportData?.pemasukan_rows.length ?? 0) === 0 ? (
                                    <tr>
                                        <td colSpan={4} className="p-6 text-center text-gray-500">
                                            Belum ada pemasukan lain pada periode ini.
                                        </td>
                                    </tr>
                                ) : (
                                    reportData?.pemasukan_rows.map((row) => (
                                        <tr key={row.id} className="border-t">
                                            <td className="p-3">{formatDate(row.tanggal)}</td>
                                            <td className="p-3 capitalize">{row.jenis}</td>
                                            <td className="p-3">{formatCurrency(row.jumlah)}</td>
                                            <td className="p-3">{row.keterangan}</td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="rounded-xl bg-white shadow overflow-hidden">
                    <div className="border-b px-4 py-3">
                        <h2 className="text-lg font-semibold">Pengeluaran Operasional</h2>
                    </div>

                    <div className="overflow-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-gray-100">
                                <tr>
                                    <th className="p-3 text-left">Tanggal</th>
                                    <th className="p-3 text-left">Operasional</th>
                                    <th className="p-3 text-left">Qty</th>
                                    <th className="p-3 text-left">Satuan</th>
                                    <th className="p-3 text-left">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                {loading ? (
                                    <tr>
                                        <td colSpan={5} className="p-6 text-center text-gray-500">
                                            Memuat data...
                                        </td>
                                    </tr>
                                ) : (reportData?.pengeluaran_rows.length ?? 0) === 0 ? (
                                    <tr>
                                        <td colSpan={5} className="p-6 text-center text-gray-500">
                                            Belum ada pengeluaran pada periode ini.
                                        </td>
                                    </tr>
                                ) : (
                                    reportData?.pengeluaran_rows.map((row) => (
                                        <tr key={row.id} className="border-t">
                                            <td className="p-3">{formatDate(row.tanggal)}</td>
                                            <td className="p-3">{row.nama_operasional}</td>
                                            <td className="p-3">{row.qty}</td>
                                            <td className="p-3">{row.satuan}</td>
                                            <td className="p-3">{formatCurrency(row.total)}</td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    );
}
