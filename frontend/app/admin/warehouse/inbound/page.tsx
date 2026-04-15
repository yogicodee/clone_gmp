"use client";

import { useState, useMemo, useEffect } from "react";
import { Pencil, Trash2, Plus, ArrowUpDown } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";

/* ================= TYPE ================= */
type Product = {
    id: number;
    nama_barang: string;
    tanggal_masuk: string;
    qty: number;
    satuan: string;
    harga_satuan: number;
    total_harga: number;
    nama_supplier: string;
};

type FormType = Omit<Product, "id" | "total_harga">;

export default function Page() {
    const [data, setData] = useState<Product[]>([
        {
            id: 1,
            nama_barang: "Beras",
            tanggal_masuk: "2026-04-01",
            qty: 10,
            satuan: "Kg",
            harga_satuan: 12000,
            total_harga: 120000,
            nama_supplier: "PT Sumber Pangan",
        },
    ]);

    const [form, setForm] = useState<FormType>({
        nama_barang: "",
        tanggal_masuk: "",
        qty: 0,
        satuan: "",
        harga_satuan: 0,
        nama_supplier: "",
    });

    const [hargaInput, setHargaInput] = useState("");
    const [editId, setEditId] = useState<number | null>(null);
    const [openForm, setOpenForm] = useState(false);
    const [deleteId, setDeleteId] = useState<number | null>(null);

    const [search, setSearch] = useState("");
    const [sortField, setSortField] = useState<keyof Product>("nama_barang");
    const [sortOrder, setSortOrder] = useState<"asc" | "desc">("asc");

    const [currentPage, setCurrentPage] = useState(1);
    const perPage = 10;

    /* ================= HELPER ================= */
    const formatRupiah = (value: number | string) => {
        const number = Number(value) || 0;
        return number.toLocaleString("id-ID");
    };

    /* ================= HANDLE ================= */
    const handleSubmit = () => {
        if (!form.nama_barang) return;

        const total = form.qty * form.harga_satuan;

        if (editId) {
            setData((prev) =>
                prev.map((item) =>
                    item.id === editId
                        ? { ...item, ...form, total_harga: total }
                        : item
                )
            );
        } else {
            setData((prev) => [
                ...prev,
                {
                    id: Date.now(),
                    ...form,
                    total_harga: total,
                },
            ]);
        }

        resetForm();
    };

    const handleEdit = (item: Product) => {
        const { id, total_harga, ...rest } = item;
        setForm(rest);
        setHargaInput(formatRupiah(rest.harga_satuan));
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
            nama_barang: "",
            tanggal_masuk: "",
            qty: 0,
            satuan: "",
            harga_satuan: 0,
            nama_supplier: "",
        });
        setHargaInput("");
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

    /* ================= FILTER + SORT ================= */
    const filteredData = useMemo(() => {
        let result = [...data];

        if (search) {
            result = result.filter(
                (item) =>
                    item.nama_barang.toLowerCase().includes(search.toLowerCase()) ||
                    item.nama_supplier.toLowerCase().includes(search.toLowerCase())
            );
        }

        result.sort((a, b) => {
            const aVal = String(a[sortField]).toLowerCase();
            const bVal = String(b[sortField]).toLowerCase();

            if (sortOrder === "asc") return aVal.localeCompare(bVal);
            return bVal.localeCompare(aVal);
        });

        return result;
    }, [data, search, sortField, sortOrder]);

    /* ================= PAGINATION ================= */
    const totalPages = Math.ceil(filteredData.length / perPage);

    const paginatedData = filteredData.slice(
        (currentPage - 1) * perPage,
        currentPage * perPage
    );

    useEffect(() => {
        setCurrentPage(1);
    }, [search]);

    useEffect(() => {
        if (currentPage > totalPages) {
            setCurrentPage(1);
        }
    }, [filteredData]);

    return (
        <div className="p-6 space-y-6">
            <div className="flex justify-between items-center">
                <h1 className="text-xl font-bold">Data Barang Masuk</h1>
            </div>

            <div className="flex items-center justify-between">
                <input
                    placeholder="Cari barang / supplier..."
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
                                <button onClick={() => handleSort("nama_barang")} className="flex items-center gap-2">
                                    Nama Barang <ArrowUpDown size={14} />
                                </button>
                            </th>
                            <th className="p-3 text-left">Tanggal</th>
                            <th className="p-3 text-left">Qty</th>
                            <th className="p-3 text-left">Satuan</th>
                            <th className="p-3 text-left">Harga</th>
                            <th className="p-3 text-left">Total</th>
                            <th className="p-3 text-left">Supplier</th>
                            <th className="p-3 text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        {paginatedData.map((item, index) => (
                            <tr key={item.id} className="border-t">
                                <td className="p-3 text-center">
                                    {(currentPage - 1) * perPage + index + 1}
                                </td>
                                <td className="p-3">{item.nama_barang}</td>
                                <td className="p-3">{item.tanggal_masuk}</td>
                                <td className="p-3">{item.qty}</td>
                                <td className="p-3">{item.satuan}</td>
                                <td className="p-3">Rp {formatRupiah(item.harga_satuan)}</td>
                                <td className="p-3">Rp {formatRupiah(item.total_harga)}</td>
                                <td className="p-3">{item.nama_supplier}</td>

                                <td className="p-3 flex justify-center gap-2">
                                    <button onClick={() => handleEdit(item)} className="p-2 bg-blue-500/30 rounded-md">
                                        <Pencil size={14} />
                                    </button>
                                    <button onClick={() => setDeleteId(item.id)} className="p-2 bg-red-500/30 rounded-md">
                                        <Trash2 size={14} />
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* PAGINATION */}
            <div className="flex justify-end gap-2">
                <button
                    disabled={currentPage === 1}
                    onClick={() => setCurrentPage((p) => p - 1)}
                    className="px-3 py-1 border rounded-md"
                >
                    Prev
                </button>

                {Array.from({ length: totalPages }, (_, i) => (
                    <button
                        key={i}
                        onClick={() => setCurrentPage(i + 1)}
                        className={`px-3 py-1 border rounded-md ${currentPage === i + 1 ? "bg-primary text-white" : ""
                            }`}
                    >
                        {i + 1}
                    </button>
                ))}

                <button
                    disabled={currentPage === totalPages || totalPages === 0}
                    onClick={() => setCurrentPage((p) => p + 1)}
                    className="px-3 py-1 border rounded-md"
                >
                    Next
                </button>
            </div>

            {/* FORM */}
            <AnimatePresence>
                {openForm && (
                    <Modal onClose={resetForm}>
                        <motion.div className="bg-white rounded-lg p-6 w-full max-w-md space-y-4">
                            <h2 className="text-lg font-semibold">
                                {editId ? "Edit Data" : "Tambah Data"}
                            </h2>

                            <input
                                placeholder="Nama Barang"
                                value={form.nama_barang}
                                onChange={(e) => setForm({ ...form, nama_barang: e.target.value })}
                                className="w-full border p-2 rounded-md"
                            />

                            <input
                                type="date"
                                value={form.tanggal_masuk}
                                onChange={(e) => setForm({ ...form, tanggal_masuk: e.target.value })}
                                className="w-full border p-2 rounded-md"
                            />

                            <input
                                type="number"
                                placeholder="Qty"
                                value={form.qty}
                                onChange={(e) => setForm({ ...form, qty: Number(e.target.value) })}
                                className="w-full border p-2 rounded-md"
                            />

                            {/* SATUAN */}
                            <select
                                value={form.satuan}
                                onChange={(e) => setForm({ ...form, satuan: e.target.value })}
                                className="w-full border p-2 rounded-md"
                            >
                                <option value="">Pilih Satuan</option>
                                <option value="Kg">Kg</option>
                                <option value="Gram">Gram</option>
                                <option value="Liter">Liter</option>
                                <option value="Pcs">Pcs</option>
                                <option value="Box">Box</option>
                            </select>

                            {/* HARGA */}
                            <input
                                placeholder="Harga Satuan"
                                value={hargaInput}
                                onChange={(e) => {
                                    const raw = e.target.value.replace(/\D/g, "");
                                    setHargaInput(formatRupiah(raw));
                                    setForm({ ...form, harga_satuan: Number(raw) });
                                }}
                                className="w-full border p-2 rounded-md"
                            />

                            {/* SUPPLIER */}
                            <select
                                value={form.nama_supplier}
                                onChange={(e) => setForm({ ...form, nama_supplier: e.target.value })}
                                className="w-full border p-2 rounded-md"
                            >
                                <option value="">Pilih Supplier</option>
                                <option value="PT Sumber Pangan">PT Sumber Pangan</option>
                                <option value="PT Makmur Jaya">PT Makmur Jaya</option>
                                <option value="CV Sejahtera">CV Sejahtera</option>
                            </select>

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

            {/* DELETE */}
            <AnimatePresence>
                {deleteId && (
                    <Modal onClose={() => setDeleteId(null)}>
                        <motion.div className="bg-white rounded-lg p-6 w-full max-w-sm text-center space-y-4">
                            <h2 className="text-lg font-semibold">Hapus Data?</h2>
                            <div className="flex justify-center gap-2">
                                <button onClick={() => setDeleteId(null)} className="px-4 py-2 bg-gray-200 rounded-md">
                                    Batal
                                </button>
                                <button onClick={handleDelete} className="px-4 py-2 bg-red-600 text-white rounded-md">
                                    Hapus
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