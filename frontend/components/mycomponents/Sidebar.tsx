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
import { useRouter, usePathname } from "next/navigation";


/* ================= MENU CONFIG (TIDAK DIUBAH) ================= */
const menus = [
  {
    label: "Master",
    icon: <Landmark />,
    key: "master",
    children: [
      { icon: <MapPin size={16} />, label: "Wilayah & Lokasi", path: "/admin/master/wilayah" },
      { icon: <Truck size={16} />, label: "Supplier", path: "/admin/master/supplier" },
      { icon: <Users size={16} />, label: "Mitra", path: "/admin/master/mitra" },
      { icon: <Building2 size={16} />, label: "SPPG", path: "/admin/master/sppg" },
      { icon: <Boxes size={16} />, label: "Produk & Barang", path: "/admin/master/produk" },
      { icon: <Warehouse size={16} />, label: "Gudang", path: "/admin/master/gudang" },
      { icon: <Car size={16} />, label: "Armada", path: "/admin/master/armada" },
      { icon: <Users size={16} />, label: "Karyawan", path: "/admin/master/karyawan" },
      { icon: <Banknote size={16} />, label: "Bank & Rekening", path: "/admin/master/bank" },
      { icon: <ShieldCheck size={16} />, label: "Kategori & Satuan", path: "/admin/master/kategori" },
    ],
  },
  {
    label: "Transaksi Pembelian",
    icon: <BaggageClaim />,
    key: "pembelian",
    children: [
      { icon: <ClipboardList size={16} />, label: "Pengajuan Purchase Order", path: "/admin/pembelian/po" },
      { icon: <PackageCheck size={16} />, label: "Validasi Harga", path: "/admin/pembelian/validasi" },
      { icon: <Banknote size={16} />, label: "Invoice Pembayaran", path: "/admin/pembelian/invoice" },
      { icon: <Truck size={16} />, label: "Riwayat Kedatangan Barang", path: "/admin/pembelian/kedatangan" },
    ],
  },
  {
    label: "Warehouse System",
    icon: <Warehouse />,
    key: "warehouse",
    children: [
      { icon: <ScanLine size={16} />, label: "Cek Stok & Opname", path: "/admin/warehouse/stok" },
      { icon: <ArrowDownUp size={16} />, label: "Inbound", path: "/admin/warehouse/inbound" },
      { icon: <PackageCheck size={16} />, label: "Picking & Packing", path: "/admin/warehouse/picking" },
      { icon: <LayoutGrid size={16} />, label: "Penataan Slot", path: "/admin/warehouse/slot" },
      { icon: <PackageSearch size={16} />, label: "Inventory Adjustment", path: "/admin/warehouse/adjustment" },
    ],
  },
  {
    label: "Transaksi Penjualan",
    icon: <ShoppingCart />,
    key: "transaksipenjualan",
    children: [
      { icon: <ClipboardList size={16} />, label: "Pendataan PO SPPG", path: "/admin/penjualan/po" },
      { icon: <FileText size={16} />, label: "Invoice Penerbitan", path: "/admin/penjualan/invoice" },
      { icon: <ShieldCheck size={16} />, label: "Validasi Pembayaran", path: "/admin/penjualan/validasi" },
      { icon: <Banknote size={16} />, label: "Invoice Pelunasan", path: "/admin/penjualan/pelunasan" },
    ],
  },
  {
    label: "Laporan & Analisa",
    icon: <BarChart3 />,
    key: "laporandananalisa",
    children: [
      { icon: <Boxes size={16} />, label: "Stok Barang", path: "/admin/laporan/stok" },
      { icon: <TrendingUp size={16} />, label: "Penjualan Per SPPG", path: "/admin/laporan/penjualan" },
      { icon: <Truck size={16} />, label: "Kinerja Logistik", path: "/admin/laporan/logistik" },
      { icon: <FileText size={16} />, label: "Laba Rugi Transaksional", path: "/admin/laporan/labarugi" },
      { icon: <PackageSearch size={16} />, label: "Analisa Kebutuhan Stok", path: "/admin/laporan/analisa" },
    ],
  },
];

export default function Sidebar({ open }: { open: boolean }) {
  const [openMenu, setOpenMenu] = useState<Record<string, boolean>>({});
  const router = useRouter();
  const pathname = usePathname();

  const toggleMenu = (key: string) => {
    setOpenMenu((prev) => ({
      ...prev,
      [key]: !prev[key],
    }));
  };

  return (
    <aside
      className={`fixed top-0 left-0 h-screen bg-primary/10 shadow-md border-r transition-all duration-300 flex flex-col
      ${open ? "w-64" : "w-20"}`}
    >
      {/* HEADER */}
      <div className="h-16 flex items-center justify-center font-bold text-lg border-b">
        {open ? "Admin Panel" : "AP"}
      </div>

      {/* 🔥 SCROLL FIX DI SINI */}
      <nav className="flex-1 overflow-y-auto p-2 space-y-2 sidebar-scroll">

        <SidebarItem
          icon={<LayoutDashboard />}
          label="Dashboard"
          open={open}
          active={pathname === "/admin"}
          onClick={() => router.push("/admin")}
        />

        {menus.map((menu) => (
          <div key={menu.key}>
            <div
              onClick={() => toggleMenu(menu.key)}
              className={`flex items-center ${open ? "justify-between px-3" : "justify-center"} py-3 rounded-lg hover:bg-gray-100 cursor-pointer group relative`}
            >
              <div className="flex items-center gap-3">
                {menu.icon}
                {open && <span className="text-sm">{menu.label}</span>}
              </div>

              {open && (
                <ChevronDown
                  size={18}
                  className={`transition-transform ${openMenu[menu.key] ? "rotate-180" : ""}`}
                />
              )}
            </div>

            <AnimatePresence>
              {open && openMenu[menu.key] && (
                <motion.div
                  initial={{ height: 0, opacity: 0 }}
                  animate={{ height: "auto", opacity: 1 }}
                  exit={{ height: 0, opacity: 0 }}
                  className="overflow-hidden"
                >
                  <div className="ml-10 mt-1 space-y-1">
                    {menu.children.map((sub, i) => (
                      <SubItem
                        key={i}
                        icon={sub.icon}
                        label={sub.label}
                        active={pathname === sub.path}
                        onClick={() => router.push(sub.path)}
                      />
                    ))}
                  </div>
                </motion.div>
              )}
            </AnimatePresence>
          </div>
        ))}

        <SidebarItem
          icon={<Settings />}
          label="Settings"
          open={open}
          active={pathname === "/admin/settings"}
          onClick={() => router.push("/admin/settings")}
        />
      </nav>

      {/* FOOTER */}
      <div className="p-4 border-t text-center text-xs text-gray-500">
        © 2026
      </div>
    </aside>
  );
}

/* COMPONENT TETAP */
function SidebarItem({ icon, label, open, onClick, active }: any) {
  return (
    <div
      onClick={onClick}
      className={`flex items-center ${open ? "gap-3 px-3" : "justify-center"
        } py-3 rounded-lg cursor-pointer
      ${active ? "bg-primary/10 text-primary font-semibold" : "hover:bg-gray-100"}`}
    >
      {icon}
      {open && <span className="text-sm">{label}</span>}
    </div>
  );
}

function SubItem({ icon, label, onClick, active }: any) {
  return (
    <div
      onClick={onClick}
      className={`flex items-center gap-2 px-2 py-2 rounded-md cursor-pointer text-sm
      ${active
          ? "bg-primary/10 text-primary font-medium"
          : "text-gray-600 hover:bg-gray-100"
        }`}
    >
      {icon}
      <span>{label}</span>
    </div>
  );
}