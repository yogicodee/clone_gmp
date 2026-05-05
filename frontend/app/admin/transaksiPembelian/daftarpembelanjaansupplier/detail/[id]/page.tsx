"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import { useRouter, useParams } from "next/navigation";

import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";

import api from "@/lib/api";
import {
    ApiDetailResponse,
    ApiListResponse,
    DaftarPembelanjaan,
    DaftarPembelanjaanItem,
    SupplierOption,
    extractErrorMessage,
} from "@/lib/transaksiPembelian";

type WarehouseStockItem = {
    nama_barang: string;
    qty: number | string;
    satuan_terkecil: string;
};

function normalizeUnit(value: string) {
    const normalized = value.trim().toLowerCase();

    if (["pcs", "pc", "piece", "pieces", "piecis", "picis"].includes(normalized)) {
        return "pcs";
    }

    if (["kg", "kgs", "kilogram"].includes(normalized)) {
        return "kg";
    }

    if (["ltr", "lt", "liter", "litre"].includes(normalized)) {
        return "liter";
    }

    return normalized;
}

function createStockKey(namaBarang: string, satuan: string) {
    return `${namaBarang.trim().toLowerCase()}|${normalizeUnit(satuan)}`;
}

/* ================= PAGE ================= */
export default function Page() {
    const params = useParams<{ id: string }>();
    const router = useRouter();
    const daftarPembelanjaanId = Number(params.id);

    const [detail, setDetail] = useState<DaftarPembelanjaan | null>(null);
    const [items, setItems] = useState<DaftarPembelanjaanItem[]>([]);
    const [warehouseStockMap, setWarehouseStockMap] = useState<Record<string, number>>({});
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");

    const [selectedSupplier, setSelectedSupplier] = useState<string | null>(null);

    const [currentPage, setCurrentPage] = useState(1);
    const perPage = 10;

    /* ================= FETCH ================= */
    const [supplierOptions, setSupplierOptions] = useState<SupplierOption[]>([]);

    const fetchData = useCallback(async () => {
        try {
            setLoading(true);
            setError("");

            const [detailRes, supplierRes, stokKeringRes, stokBasahRes] = await Promise.all([
                api.get<ApiDetailResponse<DaftarPembelanjaan>>(
                    `/daftar-pembelanjaan/${daftarPembelanjaanId}`
                ),
                api.get<ApiListResponse<SupplierOption>>("/supplier", {
                    params: { per_page: 100 },
                }),
                api.get<ApiListResponse<WarehouseStockItem>>("/stok-kering", {
                    params: { per_page: 100 },
                }),
                api.get<ApiListResponse<WarehouseStockItem>>("/stok-basah", {
                    params: { per_page: 100 },
                }),
            ]);

            const data = detailRes.data.data;

            setDetail(data);
            setItems(data.items ?? []);
            setSupplierOptions(supplierRes.data.data ?? []);

            const stockMap: Record<string, number> = {};
            const warehouseStocks = [
                ...(stokKeringRes.data.data ?? []),
                ...(stokBasahRes.data.data ?? []),
            ];

            warehouseStocks.forEach((item) => {
                const key = createStockKey(item.nama_barang, item.satuan_terkecil);
                stockMap[key] = (stockMap[key] ?? 0) + Number(item.qty || 0);
            });

            setWarehouseStockMap(stockMap);
        } catch (err) {
            setError(extractErrorMessage(err));
        } finally {
            setLoading(false);
        }
    }, [daftarPembelanjaanId]);

    const itemsWithSupplier = useMemo(() => {
        return items.map((item) => {
            const supplier = supplierOptions.find(
                (s) => s.id === item.supplier_id
            );

            return {
                ...item,
                nama_supplier: supplier?.nama || "-",

                qty: Number(item.qty) || 0,
                stok: warehouseStockMap[createStockKey(item.nama_barang, item.satuan)] ?? 0,
                kebutuhan: Number(item.kebutuhan) || 0,
            };
        });
    }, [items, supplierOptions, warehouseStockMap]);


    useEffect(() => {
        if (!Number.isNaN(daftarPembelanjaanId)) {
            // eslint-disable-next-line react-hooks/set-state-in-effect
            void fetchData();
        }
    }, [daftarPembelanjaanId, fetchData]);

    /* ================= SUPPLIER LIST ================= */
    const suppliers = useMemo(() => {
        const map = new Map<string, { nama_supplier: string }>();

        itemsWithSupplier.forEach((item) => {
            if (item.nama_supplier && !map.has(item.nama_supplier)) {
                map.set(item.nama_supplier, {
                    nama_supplier: item.nama_supplier,
                });
            }
        });

        return Array.from(map.values());
    }, [itemsWithSupplier]);

    /* ================= DEFAULT SUPPLIER ================= */
    const activeSelectedSupplier = selectedSupplier ?? suppliers[0]?.nama_supplier ?? null;

    /* ================= FILTER + KEBUTUHAN ================= */
    const filteredItems = useMemo(() => {
        return itemsWithSupplier.filter((item) => {
            if (!activeSelectedSupplier) return true;
            return item.nama_supplier === activeSelectedSupplier;
        });
    }, [activeSelectedSupplier, itemsWithSupplier]);

    /* ================= PAGINATION ================= */
    const totalPages = Math.max(1, Math.ceil(filteredItems.length / perPage));
    const normalizedCurrentPage = Math.min(currentPage, totalPages);

    const paginatedItems = filteredItems.slice(
        (normalizedCurrentPage - 1) * perPage,
        normalizedCurrentPage * perPage
    );

    // Export PDF
    function handleExportPDF() {
        const doc = new jsPDF();

        const title = `Detail Pembelanjaan #${daftarPembelanjaanId}`;
        const tanggal = detail?.tanggal_pesan
            ? new Date(detail.tanggal_pesan).toLocaleDateString("id-ID")
            : "-";

        doc.setFontSize(14);
        doc.text(title, 14, 15);
        doc.setFontSize(11);
        doc.text(`Tanggal Pesan: ${tanggal}`, 14, 22);
        doc.text(`Supplier: ${activeSelectedSupplier ?? "Semua"}`, 14, 28);

        const tableData = filteredItems.map((item, index) => [
            index + 1,
            item.nama_barang,
            item.qty,
            item.satuan,
            item.stok,
            item.kebutuhan,
        ]);

        autoTable(doc, {
            startY: 35,
            head: [
                ["No", "Nama Barang", "Qty", "Satuan", "Stok", "Kebutuhan"],
            ],
            body: tableData,
            // 🔥 HEADER MERAH
            headStyles: {
                fillColor: [220, 38, 38], // merah (Tailwind red-600)
                textColor: 255, // putih
                fontStyle: "bold",
            },

            // optional: biar lebih rapi
            styles: {
                fontSize: 10,
            },
        });

        doc.save(`pembelanjaan-${daftarPembelanjaanId}.pdf`);
    }

    /* ================= RENDER ================= */
    return (
        <div className="p-6 space-y-4">

            {/* HEADER */}
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-2xl font-bold">
                        Detail Order #{params?.id}
                    </h1>

                    {/* ✅ TANGGAL DARI TABEL */}
                    <p className="text-sm text-gray-600">
                        Tanggal Pesan:{" "}
                        <span className="font-semibold">
                            {detail?.tanggal_pesan
                                ? new Date(detail.tanggal_pesan).toLocaleDateString("id-ID")
                                : "-"}
                        </span>
                    </p>
                </div>

                <button
                    onClick={() => router.back()}
                    className="px-4 py-2 bg-white rounded-md shadow"
                >
                    Kembali
                </button>
            </div>

            {error && (
                <div className="bg-red-100 text-red-700 p-3 rounded-md">
                    {error}
                </div>
            )}

            {/* LAYOUT */}
            <div className="grid grid-cols-3 gap-4">

                {/* LEFT: SUPPLIER */}
                <div className="col-span-1 bg-white rounded-lg shadow p-4">

                    <h2 className="font-semibold mb-3">Supplier</h2>

                    <div className="space-y-2">
                        {suppliers.map((sup) => (
                            <div
                                key={sup.nama_supplier}
                                onClick={() => {
                                    setSelectedSupplier(sup.nama_supplier);
                                    setCurrentPage(1);
                                }}
                                className={`p-3 rounded-md cursor-pointer border ${activeSelectedSupplier === sup.nama_supplier
                                    ? "bg-lime-200 border-green-500"
                                    : "hover:bg-gray-100"
                                    }`}
                            >
                                {sup.nama_supplier}
                            </div>
                        ))}
                    </div>
                </div>

                {/* RIGHT: TABLE */}
                <div className="col-span-2 bg-white rounded-lg shadow p-4">
                    <div className="flex items-center justify-between mb-4">
                        <h2 className="font-semibold mb-3">
                            Detail Barang ({activeSelectedSupplier ?? "Semua"})
                        </h2>
                        <button
                            onClick={handleExportPDF}
                            className="px-4 py-2 bg-green-600 text-xs font-semibold text-white rounded-md hover:bg-green-700"
                        >
                            Export PDF
                        </button>
                    </div>

                    <table className="w-full text-sm">
                        <thead className="bg-gray-100">
                            <tr>
                                <th className="p-2">No</th>
                                <th className="p-2 text-left">Barang</th>
                                <th className="p-2">Qty</th>
                                <th className="p-2">Satuan</th>
                                <th className="p-2">Stok</th>
                                <th className="p-2">Kebutuhan</th>
                            </tr>
                        </thead>

                        <tbody>
                            {loading ? (
                                <tr>
                                    <td colSpan={6} className="p-4 text-center">
                                        Loading...
                                    </td>
                                </tr>
                            ) : paginatedItems.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="p-4 text-center">
                                        Tidak ada data
                                    </td>
                                </tr>
                            ) : (
                                paginatedItems.map((item, index) => (
                                    <tr key={item.id} className="border-t">
                                        <td className="p-2 text-center">
                                            {(normalizedCurrentPage - 1) * perPage + index + 1}
                                        </td>
                                        <td className="p-2">{item.nama_barang}</td>
                                        <td className="p-2 text-center">{item.qty}</td>
                                        <td className="p-2 text-center">{item.satuan}</td>
                                        <td className="p-2 text-center">{item.stok}</td>
                                        <td className="p-2 text-center">
                                            {item.kebutuhan}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>

                    {/* PAGINATION */}
                    <div className="flex justify-end gap-2 mt-4">
                        <button
                            disabled={normalizedCurrentPage === 1}
                            onClick={() => setCurrentPage((prev) => prev - 1)}
                            className="px-3 py-1 border rounded-md disabled:opacity-50"
                        >
                            Prev
                        </button>

                        {Array.from({ length: totalPages }, (_, i) => (
                            <button
                                key={i}
                                onClick={() => setCurrentPage(i + 1)}
                                className={`px-3 py-1 border rounded-md ${normalizedCurrentPage === i + 1 ? "bg-blue-500 text-white" : ""
                                    }`}
                            >
                                {i + 1}
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
                </div>
            </div>
        </div>
    );
}
