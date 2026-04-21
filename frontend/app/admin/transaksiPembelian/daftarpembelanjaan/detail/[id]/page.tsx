"use client";

import { useState, useMemo, useEffect } from "react";
import { Pencil, ArrowUpDown } from "lucide-react";
import { useRouter, useParams } from "next/navigation";
import { useFetch } from "@/hooks/useFetch";
import axios from "axios";

/* ================= TYPE ================= */
type Item = {
    id: number;
    nama_barang: string;
    qty: number;
    satuan: string;
    stok?: number;
    nama_supplier?: string;
    harga_satuan?: number;

    order_penawaran_id?: number;
    orderPenawaranId?: number;
    order_id?: number;
    order_penawaran?: { id: number };
};

type GroupedItem = Item & {
    ids: number[]; // 🔥 penting untuk update massal
    kebutuhan: number;
};

type Supplier = {
    id: number;
    nama: string;
};

export default function Page() {
    const router = useRouter();
    const params = useParams();
    const tanggal = params.id;

    const endpoint =
        tanggal && typeof tanggal === "string"
            ? `http://localhost:8000/api/order-penawaran/filter/by-tanggal?tanggal=${tanggal}`
            : null;

    const { data: resData, loading } = useFetch<any>(endpoint);

    /* ================= STATE ================= */
    const [localData, setLocalData] = useState<Item[]>([]);
    const [orderId, setOrderId] = useState<number | null>(null);

    const [openModal, setOpenModal] = useState(false);
    const [selectedItem, setSelectedItem] = useState<GroupedItem | null>(null);
    const [supplier, setSupplier] = useState("");

    const [search, setSearch] = useState("");
    const [sortField, setSortField] = useState<keyof Item>("nama_barang");
    const [sortOrder, setSortOrder] = useState<"asc" | "desc">("asc");

    const [currentPage, setCurrentPage] = useState(1);
    const perPage = 10;

    const [suppliers, setSuppliers] = useState<Supplier[]>([]);

    /* ================= FETCH SUPPLIER ================= */
    useEffect(() => {
        const fetchSuppliers = async () => {
            try {
                const res = await axios.get("http://localhost:8000/api/supplier");
                setSuppliers(res.data.data || res.data);
            } catch (err) {
                console.error("Gagal ambil supplier", err);
            }
        };

        fetchSuppliers();
    }, []);

    /* ================= LOAD DATA ================= */
    useEffect(() => {
        const raw: Item[] = Array.isArray(resData?.data)
            ? resData.data
            : Array.isArray(resData)
                ? resData
                : [];

        setLocalData(raw);

        if (raw.length > 0) {
            const first = raw[0];

            const detectedId =
                first.order_penawaran_id ||
                first.orderPenawaranId ||
                first.order_id ||
                first.order_penawaran?.id ||
                null;

            setOrderId(detectedId);
        }
    }, [resData]);

    /* ================= GROUPING ================= */
    const filteredData: GroupedItem[] = useMemo(() => {
        const grouped: Record<string, GroupedItem> = {};

        localData.forEach((item) => {
            const key = `${item.nama_barang}-${item.satuan}`;

            if (!grouped[key]) {
                grouped[key] = {
                    ...item,
                    ids: [item.id],
                    qty: Number(item.qty) || 0,
                    stok: item.stok || 0,
                    nama_supplier: item.nama_supplier || "-",
                    kebutuhan: 0,
                };
            } else {
                grouped[key].qty += Number(item.qty) || 0;
                grouped[key].stok += item.stok || 0;
                grouped[key].ids.push(item.id);
            }
        });

        let result = Object.values(grouped).map((item) => ({
            ...item,
            kebutuhan: Math.max(item.qty - (item.stok || 0), 0),
        }));

        if (search) {
            result = result.filter(
                (item) =>
                    item.nama_barang.toLowerCase().includes(search.toLowerCase()) ||
                    (item.nama_supplier || "").toLowerCase().includes(search.toLowerCase())
            );
        }

        result.sort((a, b) => {
            const aVal = String(a[sortField] ?? "").toLowerCase();
            const bVal = String(b[sortField] ?? "").toLowerCase();

            if (sortOrder === "asc") return aVal.localeCompare(bVal);
            return bVal.localeCompare(aVal);
        });

        return result;
    }, [localData, search, sortField, sortOrder]);

    /* ================= PAGINATION ================= */
    const totalPages = Math.max(1, Math.ceil(filteredData.length / perPage));

    const paginatedData = filteredData.slice(
        (currentPage - 1) * perPage,
        currentPage * perPage
    );

    useEffect(() => {
        setCurrentPage(1);
    }, [search]);

    const handleSort = (field: keyof Item) => {
        if (sortField === field) {
            setSortOrder(sortOrder === "asc" ? "desc" : "asc");
        } else {
            setSortField(field);
            setSortOrder("asc");
        }
    };

    /* ================= SAVE ================= */
    const handleSave = async () => {
        if (!selectedItem) {
            alert("Data tidak lengkap");
            return;
        }

        try {
            const payload = {
                nama_barang: selectedItem.nama_barang,
                qty: Number(selectedItem.qty),
                satuan: selectedItem.satuan,
                nama_supplier: supplier,
                harga_satuan: selectedItem.harga_satuan || 1,
            };

            await Promise.all(
                selectedItem.ids.map((id) => {
                    const item = localData.find((x) => x.id === id);

                    if (!item || !item.order_penawaran_id) {
                        console.warn("SKIP ITEM:", id);
                        return Promise.resolve();
                    }

                    return axios.put(
                        `http://localhost:8000/api/order-penawaran/${item.order_penawaran_id}/items/${id}`,
                        payload
                    );
                })
            );

            // update local state
            setLocalData((prev) =>
                prev.map((item) =>
                    selectedItem.ids.includes(item.id)
                        ? { ...item, nama_supplier: supplier }
                        : item
                )
            );

            setOpenModal(false);
        } catch (err: any) {
            console.log("ERROR:", err.response?.data);
            alert("Gagal update, cek console");
        }
    };

    return (
        <div className="p-6 space-y-6">
            <div className="flex justify-between items-center">
                <h1 className="text-3xl font-bold">
                    Detail Order Tanggal {tanggal}
                </h1>

                <button
                    onClick={() => router.back()}
                    className="px-4 py-2 bg-white rounded-md"
                >
                    Kembali
                </button>
            </div>

            <div className="flex justify-between">
                <input
                    placeholder="Cari barang / supplier..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="border p-2 rounded-md w-1/4 bg-white"
                />
            </div>

            <div className="bg-white/70 backdrop-blur-lg rounded-lg shadow overflow-auto">
                <table className="w-full text-sm">
                    <thead className="bg-white shadow-lg">
                        <tr>
                            <th className="p-3">No</th>
                            <th className="p-3 text-left cursor-pointer" onClick={() => handleSort("nama_barang")}>
                                Barang <ArrowUpDown size={14} className="inline" />
                            </th>
                            <th className="p-3">Qty</th>
                            <th className="p-3">Satuan</th>
                            <th className="p-3">Stok</th>
                            <th className="p-3">Kebutuhan</th>
                            <th className="p-3">Supplier</th>
                            <th className="p-3 text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        {loading ? (
                            <tr><td colSpan={8} className="p-3 text-center">Loading...</td></tr>
                        ) : paginatedData.map((item, index) => (
                            <tr key={item.ids.join("-")} className="border-t hover:bg-white/50">
                                <td className="p-3 text-center">
                                    {(currentPage - 1) * perPage + index + 1}
                                </td>
                                <td className="p-3">{item.nama_barang}</td>
                                <td className="p-3">{item.qty}</td>
                                <td className="p-3">{item.satuan}</td>
                                <td className="p-3">{item.stok}</td>
                                <td className="p-3">{item.kebutuhan}</td>
                                <td className="p-3">{item.nama_supplier}</td>

                                <td className="p-3 flex justify-center">
                                    <button
                                        onClick={() => {
                                            setSelectedItem(item);
                                            setSupplier(item.nama_supplier || "");
                                            setOpenModal(true);
                                        }}
                                        className="p-2 bg-blue-500/30 rounded"
                                    >
                                        <Pencil size={14} />
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {openModal && selectedItem && (
                <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
                    <div className="bg-white p-6 rounded-lg w-[400px] space-y-4">
                        <h2 className="text-xl font-bold">Edit Supplier</h2>

                        <div className="text-sm text-gray-600">
                            <p><b>Barang:</b> {selectedItem.nama_barang}</p>
                            <p><b>Qty:</b> {selectedItem.qty}</p>
                            <p><b>Satuan:</b> {selectedItem.satuan}</p>
                        </div>

                        <select
                            value={supplier}
                            onChange={(e) => setSupplier(e.target.value)}
                            className="w-full border p-2 rounded"
                        >
                            <option value="">-- Pilih Supplier --</option>
                            {suppliers.map((sup) => (
                                <option key={sup.id} value={sup.nama}>
                                    {sup.nama}
                                </option>
                            ))}
                        </select>

                        <div className="flex justify-end gap-2">
                            <button onClick={() => setOpenModal(false)} className="px-3 py-1 bg-gray-200 rounded">
                                Batal
                            </button>

                            <button
                                onClick={handleSave}
                                className="px-3 py-1 bg-green-500 text-white rounded"
                            >
                                Simpan
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}