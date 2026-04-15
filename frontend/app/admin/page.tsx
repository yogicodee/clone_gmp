"use client";

import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";

import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  Tooltip,
  ResponsiveContainer,
  LineChart,
  Line,
  PieChart,
  Pie,
  Cell,
  AreaChart,
  Area,
  Legend,
} from "recharts";

/* ================= DATA ================= */
const barData = [
  { name: "Jan", total: 400 },
  { name: "Feb", total: 300 },
  { name: "Mar", total: 500 },
  { name: "Apr", total: 700 },
];

const lineData = [
  { name: "Sen", value: 200 },
  { name: "Sel", value: 450 },
  { name: "Rab", value: 300 },
  { name: "Kam", value: 700 },
];

const pieData = [
  { name: "Produk A", value: 400 },
  { name: "Produk B", value: 300 },
  { name: "Produk C", value: 300 },
];

const areaData = [
  { name: "Jan", value: 200 },
  { name: "Feb", value: 400 },
  { name: "Mar", value: 300 },
  { name: "Apr", value: 600 },
];

/* ================= PAGE ================= */
export default function Page() {
  return (
    <div className="p-6 space-y-6">

      {/* HEADER */}
      <div>
        <h1 className="text-2xl font-bold">Overview Dashboard</h1>
        <p className="text-gray-500 text-sm">
          Visualisasi data penjualan
        </p>
      </div>

      {/* GRID CHART */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        {/* STAT BOX */}
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">

          <Card>
            <CardHeader>
              <CardTitle className="text-sm text-gray-500">Produk Basah</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-2xl font-bold">120</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="text-sm text-gray-500">Produk Kering</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-2xl font-bold">80</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="text-sm text-gray-500">Supplier</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-2xl font-bold">25</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="text-sm text-gray-500">Kendaraan</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-2xl font-bold">10</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="text-sm text-gray-500">Karyawan</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-2xl font-bold">45</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="text-sm text-gray-500">Mitra</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-2xl font-bold">18</p>
            </CardContent>
          </Card>

        </div>

        {/* BAR */}
        <Card>
          <CardHeader>
            <CardTitle>Penjualan Bulanan</CardTitle>
          </CardHeader>
          <CardContent>
            <ResponsiveContainer width="100%" height={250}>
              <BarChart data={barData}>
                <XAxis dataKey="name" />
                <YAxis />
                <Tooltip />
                <Bar dataKey="total" />
              </BarChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>

        {/* LINE */}
        <Card>
          <CardHeader>
            <CardTitle>Trend Penjualan</CardTitle>
          </CardHeader>
          <CardContent>
            <ResponsiveContainer width="100%" height={250}>
              <LineChart data={lineData}>
                <XAxis dataKey="name" />
                <YAxis />
                <Tooltip />
                <Line type="monotone" dataKey="value" />
              </LineChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>

        {/* PIE */}
        <Card>
          <CardHeader>
            <CardTitle>Distribusi Produk</CardTitle>
          </CardHeader>
          <CardContent>
            <ResponsiveContainer width="100%" height={250}>
              <PieChart>
                <Pie
                  data={pieData}
                  dataKey="value"
                  nameKey="name"
                  outerRadius={80}
                  label
                >
                  {pieData.map((_, index) => (
                    <Cell key={index} />
                  ))}
                </Pie>
                <Tooltip />
                <Legend />
              </PieChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>

        {/* AREA */}
        <Card>
          <CardHeader>
            <CardTitle>Growth Penjualan</CardTitle>
          </CardHeader>
          <CardContent>
            <ResponsiveContainer width="100%" height={250}>
              <AreaChart data={areaData}>
                <XAxis dataKey="name" />
                <YAxis />
                <Tooltip />
                <Area type="monotone" dataKey="value" />
              </AreaChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>

      </div>
    </div>
  );
}