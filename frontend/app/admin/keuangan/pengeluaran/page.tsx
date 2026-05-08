"use client";

import { useState, useMemo, useEffect } from "react";
import { Pencil, Trash2, Plus, ArrowUpDown } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";

/* ================= TYPE ================= */
type Product = {
    id: number;
    nama_operasional: string;
    tanggal_keluar: string;
    qty: number;
    satuan: string;
    harga_satuan: number;
};

type FormType = Omit<Product, "id">;

export default function Page() {
    const [data, setData] = useState<Product[]>([
        {
            id: 1,
            nama_operasional: "Beras",
            tanggal_keluar: "2026-05-05",
            qty: 5,
            satuan: "Kg",
            harga_satuan: 12000,
        },
    ]);

    const [form, setForm] = useState<FormType>({
        nama_operasional: "",
        tanggal_keluar: "",
        qty: 0,
        satuan: "",
        harga_satuan: 0,
    });

    const [editId, setEditId] = useState<number | null>(null);
    const [openForm, setOpenForm] = useState(false);
    const [deleteId, setDeleteId] = useState<number | null>(null);

    const [search, setSearch] = useState("");
    const [sortField, setSortField] = useState<keyof Product>("nama_operasional");
    const [sortOrder, setSortOrder] = useState<"asc" | "desc">("asc");

    const [currentPage, setCurrentPage] = useState(1);
    const perPage = 10;

    /* ================= HANDLE ================= */
    const handleSubmit = () => {
        if (!form.nama_operasional || !form.tanggal_keluar) return;

        if (editId) {
            setData((prev) =>
                prev.map((item) =>
                    item.id === editId ? { ...item, ...form } : item
                )
            );
        } else {
            setData((prev) => [
                ...prev,
                { id: Date.now(), ...form },
            ]);
        }

        resetForm();
    };

    const handleEdit = (item: Product) => {
        const { id, ...rest } = item;
        setForm(rest);
        setEditId(id);
        setOpenForm(true);
    };

    const handleDelete = () => {
        if (deleteId) {
            setData((prev) => prev.filter((item) => item.id !== deleteId));
            setDeleteId(null);
        }
    };

    const resetForm = () => {
        setForm({
            nama_operasional: "",
            tanggal_keluar: "",
            qty: 0,
            satuan: "",
            harga_satuan: 0,
        });
        setEditId(null);
        setOpenForm(false);
    };

    const handleSort = (field: keyof Product) => {
        if (sortField === field) {
            setSortOrder(sortOrder === "asc" ? "desc" : "asc");
        } else {
            setSortField(field);
            setSortOrder("asc");
        }
    };

    /* ================= FILTER ================= */
    const filteredData = useMemo(() => {
        let result = [...data];

        if (search) {
            result = result.filter((item) =>
                item.nama_operasional.toLowerCase().includes(search.toLowerCase())
            );
        }

        result.sort((a, b) => {
            const aVal = String(a[sortField]).toLowerCase();
            const bVal = String(b[sortField]).toLowerCase();

            return sortOrder === "asc"
                ? aVal.localeCompare(bVal)
                : bVal.localeCompare(aVal);
        });

        return result;
    }, [data, search, sortField, sortOrder]);

    const totalPages = Math.ceil(filteredData.length / perPage);

    const paginatedData = filteredData.slice(
        (currentPage - 1) * perPage,
        currentPage * perPage
    );

    useEffect(() => setCurrentPage(1), [search]);

    useEffect(() => {
        if (currentPage > totalPages) setCurrentPage(1);
    }, [filteredData]);

    return (
        <div className="p-6 space-y-6">
            <div className="flex justify-between items-center">
                <h1 className="text-3xl font-bold">Pengeluaran</h1>
            </div>

            <div className="flex items-center justify-between">
                <input
                    placeholder="Cari barang..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="border p-2 rounded-md w-1/4 bg-white shadow"
                />

                <button
                    onClick={() => setOpenForm(true)}
                    className="flex items-center gap-2 bg-linear-to-t from-secondary via-primary to-secondary shadow-lg text-white px-4 py-2 rounded-lg hover:-translate-y-1 transition"
                >
                    <Plus size={16} />
                    Tambah Data
                </button>
            </div>

            {/* TABLE */}
            <div className="bg-white rounded-lg shadow overflow-auto">
                <table className="w-full text-sm">
                    <thead className="bg-gray-100">
                        <tr>
                            <th className="p-3">No</th>
                            <th className="p-3">
                                <button onClick={() => handleSort("nama_operasional")} className="flex items-center gap-2">
                                    Jenis Operasional <ArrowUpDown size={14} />
                                </button>
                            </th>
                            <th className="p-3">Tanggal</th>
                            <th className="p-3">Qty</th>
                            <th className="p-3">Satuan</th>
                            <th className="p-3">Harga</th>
                            <th className="p-3">Total</th>
                            <th className="p-3 text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        {paginatedData.map((item, index) => (
                            <tr key={item.id} className="border-t">
                                <td className="p-3 text-center">
                                    {(currentPage - 1) * perPage + index + 1}
                                </td>
                                <td className="p-3">{item.nama_operasional}</td>
                                <td className="p-3">{item.tanggal_keluar}</td>
                                <td className="p-3">{item.qty}</td>
                                <td className="p-3">{item.satuan}</td>
                                <td className="p-3">
                                    Rp {Number(item.harga_satuan).toLocaleString("id-ID")}
                                </td>
                                <td className="p-3">
                                    Rp {(item.qty * item.harga_satuan).toLocaleString("id-ID")}
                                </td>

                                <td className="p-3 flex justify-center gap-2">
                                    <button onClick={() => handleEdit(item)} className="p-2 bg-blue-500/30 text-blue-700 rounded-md">
                                        <Pencil size={14} />
                                    </button>
                                    <button onClick={() => setDeleteId(item.id)} className="p-2 bg-red-500/30 text-red-700 rounded-md">
                                        <Trash2 size={14} />
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* MODAL FORM */}
            <AnimatePresence>
                {openForm && (
                    <Modal onClose={resetForm}>
                        <motion.div className="bg-white rounded-lg p-6 w-full max-w-md space-y-4">
                            <h2 className="text-lg font-semibold">
                                {editId ? "Edit Data" : "Tambah Data"}
                            </h2>

                            <select
                                value={form.nama_operasional}
                                onChange={(e) => setForm({ ...form, nama_operasional: e.target.value as any })}
                                className="w-full border p-2 rounded-md"
                            >
                                <option value="modal">Bebas Transportasi</option>
                                <option value="modal">Bebas Admin Bank</option>
                                <option value="modal">Bebas Gaji</option>
                                <option value="modal">Bebas Cashback</option>
                                <option value="modal">Bebas Angkut Pembelian</option>
                                <option value="modal">Bebas Kerugian Persediaan</option>
                                <option value="modal">Bebas Perlengkapan Kantor</option>
                                <option value="modal">Bebas Belanja</option>
                                <option value="modal">Bebas Lain-Lain</option>
                            </select>

                            <input
                                type="date"
                                value={form.tanggal_keluar}
                                onChange={(e) => setForm({ ...form, tanggal_keluar: e.target.value })}
                                className="w-full border p-2 rounded-md"
                            />

                            <input
                                placeholder="Qty"
                                onChange={(e) => setForm({ ...form, qty: Number(e.target.value) })}
                                className="w-full border p-2 rounded-md"
                            />

                            <input
                                placeholder="Satuan"
                                onChange={(e) => setForm({ ...form, satuan: e.target.value })}
                                className="w-full border p-2 rounded-md"
                            />

                            <input
                                placeholder="Harga"
                                onChange={(e) => setForm({ ...form, harga_satuan: Number(e.target.value) })}
                                className="w-full border p-2 rounded-md"
                            />

                            <div className="flex justify-end gap-2">
                                <button onClick={resetForm} className="px-4 py-2 bg-gray-200 rounded-md">
                                    Batal
                                </button>
                                <button onClick={handleSubmit} className="px-4 py-2 bg-blue-700 text-white rounded-md">
                                    Simpan
                                </button>
                            </div>
                        </motion.div>
                    </Modal>
                )}
            </AnimatePresence>
        </div>
    );
}

/* ================= MODAL ================= */
function Modal({ children, onClose }: any) {
    return (
        <motion.div
            className="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
            onClick={onClose}
        >
            <div onClick={(e) => e.stopPropagation()}>
                {children}
            </div>
        </motion.div>
    );
}