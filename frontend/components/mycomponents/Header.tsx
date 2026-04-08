"use client";

import { Menu, Bell, User } from "lucide-react";

export default function Header({ onToggle }: { onToggle: () => void }) {
  return (
    <header className="h-16 bg-white shadow flex items-center justify-between px-6">
      
      {/* LEFT */}
      <div className="flex items-center gap-3">
        <button onClick={onToggle}>
          <Menu className="w-6 h-6" />
        </button>
        <h1 className="font-semibold text-lg">Dashboard</h1>
      </div>

      {/* RIGHT */}
      <div className="flex items-center gap-4">
        <Bell className="w-5 h-5 cursor-pointer" />
        <User className="w-5 h-5 cursor-pointer" />
      </div>
    </header>
  );
}