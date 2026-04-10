"use client";

import { ReactNode, useState } from "react";
import { Menu } from "lucide-react";
import Sidebar from "@/components/mycomponents/Sidebar";
import Header from "@/components/mycomponents/Header";

export default function AdminLayout({ children }: { children: ReactNode }) {
  const [open, setOpen] = useState(true);

  return (
    <div className="min-h-screen bg-gray-100">

      {/* SIDEBAR */}
      <Sidebar open={open} />

      {/* MAIN */}
      <div
        className={`flex flex-col transition-all duration-300
    ${open ? "ml-64" : "ml-20"}`}
      >

        {/* HEADER */}
        <Header onToggle={() => setOpen(!open)} />

        {/* CONTENT */}
        <main className="h-[calc(100vh-64px)] overflow-y-auto p-6 bg-gray-50">
          {children}
        </main>

      </div>
    </div>
  );
}