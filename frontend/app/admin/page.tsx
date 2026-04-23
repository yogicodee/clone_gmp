"use client";

import { useEffect, useState } from "react";

import LineChart from "@/components/mycomponents/chart/LineChart";
import BarChart from "@/components/mycomponents/chart/BarChart";
import PieChart from "@/components/mycomponents/chart/PieChart";

import {
  Card,
  CardContent,
  CardHeader,
  CardTitle
} from "@/components/ui/card";

import { Button } from "@/components/ui/button";

import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

import {
  MoreVertical,
  ShoppingBag,
  Package,
  RotateCcw,
  DollarSign,
  Wallet,
  BadgePercent,
} from "lucide-react";

const stats = [
  {
    title: "Omset Hari Ini",
    value: "2341+",
    percent: 80,
    color: "text-blue-500",
    stroke: "stroke-blue-500",
    bg: "bg-blue-100",
    icon: ShoppingBag,
  },
  {
    title: "Pengeluaran",
    value: "178+",
    percent: 30,
    color: "text-green-500",
    stroke: "stroke-green-500",
    bg: "bg-green-100",
    icon: Package,
  },
  {
    title: "Keuntungan",
    value: "67+",
    percent: 20,
    color: "text-red-500",
    stroke: "stroke-red-500",
    bg: "bg-red-100",
    icon: RotateCcw,
  },
  {
    title: "Saldo Bank",
    value: "890+",
    percent: 65,
    color: "text-purple-500",
    stroke: "stroke-purple-500",
    bg: "bg-purple-100",
    icon: DollarSign,
  },
  {
    title: "Piutang",
    value: "540+",
    percent: 55,
    color: "text-orange-500",
    stroke: "stroke-orange-500",
    bg: "bg-orange-100",
    icon: Wallet,
  },
  {
    title: "Hutang",
    value: "320+",
    percent: 40,
    color: "text-yellow-500",
    stroke: "stroke-yellow-500",
    bg: "bg-yellow-100",
    icon: Package,
  },
  {
    title: "Margin %",
    value: "28%",
    percent: 28,
    color: "text-emerald-500",
    stroke: "stroke-emerald-500",
    bg: "bg-emerald-100",
    icon: BadgePercent,
  },
];

function ProgressCircle({
  percent,
  color,
}: {
  percent: number;
  color: string;
}) {

  const radius = 22;
  const circumference = 2 * Math.PI * radius;

  const offset =
    circumference - (percent / 100) * circumference;

  return (
    <div className="relative h-16 w-16">
      <svg
        width="64"
        height="64"
        className="rotate-[-90deg]"
      >
        <circle
          cx="32"
          cy="32"
          r={radius}
          strokeWidth="6"
          className="stroke-muted"
          fill="none"
        />

        <circle
          cx="32"
          cy="32"
          r={radius}
          strokeWidth="6"
          fill="none"
          strokeLinecap="round"
          strokeDasharray={circumference}
          strokeDashoffset={offset}
          className={color}
        />
      </svg>

      <span className="absolute inset-0 flex items-center justify-center text-xs font-semibold">
        {percent}%
      </span>

    </div>
  );
}

export default function Dashboard() {

  const [nama, setNama] = useState("");

  useEffect(() => {

    const user = localStorage.getItem("user");

    if (user) {
      try {
        const parsed = JSON.parse(user);
        setNama(parsed.nama || parsed.name || "User");
      } catch {
        setNama("User");
      }
    }

  }, []);

  const getGreeting = () => {

    const hour = new Date().getHours();

    if (hour >= 5 && hour < 12) return "☀️ Good Morning";
    if (hour >= 12 && hour < 18) return "🌤️ Good Afternoon";
    if (hour >= 18 && hour < 22) return "🌙 Good Evening";

    return "😴 Good Night";

  };

  return (
    <div className="flex min-h-screen">

      <main className="flex-1 p-6 space-y-6 bg-white/30 backdrop-blur-2xl rounded-3xl border border-white">

        {/* HEADER */}
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-2xl font-bold">
              {getGreeting()}, {nama}
            </h1>

            <p className="text-sm text-muted-foreground">
              Ringkasan performa keuangan koperasi
            </p>

          </div>

          <Button variant="outline">
            Bulan Ini
          </Button>

        </div>


        {/* KPI */}
        <div className="grid grid-cols-12 gap-5">

          {stats.map((item, index) => {

            const Icon = item.icon

            const bottomRow = index >= 4

            return (
              <Card
                key={index}
                className={`
rounded-2xl bg-white backdrop-blur-2xl
col-span-12
md:col-span-6
${bottomRow
                    ? "xl:col-span-4"
                    : "xl:col-span-3"}

`}
              >

                <CardContent className="p-5">

                  <div className="flex items-start justify-between mb-5">

                    <div
                      className={`w-11 h-11 rounded-xl flex items-center justify-center ${item.bg}`}
                    >
                      <Icon className={`w-5 h-5 ${item.color}`} />
                    </div>


                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <button>
                          <MoreVertical className="w-4 h-4 text-muted-foreground" />
                        </button>
                      </DropdownMenuTrigger>

                      <DropdownMenuContent align="end">
                        <DropdownMenuItem>Harian</DropdownMenuItem>
                        <DropdownMenuItem>Bulanan</DropdownMenuItem>
                        <DropdownMenuItem>Tahunan</DropdownMenuItem>
                      </DropdownMenuContent>

                    </DropdownMenu>

                  </div>



                  <div className="flex justify-between items-center">

                    <div>
                      <h2 className="text-3xl font-bold">
                        {item.value}
                      </h2>

                      <p className="text-sm text-muted-foreground mt-1">
                        {item.title}
                      </p>

                    </div>

                    <ProgressCircle
                      percent={item.percent}
                      color={item.stroke}
                    />

                  </div>

                </CardContent>
              </Card>
            )

          })}

        </div>



        {/* MAIN ANALYTICS */}
        <div className="grid gap-6 lg:grid-cols-2">

          {/* Cashflow / Omset Trend */}
          <Card className="rounded-2xl shadow-sm border-0">
            <CardHeader>
              <CardTitle>Cashflow & Omset Trend</CardTitle>
            </CardHeader>

            <CardContent>
              <LineChart />
            </CardContent>
          </Card>


          {/* Penjualan Per SPPG */}
          <Card className="rounded-2xl shadow-sm border-0">
            <CardHeader>
              <CardTitle>Penjualan per SPPG</CardTitle>
            </CardHeader>

            <CardContent>
              <PieChart />
            </CardContent>

          </Card>

        </div>




        {/* SECONDARY */}
        <div className="grid gap-6 lg:grid-cols-4">

          {/* BEBAN */}
          <Card className="rounded-2xl shadow-sm border-0 lg:col-span-2">
            <CardHeader className="pb-2">
              <CardTitle>Beban Operasional Analysis</CardTitle>
            </CardHeader>

            <CardContent className="pt-2">
              <BarChart />
            </CardContent>

          </Card>



          {/* PIUTANG AGING */}
          <Card className="rounded-2xl bg-white">

            <CardHeader className="pb-2">
              <CardTitle>Piutang Aging</CardTitle>
            </CardHeader>

            <CardContent className="space-y-4 pt-2">

              <div className="rounded-xl bg-muted/50 p-4 flex justify-between items-center">
                <div>
                  <p className="text-sm text-muted-foreground">
                    0-30 Hari
                  </p>
                  <h4 className="font-semibold text-lg">
                    120 Jt
                  </h4>
                </div>

                <span className="text-green-600 text-sm font-medium">
                  Lancar
                </span>
              </div>



              <div className="rounded-xl bg-muted/50 p-4 flex justify-between items-center">
                <div>
                  <p className="text-sm text-muted-foreground">
                    31-60 Hari
                  </p>
                  <h4 className="font-semibold text-lg">
                    48 Jt
                  </h4>
                </div>

                <span className="text-yellow-600 text-sm font-medium">
                  Warning
                </span>

              </div>



              <div className="rounded-xl bg-muted/50 p-4 flex justify-between items-center">
                <div>
                  <p className="text-sm text-muted-foreground">
                    61+ Hari
                  </p>
                  <h4 className="font-semibold text-lg">
                    12 Jt
                  </h4>
                </div>

                <span className="text-red-600 text-sm font-medium">
                  Overdue
                </span>

              </div>

            </CardContent>

          </Card>




          {/* ASSET */}
          <Card className="rounded-2xl bg-linear-150 from-blue-800 to-blue-950 backdrop-blur-2xl text-white">

            <CardHeader className="pb-2">
              <CardTitle>Asset / Persediaan</CardTitle>
            </CardHeader>

            <CardContent className="space-y-5 pt-2">

              <div className="rounded-xl bg-muted/50 p-4">
                <p className="text-sm text-white/70">
                  Total Asset
                </p>

                <h3 className="text-2xl font-bold mt-2">
                  Rp 250.000.000
                </h3>
              </div>


              <div className="rounded-xl bg-muted/50 p-4">
                <p className="text-sm text-white/70">
                  Jumlah SKU
                </p>

                <h3 className="text-2xl font-bold mt-2">
                  148
                </h3>
              </div>


              <div className="rounded-xl bg-muted/50 p-4">
                <p className="text-sm text-white/70">
                  Dead Stock
                </p>

                <h3 className="text-2xl font-bold mt-2">
                  6 Item
                </h3>
              </div>

            </CardContent>

          </Card>

        </div>






      </main>
    </div>
  )

}