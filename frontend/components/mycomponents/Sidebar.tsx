"use client";

import { useState } from "react";
import {
  LayoutDashboard,
  Settings,
  ChevronDown,
  Users,
  MapPin,
  Truck,
  Building2,
  Boxes,
  Warehouse,
  Car,
  ShieldCheck,
  Banknote,
  ShoppingCart,
  ClipboardList,
  PackageCheck,
  ArrowDownUp,
  ScanLine,
  PackageSearch,
  LayoutGrid,
  BarChart3,
  FileText,
  TrendingUp,
  Landmark,
  BaggageClaim,
} from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";

/* ================= MENU CONFIG ================= */
const menus = [
  {
    label: "Master",
    icon: <Landmark />,
    key: "master",
    children: [
      { icon: <MapPin size={16} />, label: "Wilayah & Lokasi" },
      { icon: <Truck size={16} />, label: "Supplier" },
      { icon: <Users size={16} />, label: "Mitra" },
      { icon: <Building2 size={16} />, label: "SPPG" },
      { icon: <Boxes size={16} />, label: "Produk & Satuan" },
      { icon: <Warehouse size={16} />, label: "Gudang" },
      { icon: <Car size={16} />, label: "Armada" },
      { icon: <Users size={16} />, label: "Karyawan" },
      { icon: <ShieldCheck size={16} />, label: "Level User & Hak Akses" },
      { icon: <Banknote size={16} />, label: "Bank & Rekening" },
    ],
  },

  {
    label: "Transaksi Pembelian",
    icon: <BaggageClaim />, // pembelian
    key: "pembelian",
    children: [
      { icon: <ClipboardList size={16} />, label: "Pengajuan Purchase Order" },
      { icon: <PackageCheck size={16} />, label: "Validasi Harga" },
      { icon: <Banknote size={16} />, label: "Invoice Pembayaran" },
      { icon: <Truck size={16} />, label: "Riwayat Kedatangan Barang" },
    ],
  },

  {
    label: "Warehouse System",
    icon: <Warehouse />, // warehouse = gudang
    key: "warehouse",
    children: [
      { icon: <ScanLine size={16} />, label: "Cek Stok & Opname" },
      { icon: <ArrowDownUp size={16} />, label: "Inbound" },
      { icon: <PackageCheck size={16} />, label: "Picking & Packing" },
      { icon: <LayoutGrid size={16} />, label: "Penataan Slot" },
      { icon: <PackageSearch size={16} />, label: "Inventory Adjustment" },
    ],
  },

  {
    label: "Transaksi Penjualan",
    icon: <ShoppingCart />, // masih relevan (sales)
    key: "transaksipenjualan",
    children: [
      { icon: <ClipboardList size={16} />, label: "Pendataan PO SPPG" },
      { icon: <FileText size={16} />, label: "Invoice Penerbitan" },
      { icon: <ShieldCheck size={16} />, label: "Validasi Pembayaran" },
      { icon: <Banknote size={16} />, label: "Invoice Pelunasan" },
    ],
  },

  {
    label: "Laporan & Analisa",
    icon: <BarChart3 />, // laporan = chart
    key: "laporandananalisa",
    children: [
      { icon: <Boxes size={16} />, label: "Stok Barang" },
      { icon: <TrendingUp size={16} />, label: "Penjualan Per SPPG" },
      { icon: <Truck size={16} />, label: "Kinerja Logistik" },
      { icon: <FileText size={16} />, label: "Laba Rugi Transaksional" },
      { icon: <PackageSearch size={16} />, label: "Analisa Kebutuhan Stok" },
    ],
  },
];

export default function Sidebar({ open }: { open: boolean }) {
  const [openMenu, setOpenMenu] = useState<Record<string, boolean>>({});

  const toggleMenu = (key: string) => {
    setOpenMenu((prev) => ({
      ...prev,
      [key]: !prev[key],
    }));
  };

  return (
    <aside
      className={`bg-white h-screen overflow-y-auto shadow-lg transition-all duration-300 ${open ? "w-64" : "w-20"
        }`}
    >
      {/* HEADER */}
      <div className="h-16 flex items-center justify-center font-bold text-lg">
        {open ? "Admin Panel" : "AP"}
      </div>

      <nav className="p-2 space-y-2">
        <SidebarItem icon={<LayoutDashboard />} label="Dashboard" open={open} />

        {/* LOOP MENU */}
        {menus.map((menu) => (
          <div key={menu.key}>
            {/* PARENT */}
            <div
              onClick={() => toggleMenu(menu.key)}
              className={`flex items-center ${open ? "justify-between px-3" : "justify-center"
                } py-3 rounded-lg hover:bg-gray-100 cursor-pointer group relative`}
            >
              <div className="flex items-center gap-3">
                {menu.icon}
                {open && <span className="text-sm">{menu.label}</span>}
              </div>

              {/* ICON ARROW */}
              {open && (
                <ChevronDown
                  size={18}
                  className={`transition-transform ${openMenu[menu.key] ? "rotate-180" : ""
                    }`}
                />
              )}

              {/* TOOLTIP MODE COLLAPSE */}
              {!open && (
                <span className="absolute left-16 bg-black text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 whitespace-nowrap">
                  {menu.label}
                </span>
              )}
            </div>

            {/* DROPDOWN */}
            <AnimatePresence>
              {open && openMenu[menu.key] && (
                <motion.div
                  initial={{ height: 0, opacity: 0 }}
                  animate={{ height: "auto", opacity: 1 }}
                  exit={{ height: 0, opacity: 0 }}
                  transition={{ duration: 0.25 }}
                  className="overflow-hidden"
                >
                  <div className="ml-10 mt-1 space-y-1">
                    {menu.children.map((sub, i) => (
                      <SubItem key={i} icon={sub.icon} label={sub.label} />
                    ))}
                  </div>
                </motion.div>
              )}
            </AnimatePresence>
          </div>
        ))}

        <SidebarItem icon={<Settings />} label="Settings" open={open} />
      </nav>
    </aside>
  );
}

/* ================= COMPONENT ================= */

function SidebarItem({
  icon,
  label,
  open,
}: {
  icon: React.ReactNode;
  label: string;
  open: boolean;
}) {
  return (
    <div
      className={`flex items-center ${open ? "gap-3 px-3" : "justify-center"
        } py-3 rounded-lg hover:bg-gray-100 cursor-pointer group relative`}
    >
      {icon}

      {open && <span className="text-sm">{label}</span>}

      {/* TOOLTIP */}
      {!open && (
        <span className="absolute left-16 bg-black text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100">
          {label}
        </span>
      )}
    </div>
  );
}

function SubItem({
  icon,
  label,
}: {
  icon: React.ReactNode;
  label: string;
}) {
  return (
    <div className="flex items-center gap-2 px-2 py-2 rounded-md hover:bg-gray-100 cursor-pointer text-sm text-gray-600">
      {icon}
      <span>{label}</span>
    </div>
  );
}