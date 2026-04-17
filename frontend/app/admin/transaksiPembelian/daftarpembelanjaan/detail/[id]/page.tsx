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
            kebutuhan: 5,
            nama_supplier: "PT Sumber Pangan",
        },
        {
            id: 2,
            nama_barang: "Minyak Goreng",
            qty: 20,
            satuan: "Liter",
            stok: 10,
            kebutuhan: 10,
            nama_supplier: "CV Makmur Jaya",
        },
    ]);

    const barangList = ["Beras", "Minyak Goreng", "Gula", "Telur"];
    const satuanList = ["Kg", "Liter", "Pcs", "Dus"];
    const supplierList = ["PT Sumber Pangan", "CV Makmur Jaya", "PT Sejahtera", "UD Berkah"];

    const [form, setForm] = useState({
        nama_barang: "",
        qty: "",
        satuan: "",
        stok: "",
        kebutuhan: "",
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

        const qty = Number(form.qty);
        const stok = Number(form.stok);
        const kebutuhan = Math.max(qty - stok, 0);

        const payload = {
            ...form,
            qty,
            stok,
            kebutuhan,
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


    const isEdit = !!editId;

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

            {/* TABLE */}
            <div className="bg-white/70 backdrop-blur-lg rounded-lg shadow overflow-auto">
                <table className="w-full text-sm">
                    <thead className="bg-white shadow-lg">
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
                            <tr key={item.id} className="border-t border-primary/20 hover:bg-white/50">
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

            {/* FORM */}
            <AnimatePresence>
                {openForm && (
                    <Modal onClose={resetForm}>
                        <motion.div className="bg-white rounded-lg p-6 w-100 max-w-md space-y-4">

                            {/* NAMA BARANG */}
                            <select
                                disabled={isEdit}
                                value={form.nama_barang}
                                onChange={(e) => setForm({ ...form, nama_barang: e.target.value })}
                                className="w-full border p-2 rounded-md bg-white"
                            >
                                <option value="">Pilih Nama Barang</option>
                                {barangList.map((item) => (
                                    <option key={item} value={item}>
                                        {item}
                                    </option>
                                ))}
                            </select>

                            {/* QTY */}
                            <input
                                disabled={isEdit}
                                type="text"
                                placeholder="Qty"
                                value={
                                    form.qty
                                        ? Number(form.qty).toLocaleString("id-ID")
                                        : ""
                                }
                                onChange={(e) => {
                                    const raw = e.target.value.replace(/\D/g, "");
                                    setForm({ ...form, qty: raw });
                                }}
                                className="w-full border p-2 rounded-md"
                            />

                            {/* SATUAN */}
                            <select
                                disabled={isEdit}
                                value={form.satuan}
                                onChange={(e) => setForm({ ...form, satuan: e.target.value })}
                                className="w-full border p-2 rounded-md bg-white"
                            >
                                <option value="">Pilih Satuan</option>
                                {satuanList.map((item) => (
                                    <option key={item} value={item}>
                                        {item}
                                    </option>
                                ))}
                            </select>

                            {/* STOK */}
                            <input
                                disabled={isEdit}
                                type="text"
                                placeholder="Stok"
                                value={
                                    form.stok
                                        ? Number(form.stok).toLocaleString("id-ID")
                                        : ""
                                }
                                onChange={(e) => {
                                    const raw = e.target.value.replace(/\D/g, "");
                                    setForm({ ...form, stok: raw });
                                }}
                                className="w-full border p-2 rounded-md"
                            />


                            {/* SUPPLIER */}
                            <select
                                value={form.nama_supplier}
                                onChange={(e) => setForm({ ...form, nama_supplier: e.target.value })}
                                className="w-full border p-2 rounded-md bg-white"
                            >
                                <option value="">Pilih Supplier</option>
                                {supplierList.map((item) => (
                                    <option key={item} value={item}>
                                        {item}
                                    </option>
                                ))}
                            </select>

                            {/* ACTION */}
                            <div className="flex justify-end gap-2 pt-2">
                                <button
                                    onClick={resetForm}
                                    className="px-3 py-1 bg-gray-200 rounded-md"
                                >
                                    Batal
                                </button>

                                <button
                                    onClick={handleSubmit}
                                    className="px-3 py-1 bg-blue-600 text-white rounded-md"
                                >
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

/* MODAL */
function Modal({ children, onClose }: any) {
    return (
        <div className="fixed inset-0 bg-black/40 flex justify-center items-center" onClick={onClose}>
            <div onClick={(e) => e.stopPropagation()}>{children}</div>
        </div>
    );
}