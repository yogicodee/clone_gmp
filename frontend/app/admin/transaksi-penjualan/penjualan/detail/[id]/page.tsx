"use client";

import { useState, useMemo, useEffect } from "react";
import { Pencil, Trash2, Plus, ArrowUpDown } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { useRouter, useParams } from "next/navigation";

/* ================= TYPE ================= */
type Item = {
    id: number;
    nama_barang: string;
    qty: number;
    harga_satuan: number;
    total: number;
};

type FormType = Omit<Item, "id" | "total">;

export default function Page() {
    const [data, setData] = useState<Item[]>([
        {
            id: 1,
            nama_barang: "Beras",
            qty: 10,
            harga_satuan: 12000,
            total: 120000,
        },
        {
            id: 2,
            nama_barang: "Minyak Goreng",
            qty: 5,
            harga_satuan: 15000,
            total: 75000,
        },
    ]);

    const [form, setForm] = useState<FormType>({
        nama_barang: "",
        qty: 0,
        harga_satuan: 0,
    });

    const [editId, setEditId] = useState<number | null>(null);
    const [openForm, setOpenForm] = useState(false);
    const [deleteId, setDeleteId] = useState<number | null>(null);
    const router = useRouter();
    const params = useParams();

    /* ================= FILTER ================= */
    const [search, setSearch] = useState("");

    /* ================= SORT ================= */
    const [sortField, setSortField] = useState<keyof Item>("nama_barang");
    const [sortOrder, setSortOrder] = useState<"asc" | "desc">("asc");

    /* ================= PAGINATION ================= */
    const [currentPage, setCurrentPage] = useState(1);
    const perPage = 10;

    /* ================= HANDLE ================= */

    const handleSubmit = () => {
        if (!form.nama_barang) return;

        const newData = {
            ...form,
            total: form.qty * form.harga_satuan,
        };

        if (editId) {
            setData((prev) =>
                prev.map((item) =>
                    item.id === editId ? { ...item, ...newData } : item
                )
            );
        } else {
            setData((prev) => [
                ...prev,
                { id: Date.now(), ...newData },
            ]);
        }

        resetForm();
    };

    const handleEdit = (item: Item) => {
        const { id, total, ...rest } = item;
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
            nama_barang: "",
            qty: 0,
            harga_satuan: 0,
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
            result = result.filter((item) =>
                item.nama_barang.toLowerCase().includes(search.toLowerCase())
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
                    Detail Penjualan #{params.id}
                </h1>

                <button
                    onClick={() => router.back()}
                    className="px-4 py-2 bg-gray-200 rounded-md"
                >
                    Kembali
                </button>
            </div>

            <div className="flex justify-between">
                <input
                    placeholder="Cari barang..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="border p-2 rounded-md w-1/4"
                />

                <button
                    onClick={() => setOpenForm(true)}
                    className="flex items-center gap-2 bg-linear-to-t from-secondary via-primary to-secondary shadow-lg shadow-black/20 text-white px-4 py-2 rounded-lg hover:-translate-y-1 transition cursor-pointer"
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
                            <th className="p-3 text-left" onClick={() => handleSort("nama_barang")}>Barang</th>
                            <th className="p-3 text-left">Qty</th>
                            <th className="p-3 text-left" onClick={() => handleSort("harga_satuan")}>Harga Satuan</th>
                            <th className="p-3 text-left" onClick={() => handleSort("total")}>Total</th>
                            <th className="p-3 text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        {paginatedData.map((item, index) => (
                            <tr key={item.id} className="border-t">
                                <td className="p-3 text-center">{index + 1}</td>
                                <td className="p-3">{item.nama_barang}</td>
                                <td className="p-3">{item.qty}</td>
                                <td className="p-3">{item.harga_satuan}</td>
                                <td className="p-3">{item.total}</td>

                                <td className="p-3 flex justify-center gap-2">
                                    <button onClick={() => handleEdit(item)} className="p-2 bg-blue-500/30 rounded">
                                        <Pencil size={14} />
                                    </button>

                                    <button onClick={() => setDeleteId(item.id)} className="p-2 bg-red-500/30 rounded">
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
                    disabled={currentPage === totalPages}
                    onClick={() => setCurrentPage((p) => p + 1)}
                    className="px-3 py-1 border rounded-md"
                >
                    Next
                </button>
            </div>

            {/* FORM (TIDAK DIUBAH STYLE) */}
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
                                onChange={(e) =>
                                    setForm({ ...form, nama_barang: e.target.value })
                                }
                                className="w-full border p-2 rounded-md"
                            />

                            <input
                                type="number"
                                placeholder="Qty"
                                value={form.qty}
                                onChange={(e) =>
                                    setForm({ ...form, qty: Number(e.target.value) })
                                }
                                className="w-full border p-2 rounded-md"
                            />

                            <input
                                type="number"
                                placeholder="Harga Satuan"
                                value={form.harga_satuan}
                                onChange={(e) =>
                                    setForm({ ...form, harga_satuan: Number(e.target.value) })
                                }
                                className="w-full border p-2 rounded-md"
                            />

                            <div className="flex justify-end gap-2">
                                <button
                                    onClick={resetForm}
                                    className="px-4 py-2 bg-gray-200 rounded-md"
                                >
                                    Batal
                                </button>

                                <button
                                    onClick={handleSubmit}
                                    className="px-4 py-2 bg-blue-700 text-white rounded-md"
                                >
                                    Simpan
                                </button>
                            </div>
                        </motion.div>
                    </Modal>
                )}
            </AnimatePresence>

            {/* MODAL DELETE tetap */}
            <AnimatePresence>
                {deleteId && (
                    <Modal onClose={() => setDeleteId(null)}>
                        <motion.div className="bg-white rounded-lg p-6 w-full max-w-sm text-center space-y-4">
                            <h2 className="text-lg font-semibold">
                                Hapus Data?
                            </h2>

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

/* MODAL tetap */
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
            <div onClick={(e) => e.stopPropagation()}>{children}</div>
        </motion.div>
    );
}