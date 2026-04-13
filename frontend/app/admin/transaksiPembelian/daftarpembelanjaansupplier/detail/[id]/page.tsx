"use client";

import { useState, useMemo } from "react";
import { useRouter, useParams } from "next/navigation";

/* ================= TYPE ================= */
type Item = {
    id: number;
    nama_barang: string;
    qty: number;
    satuan: string;
    stok: number;
    kebutuhan: number;
    nama_supplier: string;
};

export default function Page() {
    const router = useRouter();
    const params = useParams();

    const [data] = useState<Item[]>([
        {
            id: 1,
            nama_barang: "Beras",
            qty: 10,
            satuan: "Kg",
            stok: 5,
            kebutuhan: 15,
            nama_supplier: "PT Sumber Pangan",
        },
        {
            id: 2,
            nama_barang: "Minyak Goreng",
            qty: 20,
            satuan: "Liter",
            stok: 10,
            kebutuhan: 25,
            nama_supplier: "PT Sumber Pangan",
        },
        {
            id: 3,
            nama_barang: "Gula",
            qty: 15,
            satuan: "Kg",
            stok: 8,
            kebutuhan: 20,
            nama_supplier: "CV Makmur Jaya",
        },
    ]);

    /* ================= SUPPLIER LIST ================= */
    const suppliers = useMemo(() => {
        const unique = Array.from(
            new Map(data.map((item) => [item.nama_supplier, item])).values()
        );
        return unique;
    }, [data]);

    const [selectedSupplier, setSelectedSupplier] = useState<string | null>(
        suppliers[0]?.nama_supplier || null
    );

    /* ================= FILTER DETAIL ================= */
    const detailData = useMemo(() => {
        return data.filter(
            (item) => item.nama_supplier === selectedSupplier
        );
    }, [data, selectedSupplier]);

    return (
        <div className="p-6 space-y-4">
            {/* HEADER */}
            <div className="flex justify-between items-center">
                <h1 className="text-xl font-bold">
                    Detail Order #{params?.id}
                </h1>

                <button
                    onClick={() => router.back()}
                    className="px-4 py-2 bg-gray-200 rounded-md"
                >
                    Kembali
                </button>
            </div>

            {/* SPLIT LAYOUT */}
            <div className="grid grid-cols-3 gap-4">

                {/* LEFT - SUPPLIER */}
                <div className="col-span-1 bg-white rounded-lg shadow p-4">
                    <h2 className="font-semibold mb-3">Supplier</h2>

                    <div className="space-y-2">
                        {suppliers.map((sup) => (
                            <div
                                key={sup.nama_supplier}
                                onClick={() => setSelectedSupplier(sup.nama_supplier)}
                                className={`p-3 rounded-md cursor-pointer border ${selectedSupplier === sup.nama_supplier
                                    ? "bg-blue-100 border-blue-400"
                                    : "hover:bg-gray-100"
                                    }`}
                            >
                                <p className="font-medium">{sup.nama_supplier}</p>
                            </div>
                        ))}
                    </div>
                </div>

                {/* RIGHT - DETAIL */}
                <div className="col-span-2 bg-white rounded-lg shadow p-4">
                    <h2 className="font-semibold mb-3">
                        Detail Barang ({selectedSupplier})
                    </h2>

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
                            {detailData.map((item, index) => (
                                <tr key={item.id} className="border-t">
                                    <td className="p-2 text-center">{index + 1}</td>
                                    <td className="p-2">{item.nama_barang}</td>
                                    <td className="p-2 text-center">{item.qty}</td>
                                    <td className="p-2 text-center">{item.satuan}</td>
                                    <td className="p-2 text-center">{item.stok}</td>
                                    <td className="p-2 text-center">{item.kebutuhan}</td>
                                </tr>
                            ))}

                            {detailData.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="text-center p-4">
                                        Tidak ada data
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
}