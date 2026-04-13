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
    satuan: string;
    stok: number;
    kebutuhan: number;
    nama_supplier: string;
};

type FormType = Omit<Item, "id">;

export default function Page() {
    const [data, setData] = useState<Item[]>([
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
            nama_supplier: "CV Makmur Jaya",
        },
    ]);

    const [form, setForm] = useState<FormType>({
        nama_barang: "",
        qty: 0,
        satuan: "",
        stok: 0,
        kebutuhan: 0,
        nama_supplier: "",
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

    const handleEdit = (item: Item) => {
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
            nama_barang: "",
            qty: 0,
            satuan: "",
            stok: 0,
            kebutuhan: 0,
            nama_supplier: "",
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

    return (
        <div className="p-6 space-y-6">
            {/* HEADER */}
            <div className="flex justify-between items-center">
                <h1 className="text-xl font-bold">
                    Detail Order #{params.id}
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
                    placeholder="Cari barang / supplier..."
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
                            <th className="p-3 text-left">Satuan</th>
                            <th className="p-3 text-left">Stok</th>
                            <th className="p-3 text-left">Kebutuhan</th>
                            <th className="p-3 text-left">Supplier</th>
                            <th className="p-3 text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        {paginatedData.map((item, index) => (
                            <tr key={item.id} className="border-t">
                                <td className="p-3 text-center">{index + 1}</td>
                                <td className="p-3">{item.nama_barang}</td>
                                <td className="p-3">{item.qty}</td>
                                <td className="p-3">{item.satuan}</td>
                                <td className="p-3">{item.stok}</td>
                                <td className="p-3">{item.kebutuhan}</td>
                                <td className="p-3">{item.nama_supplier}</td>

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

            {/* FORM */}
            <AnimatePresence>
                {openForm && (
                    <Modal onClose={resetForm}>
                        <div className="bg-white p-6 rounded-lg space-y-3">
                            <input placeholder="Nama Barang" value={form.nama_barang}
                                onChange={(e) => setForm({ ...form, nama_barang: e.target.value })} />

                            <input type="number" placeholder="Qty"
                                onChange={(e) => setForm({ ...form, qty: Number(e.target.value) })} />

                            <input placeholder="Satuan"
                                onChange={(e) => setForm({ ...form, satuan: e.target.value })} />

                            <input type="number" placeholder="Stok"
                                onChange={(e) => setForm({ ...form, stok: Number(e.target.value) })} />

                            <input type="number" placeholder="Kebutuhan"
                                onChange={(e) => setForm({ ...form, kebutuhan: Number(e.target.value) })} />

                            <input placeholder="Supplier"
                                onChange={(e) => setForm({ ...form, nama_supplier: e.target.value })} />

                            <button onClick={handleSubmit}>Simpan</button>
                        </div>
                    </Modal>
                )}
            </AnimatePresence>
        </div>
    );
}

/* MODAL */
function Modal({ children, onClose }: any) {
    return (
        <div className="fixed inset-0 bg-black/40 flex justify-center items-center" onClick={onClose}>
            <div onClick={(e) => e.stopPropagation()}>{children}</div>
        </div>
    );
}