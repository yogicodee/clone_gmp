"use client";

import { useState, useMemo, useEffect } from "react";
import { Pencil, Trash2, Plus, ArrowUpDown, Eye } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { useRouter } from "next/navigation";

/* ================= TYPE ================= */
type Order = {
  id: number;
  kode_penjualan: string;
  tanggal: string;
  total_harga: string;
  status: "draft" | "selesai" | "batal";
};

type FormType = Omit<Order, "id">;

const formatRupiah = (value: string | number) => {
  const number = Number(value || 0);
  return new Intl.NumberFormat("id-ID").format(number);
};

export default function Page() {
  const router = useRouter();

  const [data, setData] = useState<Order[]>([
    {
      id: 1,
      kode_penjualan: "TRX-001",
      tanggal: "2026-04-01",
      total_harga: "150000",
      status: "selesai",
    },
    {
      id: 2,
      kode_penjualan: "TRX-002",
      tanggal: "2026-04-02",
      total_harga: "200000",
      status: "draft",
    },
  ]);

  const [form, setForm] = useState<FormType>({
    kode_penjualan: "",
    tanggal: "",
    total_harga: "",
    status: "draft",
  });

  const [editId, setEditId] = useState<number | null>(null);
  const [openForm, setOpenForm] = useState(false);
  const [deleteId, setDeleteId] = useState<number | null>(null);

  /* ================= FILTER ================= */
  const [search, setSearch] = useState("");

  /* ================= SORT ================= */
  const [sortField, setSortField] = useState<keyof Order>("tanggal");
  const [sortOrder, setSortOrder] = useState<"asc" | "desc">("asc");

  /* ================= PAGINATION ================= */
  const [currentPage, setCurrentPage] = useState(1);
  const perPage = 10;

  /* ================= HANDLE ================= */

  const handleSubmit = () => {
    if (!form.kode_penjualan || !form.tanggal || !form.total_harga) return;

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

  const handleEdit = (item: Order) => {
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
      kode_penjualan: "",
      tanggal: "",
      total_harga: "",
      status: "draft",
    });
    setEditId(null);
    setOpenForm(false);
  };

  const handleSort = (field: keyof Order) => {
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
          item.kode_penjualan.toLowerCase().includes(search.toLowerCase()) ||
          item.status.toLowerCase().includes(search.toLowerCase())
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
        <h1 className="text-xl font-bold">List Penjualan</h1>
      </div>

      <div className="flex items-center justify-between">
        <input
          placeholder="Cari kode penjualan / status..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="border p-2 rounded-md w-1/4 bg-white shadow"
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

              <th className="p-3">
                <button onClick={() => handleSort("kode_penjualan")} className="flex gap-2">
                  Kode Penjualan <ArrowUpDown size={14} />
                </button>
              </th>

              <th className="p-3">
                <button onClick={() => handleSort("tanggal")} className="flex gap-2">
                  Tanggal <ArrowUpDown size={14} />
                </button>
              </th>

              <th className="p-3">
                <button onClick={() => handleSort("total_harga")} className="flex gap-2">
                  Total Harga <ArrowUpDown size={14} />
                </button>
              </th>

              <th className="p-3">
                <button onClick={() => handleSort("status")} className="flex gap-2">
                  Status <ArrowUpDown size={14} />
                </button>
              </th>

              <th className="p-3 text-center">Aksi</th>
            </tr>
          </thead>

          <tbody>
            {paginatedData.map((item, index) => (
              <tr key={item.id} className="border-t">
                <td className="p-3 text-center">
                  {(currentPage - 1) * perPage + index + 1}
                </td>
                <td className="p-3">{item.kode_penjualan}</td>
                <td className="p-3">{item.tanggal}</td>
                <td className="p-3">Rp {formatRupiah(item.total_harga)}</td>
                <td className="p-3 capitalize">{item.status}</td>

                <td className="p-3 flex justify-center gap-2">
                  <button
                    onClick={() => router.push(`/admin/transaksi-penjualan/penjualan/detail/${item.id}`)}
                    className="p-2 bg-green-500/30 text-green-700 rounded-md"
                  >
                    <Eye size={14} />
                  </button>

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

      {/* FORM MODAL (field disesuaikan saja) */}
      <AnimatePresence>
        {openForm && (
          <Modal onClose={resetForm}>
            <motion.div className="bg-white rounded-lg p-6 w-100 max-w-md space-y-4">
              <h2 className="text-lg font-semibold">
                {editId ? "Edit Data" : "Tambah Data"}
              </h2>

              <input
                placeholder="Kode Penjualan"
                value={form.kode_penjualan}
                onChange={(e) =>
                  setForm({ ...form, kode_penjualan: e.target.value })
                }
                className="w-full border p-2 rounded-md"
              />

              <input
                type="date"
                value={form.tanggal}
                onChange={(e) =>
                  setForm({ ...form, tanggal: e.target.value })
                }
                className="w-full border p-2 rounded-md"
              />

              <input
                placeholder="Total Harga"
                value={formatRupiah(form.total_harga)}
                onChange={(e) => {
                  const raw = e.target.value.replace(/\D/g, ""); // ambil angka saja
                  setForm({ ...form, total_harga: raw });
                }}
                className="w-full border p-2 rounded-md"
              />

              <select
                value={form.status}
                onChange={(e) =>
                  setForm({ ...form, status: e.target.value as any })
                }
                className="w-full border p-2 rounded-md"
              >
                <option value="draft">Draft</option>
                <option value="selesai">Selesai</option>
                <option value="batal">Batal</option>
              </select>

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

      {/* DELETE MODAL tetap */}
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