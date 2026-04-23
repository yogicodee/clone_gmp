"use client";

import { useState, useMemo, useEffect } from "react";
import { Pencil, Trash2, Plus, ArrowUpDown, Eye } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";

import { useFetch } from "@/hooks/useFetch";
import api from "@/lib/api";
import { useRouter } from "next/navigation";

/* ================= TYPE ================= */
type Product = {
  id: number;
  nama_akuntan: string;
  nama_sppg: string;
  tanggal: string;
  no_po: string;
  armada: string;
  no_pol: string;
  nama_driver: string;
};

type FormType = Omit<Product, "id">;

/* ================= MASTER DROPDOWN ================= */

const sppgOptions = [
  "SPPG Surabaya",
  "SPPG Sidoarjo",
  "SPPG Gresik",
];

const armadaOptions = [
  {
    armada: "Truck Box 01",
    no_pol: "L 1234 AB",
  },
  {
    armada: "Truck Box 02",
    no_pol: "W 5678 CD",
  },
  {
    armada: "Pickup 03",
    no_pol: "N 9988 EF",
  },
];

export default function Page() {
  const { data: sppgData } = useFetch<any>("/sppg");
  const { data: armadaData } = useFetch<any>("/armada");

  const router = useRouter();

  const [data, setData] = useState<Product[]>([
    {
      id: 1,
      nama_akuntan: "Tanti Dwi",
      nama_sppg: "SPPG Surabaya",
      tanggal: "2026-04-22",
      no_po: "PO-001",
      armada: "Truck Box 01",
      no_pol: "L 1234 AB",
      nama_driver: "Subandi"
    }
  ]);

  const [form, setForm] = useState<FormType>({
    nama_akuntan: "",
    nama_sppg: "",
    tanggal: "",
    no_po: "",
    armada: "",
    no_pol: "",
    nama_driver: ""
  });

  const [editId, setEditId] = useState<number | null>(null);
  const [openForm, setOpenForm] = useState(false);
  const [deleteId, setDeleteId] = useState<number | null>(null);

  /* ================= FILTER ================= */

  const [search, setSearch] = useState("");

  /* ================= SORT ================= */

  const [sortField, setSortField] =
    useState<keyof Product>("nama_akuntan");

  const [sortOrder, setSortOrder] =
    useState<"asc" | "desc">("asc");


  /* ================= PAGINATION ================= */

  const [currentPage, setCurrentPage] = useState(1);
  const perPage = 10;


  /* ================= HANDLE ================= */

  const handleSubmit = () => {

    if (
      !form.nama_akuntan ||
      !form.nama_sppg ||
      !form.tanggal ||
      !form.no_po ||
      !form.armada ||
      !form.nama_driver
    ) return;

    if (editId) {
      setData(prev =>
        prev.map(item =>
          item.id === editId
            ? { ...item, ...form }
            : item
        )
      );
    } else {
      setData(prev => [
        ...prev,
        {
          id: Date.now(),
          ...form
        }
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
      setData(prev =>
        prev.filter(item =>
          item.id !== deleteId
        )
      );
      setDeleteId(null);
    }
  };


  const resetForm = () => {
    setForm({
      nama_akuntan: "",
      nama_sppg: "",
      tanggal: "",
      no_po: "",
      armada: "",
      no_pol: "",
      nama_driver: ""
    });

    setEditId(null);
    setOpenForm(false);
  };


  const handleSort = (field: keyof Product) => {
    if (sortField === field) {
      setSortOrder(
        sortOrder === "asc"
          ? "desc"
          : "asc"
      );
    } else {
      setSortField(field);
      setSortOrder("asc");
    }
  };


  /* ================= AUTO NO POL ================= */

  useEffect(() => {
    const found = armadaOptions.find(
      item => item.armada === form.armada
    );

    if (found) {
      setForm(prev => ({
        ...prev,
        no_pol: found.no_pol
      }));
    }

  }, [form.armada]);


  /* ================= FILTER + SORT ================= */

  const filteredData = useMemo(() => {

    let result = [...data];

    if (search) {
      result = result.filter(item =>
        item.nama_akuntan
          .toLowerCase()
          .includes(search.toLowerCase()) ||

        item.no_po
          .toLowerCase()
          .includes(search.toLowerCase())
      );
    }

    result.sort((a, b) => {

      const aVal = String(a[sortField]).toLowerCase();
      const bVal = String(b[sortField]).toLowerCase();

      if (sortOrder === "asc") {
        return aVal.localeCompare(bVal);
      }

      return bVal.localeCompare(aVal);

    });

    return result;

  }, [
    data,
    search,
    sortField,
    sortOrder
  ]);


  /* ================= PAGINATION ================= */

  const totalPages = Math.ceil(
    filteredData.length / perPage
  );

  const paginatedData = filteredData.slice(
    (currentPage - 1) * perPage,
    currentPage * perPage
  );


  useEffect(() => {
    setCurrentPage(1);
  }, [search]);


  return (
    <div className="p-6 space-y-6">

      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">
          Tanda Terima
        </h1>
      </div>


      <div className="flex items-center justify-between">
        <input
          placeholder="Cari nomor surat jalan / no PO..."
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


      <div className="bg-white/70 backdrop-blur-lg rounded-lg shadow overflow-auto">

        <table className="w-full text-sm">

          <thead className="bg-white shadow-lg">
            <tr>

              <th className="p-3">No</th>
              <th className="p-3">No PO</th>

              <th className="p-3">
                <button onClick={() => handleSort("nama_akuntan")} className="flex items-center gap-2">
                  Nama Akuntan
                  <ArrowUpDown size={14} />
                </button>
              </th>
              <th className="p-3">SPPG</th>
              <th className="p-3">Tanggal</th>
              <th className="p-3">Armada</th>
              <th className="p-3">No Pol</th>
              <th className="p-3">Driver</th>

              <th className="p-3 text-center">
                Aksi
              </th>

            </tr>
          </thead>

          <tbody>

            {paginatedData.map((item, index) => (

              <tr
                key={item.id}
                className="border-t border-primary/20 hover:bg-white/50"
              >

                <td className="p-3 text-center">
                  {(currentPage - 1) * perPage + index + 1}
                </td>

                <td className="p-3">{item.no_po}</td>
                <td className="p-3">{item.nama_akuntan}</td>
                <td className="p-3">{item.nama_sppg}</td>
                <td className="p-3">{item.tanggal}</td>
                <td className="p-3">{item.armada}</td>
                <td className="p-3">{item.no_pol}</td>
                <td className="p-3">{item.nama_driver}</td>

                <td className="p-3 flex justify-center gap-2">
                  <button
                    onClick={() =>
                      router.push(
                        `/admin/transaksi-penjualan/tanda-terima/detail/${item.id}`
                      )
                    }
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


      <AnimatePresence>
        {openForm && (
          <Modal onClose={resetForm}>

            <motion.div className="bg-white rounded-lg p-6 w-full max-w-md space-y-4">

              <h2 className="text-lg font-semibold">
                {editId ? "Edit Data" : "Tambah Data"}
              </h2>

              <input
                placeholder="Nomor Surat Jalan"
                value={form.nama_akuntan}
                onChange={(e) =>
                  setForm({
                    ...form,
                    nama_akuntan: e.target.value
                  })
                }
                className="w-full border p-2 rounded-md"
              />


              {/* Selesct SPPG */}
              <select
                value={form.nama_sppg}
                onChange={(e) => setForm({ ...form, nama_sppg: e.target.value })}
                className="w-full border p-2 rounded-md"
              >
                <option value="">Pilih SPPG</option>
                {sppgData.map((item, i) => (
                  <option key={i} value={item.nama_sppg}>
                    {item.nama_sppg}
                  </option>
                ))}
              </select>


              <input
                type="date"
                value={form.tanggal}
                onChange={(e) =>
                  setForm({
                    ...form,
                    tanggal: e.target.value
                  })
                }
                className="w-full border p-2 rounded-md"
              />


              <input
                placeholder="No PO"
                value={form.no_po}
                onChange={(e) =>
                  setForm({
                    ...form,
                    no_po: e.target.value
                  })
                }
                className="w-full border p-2 rounded-md"
              />


              {/* Select Armada */}
              <select
                value={form.armada}
                onChange={(e) => {
                  const selectedArmada = e.target.value;

                  const selectedData = armadaData.find(
                    item => item.nama_unit === selectedArmada
                  );

                  setForm({
                    ...form,
                    armada: selectedArmada,
                    no_pol: selectedData?.no_pol || ""
                  });
                }}
                className="w-full border p-2 rounded-md"
              >
                <option value="">Pilih Armada</option>

                {armadaData.map((item, i) => (
                  <option
                    key={i}
                    value={item.nama_unit}
                  >
                    {item.nama_unit}
                  </option>
                ))}
              </select>


              <input
                value={form.no_pol}
                readOnly
                placeholder="No Polisi Auto"
                className="w-full border p-2 rounded-md bg-gray-100"
              />


              <input
                placeholder="Nama Driver"
                value={form.nama_driver}
                onChange={(e) =>
                  setForm({
                    ...form,
                    nama_driver: e.target.value
                  })
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
  )
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
      <div onClick={(e) => e.stopPropagation()}>
        {children}
      </div>
    </motion.div>
  )

}