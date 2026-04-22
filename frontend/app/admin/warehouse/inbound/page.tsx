"use client";

import { useState, useMemo, useEffect } from "react";
import { Pencil, Trash2, Plus } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { useFetch } from "@/hooks/useFetch";
import api from "@/lib/api";

/* ================= TYPE ================= */
type Product = {
    id: number;
    nama_barang: string;
    kategori: string;
    tanggal_masuk: string;
    qty: number;
    satuan: string;
    harga_satuan: number;
    total_harga: number;
    nama_supplier: string;
};

type FormType = {
    gudang_id: number;
    nama_barang: string;
    nama_gudang: string;
    kategori: string;
    tanggal_masuk: string;
    qty: number;
    satuan: string;
    harga_satuan: number;
    nama_supplier: string;
};

export default function Page() {
    const { data, loading, refetch } = useFetch<Product>("/inbound");
    const { data: satuanData } = useFetch<any>("/kategori");
    const { data: supplierData } = useFetch<any>("/supplier");
    const { data: gudangData } = useFetch<any>("/gudang");

    const [form, setForm] = useState<FormType>({
        gudang_id: 1,
        nama_barang: "",
        nama_gudang: "",
        kategori: "",
        tanggal_masuk: "",
        qty: 0,
        satuan: "",
        harga_satuan: 0,
        nama_supplier: "",
    });

    const [hargaInput, setHargaInput] = useState("");
    const [qtyInput, setQtyInput] = useState("");

    const [editId, setEditId] = useState<number | null>(null);
    const [openForm, setOpenForm] = useState(false);
    const [deleteId, setDeleteId] = useState<number | null>(null);

    const [search, setSearch] = useState("");
    const [sortField, setSortField] = useState<keyof Product>("nama_barang");
    const [sortOrder, setSortOrder] = useState<"asc" | "desc">("asc");

    const [currentPage, setCurrentPage] = useState(1);
    const perPage = 10;

    const formatRupiah = (value: number | string) => {
        const number = Number(value) || 0;
        return number.toLocaleString("id-ID");
    };

    /* ================= HANDLE ================= */
    const handleSubmit = async () => {
        if (
            !form.nama_barang ||
            !form.kategori ||
            !form.tanggal_masuk ||
            form.qty <= 0 ||
            !form.satuan ||
            !form.nama_gudang ||
            form.harga_satuan <= 0 ||
            !form.nama_supplier
        ) {
            alert("Semua field wajib diisi dengan benar");
            return;
        }

        const payload = {
            ...form,
            kategori: form.kategori.toLowerCase(), // ✅ fix
            total_harga: form.qty * form.harga_satuan,
        };

        try {
            if (editId) {
                await api.put(`/inbound/${editId}`, payload);
            } else {
                await api.post("/inbound", payload);
            }

            await refetch();
            resetForm();
        } catch (error: any) {
            console.log("ERROR 422 =>", error.response?.data);
        }
    };

    const handleEdit = (item: Product) => {
        const { id, total_harga, ...rest } = item;
        setForm({ ...rest, gudang_id: 1 });
        setEditId(id);
        setOpenForm(true);
    };

    const handleDelete = async () => {
        if (!deleteId) return;

        try {
            await api.delete(`/inbound/${deleteId}`);
            await refetch();
            setDeleteId(null);
        } catch (error: any) {
            console.log("ERROR 422 =>", error.response?.data);
        }
    };

    const resetForm = () => {
        setForm({
            gudang_id: 1,
            nama_barang: "",
            kategori: "",
            tanggal_masuk: "",
            qty: 0,
            satuan: "",
            harga_satuan: 0,
            nama_supplier: "",
        });
        setHargaInput("");
        setQtyInput("");
        setEditId(null);
        setOpenForm(false);
    };

    /* ================= FILTER ================= */
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
                <h1 className="text-3xl font-bold">Data Barang Masuk</h1>
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
            <div className="bg-white/70 backdrop-blur-lg rounded-lg shadow overflow-auto">
                <table className="w-full text-sm">
                    <thead className="bg-white shadow-lg">
                        <tr>
                            <th className="p-3">No</th>
                            <th className="p-3 text-left">Nama Barang</th>
                            <th className="p-3 text-left">Kategori</th>
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
                            <tr key={item.id} className="border-t border-primary/20 hover:bg-white/50">
                                <td className="p-3 text-center">
                                    {(currentPage - 1) * perPage + index + 1}
                                </td>
                                <td className="p-3">{item.nama_barang}</td>
                                <td className="p-3">{item.kategori}</td>
                                <td className="p-3">{item.tanggal_masuk}</td>
                                <td className="p-3">{item.qty}</td>
                                <td className="p-3">{item.satuan}</td>
                                <td className="p-3">Rp {formatRupiah(item.harga_satuan)}</td>
                                <td className="p-3">Rp {formatRupiah(item.total_harga)}</td>
                                <td className="p-3">{item.nama_supplier}</td>

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

            {/* PAGINATION */}
            <div className="flex justify-end gap-2">
                <button
                    disabled={currentPage === 1}
                    onClick={() => setCurrentPage((p) => p - 1)}
                    className="px-3 py-1 border border-white rounded-md"
                >
                    Prev
                </button>

                {Array.from({ length: totalPages }, (_, i) => (
                    <button
                        key={i}
                        onClick={() => setCurrentPage(i + 1)}
                        className={`px-3 py-1 border border-white rounded-md ${currentPage === i + 1 ? "bg-primary text-white" : ""
                            }`}
                    >
                        {i + 1}
                    </button>
                ))}

                <button
                    disabled={currentPage === totalPages || totalPages === 0}
                    onClick={() => setCurrentPage((p) => p + 1)}
                    className="px-3 py-1 border border-white rounded-md"
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

                            {/* NAMA BARANG */}
                            <select
                                value={form.nama_barang}
                                onChange={(e) => setForm({ ...form, nama_barang: e.target.value })}
                                className="w-full border p-2 rounded-md"
                            >
                                <option value="">Pilih Barang</option>
                                <option value="Beras">Beras</option>
                                <option value="Minyak">Minyak</option>
                                <option value="Gula">Gula</option>
                            </select>

                            {/* KATEGORI */}
                            <select
                                value={form.kategori}
                                onChange={(e) => setForm({ ...form, kategori: e.target.value })}
                                className="w-full border p-2 rounded-md"
                            >
                                <option value="">Pilih Kategori</option>
                                <option value="basah">Basah</option>
                                <option value="kering">Kering</option>
                            </select>

                            {/* TANGGAL */}
                            <input
                                type="date"
                                value={form.tanggal_masuk}
                                onChange={(e) => setForm({ ...form, tanggal_masuk: e.target.value })}
                                className="w-full border p-2 rounded-md"
                            />

                            {/* QTY */}
                            <input
                                placeholder="Qty"
                                value={qtyInput}
                                onChange={(e) => {
                                    const val = e.target.value.replace(/\D/g, "");
                                    setQtyInput(val);
                                    setForm({ ...form, qty: Number(val) });
                                }}
                                className="w-full border p-2 rounded-md"
                            />

                            {/* SATUAN */}
                            <select
                                value={form.satuan}
                                onChange={(e) => setForm({ ...form, satuan: e.target.value })}
                                className="w-full border p-2 rounded-md"
                            >
                                <option value="">Pilih Satuan</option>
                                {satuanData.map((item: any) => (
                                    <option key={item.id} value={item.nama_satuan}>
                                        {item.nama_satuan}
                                    </option>
                                ))}
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

                            {/* Gudang */}
                            <select
                                value={form.nama_gudang}
                                onChange={(e) => setForm({ ...form, nama_gudang: e.target.value })}
                                className="w-full border p-2 rounded-md"
                            >
                                <option value="">Pilih Supplier</option>
                                {supplierData.map((item: any) => (
                                    <option key={item.id} value={item.nama_gudang}>
                                        {item.nama_gudang}
                                    </option>
                                ))}
                            </select>

                            {/* SUPPLIER */}
                            <select
                                value={form.nama_supplier}
                                onChange={(e) => setForm({ ...form, nama_supplier: e.target.value })}
                                className="w-full border p-2 rounded-md"
                            >
                                <option value="">Pilih Supplier</option>
                                {supplierData.map((item: any) => (
                                    <option key={item.id} value={item.nama}>
                                        {item.nama}
                                    </option>
                                ))}
                            </select>

                            {/* BUTTON */}
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