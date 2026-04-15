"use client";

import { useState, useMemo, useEffect } from "react";
import { Pencil, Trash2, Plus, ArrowUpDown } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { useParams, useRouter } from "next/navigation";

/* ================= TYPE ================= */
type Item = {
    id: number;
    nama_barang: string;
    qty: number;
    satuan: string;
    harga_satuan: number;
    keterangan: string;
};

type FormType = {
    nama_barang: string;
    qty: string;
    satuan: string;
    harga_satuan: string;
    keterangan: string;
};

export default function DetailPage() {
    const params = useParams();
    const router = useRouter();

    const [data, setData] = useState<Item[]>([
        {
            id: 1,
            nama_barang: "Semen",
            qty: 100,
            satuan: "Zak",
            harga_satuan: 65000,
            keterangan: "Proyek A",
        },
        {
            id: 2,
            nama_barang: "Pasir",
            qty: 5,
            satuan: "Truk",
            harga_satuan: 800000,
            keterangan: "Proyek B",
        },
    ]);

    const barangList = ["Semen", "Pasir", "Batu", "Besi"];
    const satuanList = ["KG", "Liter", "Zak"];

    const [form, setForm] = useState<FormType>({
        nama_barang: "",
        qty: "",
        satuan: "",
        harga_satuan: "",
        keterangan: "",
    });

    const [editId, setEditId] = useState<number | null>(null);
    const [openForm, setOpenForm] = useState(false);
    const [deleteId, setDeleteId] = useState<number | null>(null);

    const [search, setSearch] = useState("");
    const [sortField, setSortField] = useState<keyof Item>("nama_barang");
    const [sortOrder, setSortOrder] = useState<"asc" | "desc">("asc");

    const [currentPage, setCurrentPage] = useState(1);
    const perPage = 10;

    /* ================= HANDLE ================= */

    const handleSubmit = () => {
        if (!form.nama_barang || !form.satuan) return;

        const payload = {
            nama_barang: form.nama_barang,
            qty: Number(form.qty),
            satuan: form.satuan,
            harga_satuan: Number(form.harga_satuan),
            keterangan: form.keterangan,
        };

        if (editId) {
            setData((prev) =>
                prev.map((item) =>
                    item.id === editId ? { ...item, ...payload } : item
                )
            );
        } else {
            setData((prev) => [
                ...prev,
                { id: Date.now(), ...payload },
            ]);
        }

        resetForm();
    };

    const handleEdit = (item: Item) => {
        setForm({
            nama_barang: item.nama_barang,
            qty: String(item.qty),
            satuan: item.satuan,
            harga_satuan: String(item.harga_satuan),
            keterangan: item.keterangan,
        });
        setEditId(item.id);
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
            qty: "",
            satuan: "",
            harga_satuan: "",
            keterangan: "",
        });
        setEditId(null);
        setOpenForm(false);
    };

    const handleSort = (field: keyof Item) => {
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
                    item.keterangan.toLowerCase().includes(search.toLowerCase())
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

    return (
        <div className="p-6 space-y-6">

            {/* HEADER */}
            <div className="flex justify-between items-center">
                <h1 className="text-xl font-bold">
                    Detail Order #{params.id}
                </h1>

                <button
                    onClick={() => router.back()}
                    className="px-4 py-2 bg-white rounded-md"
                >
                    Kembali
                </button>
            </div>

            {/* SEARCH + ADD */}
            <div className="flex items-center justify-between">
                <input
                    placeholder="Cari barang..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="border p-2 rounded-md w-1/4 bg-white shadow"
                />

                <button
                    onClick={() => setOpenForm(true)}
                    className="flex items-center gap-2 bg-linear-to-t from-secondary via-primary to-secondary shadow-lg shadow-black/20 text-white px-4 py-2 rounded-lg hover:-translate-y-1 transition cursor-pointer"
                >
                    <Plus size={16} />
                    Tambah Barang
                </button>
            </div>

            {/* TABLE */}
            <div className="bg-white/70 backdrop-blur-lg rounded-lg shadow overflow-auto">
                <table className="w-full text-sm">
                    <thead className="bg-white shadow-lg">
                        <tr>
                            <th className="p-3">No</th>
                            <th className="p-3 text-left">
                                <button onClick={() => handleSort("nama_barang")} className="flex gap-2">
                                    Nama Barang <ArrowUpDown size={14} />
                                </button>
                            </th>
                            <th className="p-3 text-left">Qty</th>
                            <th className="p-3 text-left">Satuan</th>
                            <th className="p-3 text-left">Harga Penawaran</th>
                            <th className="p-3 text-left">Keterangan</th>
                            <th className="p-3 text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        {paginatedData.map((item, index) => (
                            <tr key={item.id} className="border-t border-primary/20 hover:bg-white/50">
                                <td className="p-3 text-center">
                                    {(currentPage - 1) * perPage + index + 1}
                                </td>
                                <td className="p-3">{item.nama_barang}</td>
                                <td className="p-3">{item.qty}</td>
                                <td className="p-3">{item.satuan}</td>
                                <td className="p-3">
                                    Rp {Number(item.harga_satuan).toLocaleString("id-ID")}
                                </td>
                                <td className="p-3">{item.keterangan}</td>

                                <td className="p-3 flex justify-center gap-2">
                                    <button
                                        onClick={() => handleEdit(item)}
                                        className="p-2 bg-blue-500/30 text-blue-700 rounded-md"
                                    >
                                        <Pencil size={14} />
                                    </button>

                                    <button
                                        onClick={() => setDeleteId(item.id)}
                                        className="p-2 bg-red-500/30 text-red-700 rounded-md"
                                    >
                                        <Trash2 size={14} />
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* FORM MODAL */}
            <AnimatePresence>
                {openForm && (
                    <Modal onClose={resetForm}>
                        <motion.div className="bg-white rounded-lg p-6 w-full max-w-md space-y-4">
                            <h2 className="text-lg font-semibold">
                                {editId ? "Edit Barang" : "Tambah Barang"}
                            </h2>

                            <select
                                value={form.nama_barang}
                                onChange={(e) =>
                                    setForm({ ...form, nama_barang: e.target.value })
                                }
                                className="w-full border p-2 rounded-md bg-white"
                            >
                                <option value="">Pilih Nama Barang</option>
                                {barangList.map((item) => (
                                    <option key={item} value={item}>
                                        {item}
                                    </option>
                                ))}
                            </select>

                            <input
                                type="number"
                                placeholder="Qty"
                                value={form.qty}
                                onChange={(e) =>
                                    setForm({ ...form, qty: e.target.value })
                                }
                                className="w-full border p-2 rounded-md"
                            />

                            {/* Pilih Satuan */}
                            <select
                                value={form.satuan}
                                onChange={(e) =>
                                    setForm({ ...form, satuan: e.target.value })
                                }
                                className="w-full border p-2 rounded-md bg-white"
                            >
                                <option value="">Pilih Nama Satuan</option>
                                {satuanList.map((item) => (
                                    <option key={item} value={item}>
                                        {item}
                                    </option>
                                ))}
                            </select>

                            {/* INPUT HARGA AUTO FORMAT */}
                            <input
                                type="text"
                                placeholder="Harga Satuan"
                                value={
                                    form.harga_satuan
                                        ? Number(form.harga_satuan).toLocaleString("id-ID")
                                        : ""
                                }
                                onChange={(e) => {
                                    const raw = e.target.value.replace(/\D/g, "");
                                    setForm({ ...form, harga_satuan: raw });
                                }}
                                className="w-full border p-2 rounded-md"
                            />

                            <input
                                placeholder="Keterangan"
                                value={form.keterangan}
                                onChange={(e) =>
                                    setForm({ ...form, keterangan: e.target.value })
                                }
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

            {/* DELETE MODAL */}
            <AnimatePresence>
                {deleteId && (
                    <Modal onClose={() => setDeleteId(null)}>
                        <motion.div className="bg-white rounded-lg p-6 w-full max-w-sm text-center space-y-4">
                            <h2 className="text-lg font-semibold">Hapus Data?</h2>

                            <div className="flex justify-center gap-2">
                                <button
                                    onClick={() => setDeleteId(null)}
                                    className="px-4 py-2 bg-gray-200 rounded-md"
                                >
                                    Batal
                                </button>

                                <button
                                    onClick={handleDelete}
                                    className="px-4 py-2 bg-red-600 text-white rounded-md"
                                >
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
function Modal({
    children,
    onClose,
}: {
    children: React.ReactNode;
    onClose: () => void;
}) {
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