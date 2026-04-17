"use client";

import {
  Home,
  ShoppingCart,
  FileText,
  Bell,
  Settings,
  User,
  Package,
  Truck,
  Users,
  UserCheck,
} from "lucide-react";

import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";

import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  Tooltip,
  ResponsiveContainer,
  PieChart,
  Pie,
  Cell,
  BarChart,
  Bar,
} from "recharts";
import {
  motion,
  useMotionValue,
  useTransform,
  animate,
} from "framer-motion";
import { useEffect } from "react";

/* ================= DATA ================= */
const lineData = [
  { name: "10am", value: 40 },
  { name: "11am", value: 20 },
  { name: "12pm", value: 35 },
  { name: "01pm", value: 60 },
  { name: "02pm", value: 30 },
  { name: "03pm", value: 55 },
];

const pieData = [
  { name: "Sale", value: 70 },
  { name: "Return", value: 20 },
  { name: "Distribute", value: 10 },
];

const barData = [
  { name: "Sun", value: 40 },
  { name: "Mon", value: 60 },
  { name: "Tue", value: 30 },
  { name: "Wed", value: 80 },
  { name: "Thu", value: 50 },
];

/* ================= FORMAT ================= */
const formatNumber = (val: number) =>
  new Intl.NumberFormat("id-ID").format(val);

/* ================= COMPONENT ================= */
function StatCard({ title, value, icon: Icon, gradient }: any) {
  const count = useMotionValue(0);
  const rounded = useTransform(count, (latest) =>
    Math.floor(latest)
  );

  useEffect(() => {
    const controls = animate(count, value, {
      duration: 1.2,
      ease: "easeOut",
    });

    return controls.stop;
  }, [value]);

  return (
    <motion.div
      initial={{ opacity: 0, y: 20, scale: 0.95 }}
      animate={{ opacity: 1, y: 0, scale: 1 }}
      transition={{ duration: 0.4 }}
    >
      <Card className={`text-white rounded-2xl ${gradient}`}>
        <CardContent className="p-4 flex flex-col gap-3">

          {/* ICON + TITLE */}
          <div className="bg-white/20 p-2 rounded-lg flex items-center gap-2 justify-center">
            <Icon size={20} />
            <p className="text-base opacity-80">{title}</p>
          </div>

          {/* VALUE (ANIMATED) */}
          <motion.p className="text-3xl font-bold text-center">
            {rounded}
          </motion.p>

        </CardContent>
      </Card>
    </motion.div>
  );
}



const COLORS = ["#3b82f6", "#22c55e", "#ef4444"]; // biru, hijau, merah

const orders = [
  {
    id: "#9812",
    product: "Air Vapormax",
    status: "Complete",
    price: 22500,
  },
  {
    id: "#9813",
    product: "Nike Air Force",
    status: "Pending",
    price: 18000,
  },
  {
    id: "#9814",
    product: "Air Vapormax",
    status: "Complete",
    price: 22500,
  },
  {
    id: "#9815",
    product: "Nike Air Force",
    status: "Pending",
    price: 18000,
  },
  {
    id: "#9816",
    product: "Air Vapormax",
    status: "Complete",
    price: 22500,
  },
  {
    id: "#9817",
    product: "Nike Air Force",
    status: "Pending",
    price: 18000,
  },
  {
    id: "#9818",
    product: "Air Vapormax",
    status: "Complete",
    price: 22500,
  },
  {
    id: "#9819",
    product: "Nike Air Force",
    status: "Pending",
    price: 18000,
  },
  {
    id: "#9820",
    product: "Air Vapormax",
    status: "Complete",
    price: 22500,
  },
];

const formatRupiah = (number: number) => {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(number);
};

/* ================= PAGE ================= */
export default function Dashboard() {
  return (
    <div className="flex min-h-screen bg-white/40 backdrop-blur-2xl rounded-2xl border border-white">

      {/* MAIN */}
      <main className="flex-1 p-6 space-y-6">

        {/* HEADER */}
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-2xl font-bold">
              Dashboard Garuda Merah Putih
            </h1>
            <p className="text-sm text-muted-foreground">
              Ringkasan performa penjualan
            </p>
          </div>
          <div className="text-sm text-muted-foreground">
            April 2026
          </div>
        </div>

        {/* KPI */}
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
          <StatCard title="Produk Basah" value={120} icon={Package} gradient="bg-gradient-to-br from-lime-500 to-lime-200" />
          <StatCard title="Produk Kering" value={80} icon={Package} gradient="bg-gradient-to-br from-teal-500 to-teal-200" />
          <StatCard title="Supplier" value={25} icon={Truck} gradient="bg-gradient-to-br from-slate-500 to-slate-200" />
          <StatCard title="Kendaraan" value={10} icon={Truck} gradient="bg-gradient-to-br from-rose-500 to-rose-200" />
          <StatCard title="Karyawan" value={45} icon={Users} gradient="bg-gradient-to-br from-indigo-500 to-indigo-200" />
          <StatCard title="Mitra" value={18} icon={UserCheck} gradient="bg-gradient-to-br from-zinc-500 to-zinc-200" />
        </div>

        {/* CONTENT */}
        <div className="grid grid-cols-1 lg:grid-cols-5 gap-6">

          {/* LEFT */}
          <div className="lg:col-span-3 space-y-6">

            {/* LINE */}
            <Card className="bg-gradient-to-t from-blue-950 via-blue-950 to-zinc-900">
              <CardHeader>
                <CardTitle className="text-white">Sales Reports</CardTitle>
              </CardHeader>
              <CardContent>
                <ResponsiveContainer width="100%" height={250}>
                  <LineChart data={lineData}>
                    <XAxis dataKey="name" stroke="#ffffff" />
                    <YAxis stroke="#ffffff" />
                    <Tooltip />
                    <Line dataKey="value" stroke="#ffffff" />
                  </LineChart>
                </ResponsiveContainer>
              </CardContent>
            </Card>

            {/* TABLE */}
            <Card>
              <CardHeader>
                <CardTitle>Recent Order</CardTitle>
              </CardHeader>
              <CardContent>
                <table className="w-full text-sm">
                  <thead className="text-gray-500">
                    <tr className="text-left">
                      <th>ID</th>
                      <th>Product</th>
                      <th>Status</th>
                      <th>Price</th>
                    </tr>
                  </thead>
                  <tbody>
                    {orders.map((item, index) => (
                      <tr key={index}>
                        <td>{item.id}</td>
                        <td>{item.product}</td>
                        <td
                          className={
                            item.status === "Complete"
                              ? "text-green-500"
                              : "text-primary"
                          }
                        >
                          {item.status}
                        </td>
                        <td>{formatRupiah(item.price)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </CardContent>
            </Card>

          </div>

          {/* RIGHT */}
          <div className="lg:col-span-2 space-y-6">

            {/* PIE */}
            <Card>
              <CardHeader>
                <CardTitle>Sales Reports</CardTitle>
              </CardHeader>
              <CardContent className="relative flex justify-center items-center">
                <ResponsiveContainer width="100%" height={220}>
                  <PieChart>
                    <Pie
                      data={pieData}
                      dataKey="value"
                      innerRadius={50}
                      outerRadius={80}
                    >
                      {pieData.map((_, i) => (
                        <Cell key={i} fill={COLORS[i % COLORS.length]} />
                      ))}
                    </Pie>
                  </PieChart>
                </ResponsiveContainer>

                {/* CENTER TEXT */}
                <div className="absolute text-xl font-bold">
                  70%
                </div>
              </CardContent>
            </Card>

            {/* BAR */}
            <Card>
              <CardHeader>
                <CardTitle>Analytics</CardTitle>
              </CardHeader>
              <CardContent>
                <ResponsiveContainer width="100%" height={220}>
                  <BarChart data={barData}>
                    <XAxis dataKey="name" />
                    <YAxis />
                    <Tooltip />
                    <Bar dataKey="value" fill="#facc15" />
                  </BarChart>
                </ResponsiveContainer>
              </CardContent>
            </Card>

          </div>

        </div>
      </main>
    </div>
  );
}