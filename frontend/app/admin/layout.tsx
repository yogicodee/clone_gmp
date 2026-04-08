"use client";

import { ReactNode, useState } from "react";
import { Menu } from "lucide-react";
import Sidebar from "@/components/mycomponents/Sidebar";
import Header from "@/components/mycomponents/Header";

export default function AdminLayout({ children }: { children: ReactNode }) {
  const [open, setOpen] = useState(true);

  return (
    <div className="flex min-h-screen overflow-hidden bg-gray-100">
      
      {/* SIDEBAR */}
      <Sidebar open={open} />

      {/* MAIN CONTENT */}
      <div className="flex flex-col flex-1">
        
        {/* HEADER */}
        <Header onToggle={() => setOpen(!open)} />

        {/* PAGE CONTENT */}
        <main className="flex-1 overflow-y-auto bg-gray-50 p-6">
          {children}
        </main>

      </div>
    </div>
  );
}