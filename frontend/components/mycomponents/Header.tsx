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
        <h1 className="font-semibold text-lg">Admin</h1>
      </div>

      {/* RIGHT */}
      <div className="flex items-center gap-4">
        <p>Hi' Syahril</p>
        <User className="w-8 h-8 p-2 rounded-full cursor-pointer bg-gray-200 text-primary" />
      </div>
    </header>
  );
}