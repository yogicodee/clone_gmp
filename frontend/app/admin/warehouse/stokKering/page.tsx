"use client";

import { useMemo, useState } from "react";
import { ArrowUpDown } from "lucide-react";
import { useFetch } from "@/hooks/useFetch";

type GudangOption = {
    id: number;
    nama_gudang: string;
};

type Product = {
    id: number;
    gudang_id: number;
    nama_barang: string;
    qty: number | string;
    satuan_terkecil: string;
    harga_beli: number | string;
    gudang?: GudangOption | null;
};

type GroupedProduct = {
    id: string;
    gudang_id: number;
    nama_barang: string;
    qty: number;
    satuan_terkecil: string;
    harga_beli: number;
    gudang?: GudangOption | null;
};

export default function Page() {
    const { data } = useFetch<Product>("/stok-kering");

    const [search, setSearch] = useState("");
    const [sortField, setSortField] = useState<keyof Product>("nama_barang");
    const [sortOrder, setSortOrder] = useState<"asc" | "desc">("asc");
    const [currentPage, setCurrentPage] = useState(1);
    const perPage = 10;

    const groupedData = useMemo<GroupedProduct[]>(() => {
        const groupedMap = new Map<string, GroupedProduct>();

        for (const item of data) {
            const hargaBeli = Number(item.harga_beli);
            const qty = Number(item.qty);
            const key = [
                item.nama_barang.trim().toLowerCase(),
                item.gudang_id,
                item.satuan_terkecil.trim().toLowerCase(),
                hargaBeli,
            ].join("|");

            const existing = groupedMap.get(key);

            if (existing) {
                existing.qty += qty;
                continue;
            }

            groupedMap.set(key, {
                id: key,
                gudang_id: item.gudang_id,
                nama_barang: item.nama_barang,
                qty,
                satuan_terkecil: item.satuan_terkecil,
                harga_beli: hargaBeli,
                gudang: item.gudang ?? null,
            });
        }

        return Array.from(groupedMap.values());
    }, [data]);

    const filteredData = useMemo(() => {
        let result = [...groupedData];

        if (search) {
            result = result.filter(
                (item) =>
                    item.nama_barang.toLowerCase().includes(search.toLowerCase()) ||
                    item.gudang?.nama_gudang?.toLowerCase().includes(search.toLowerCase())
            );
        }

        result.sort((a, b) => {
            const aVal = String(a[sortField]).toLowerCase();
            const bVal = String(b[sortField]).toLowerCase();

            if (sortOrder === "asc") return aVal.localeCompare(bVal);
            return bVal.localeCompare(aVal);
        });

        return result;
    }, [groupedData, search, sortField, sortOrder]);

    const totalPages = Math.ceil(filteredData.length / perPage);
    const normalizedCurrentPage = totalPages === 0 ? 1 : Math.min(currentPage, totalPages);

    const paginatedData = filteredData.slice(
        (normalizedCurrentPage - 1) * perPage,
        normalizedCurrentPage * perPage
    );

    const handleSort = (field: keyof Product) => {
        if (sortField === field) {
            setSortOrder(sortOrder === "asc" ? "desc" : "asc");
        } else {
            setSortField(field);
            setSortOrder("asc");
        }
    };

    return (
        <div className="p-6 space-y-6">
            <div className="flex justify-between items-center">
                <h1 className="text-3xl font-bold">Stok Bahan Kering</h1>
            </div>

            <div className="flex items-center justify-between">
                <input
                    placeholder="Cari barang atau gudang..."
                    value={search}
                    onChange={(e) => {
                        setSearch(e.target.value);
                        setCurrentPage(1);
                    }}
                    className="border p-2 rounded-md w-1/4 bg-white shadow"
                />
            </div>

            <div className="bg-white/70 backdrop-blur-lg rounded-lg shadow overflow-auto">
                <table className="w-full text-sm">
                    <thead className="bg-white shadow-lg">
                        <tr>
                            <th className="p-3">No</th>
                            <th className="p-3">
                                <button onClick={() => handleSort("nama_barang")} className="flex items-center gap-2">
                                    Nama Barang <ArrowUpDown size={14} />
                                </button>
                            </th>
                            <th className="p-3">
                                <button onClick={() => handleSort("gudang_id")} className="flex items-center gap-2">
                                    Gudang <ArrowUpDown size={14} />
                                </button>
                            </th>
                            <th className="p-3 text-left">Qty</th>
                            <th className="p-3 text-left">Satuan</th>
                            <th className="p-3 text-left">Harga Beli</th>
                        </tr>
                    </thead>

                    <tbody>
                        {paginatedData.length > 0 ? (
                            paginatedData.map((item, index) => (
                                <tr key={item.id} className="border-t border-primary/20 hover:bg-white/50">
                                    <td className="p-3 text-center">
                                        {(normalizedCurrentPage - 1) * perPage + index + 1}
                                    </td>
                                    <td className="p-3">{item.nama_barang}</td>
                                    <td className="p-3">{item.gudang?.nama_gudang ?? "-"}</td>
                                    <td className="p-3">{Number(item.qty)}</td>
                                    <td className="p-3">{item.satuan_terkecil}</td>
                                    <td className="p-3">
                                        Rp {Number(item.harga_beli).toLocaleString("id-ID")}
                                    </td>
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td colSpan={6} className="p-6 text-center text-gray-500">
                                    Belum ada data stok kering.
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
                    className="px-3 py-1 border border-white rounded-md"
                >
                    Prev
                </button>

                {Array.from({ length: totalPages }, (_, i) => (
                    <button
                        key={i}
                        onClick={() => setCurrentPage(i + 1)}
                        className={`px-3 py-1 border border-white rounded-md ${normalizedCurrentPage === i + 1 ? "bg-primary text-white" : ""}`}
                    >
                        {i + 1}
                    </button>
                ))}

                <button
                    disabled={normalizedCurrentPage === totalPages || totalPages === 0}
                    onClick={() => setCurrentPage((p) => p + 1)}
                    className="px-3 py-1 border border-white rounded-md"
                >
                    Next
                </button>
            </div>
        </div>
    );
}
