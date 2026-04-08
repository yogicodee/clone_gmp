"use client";

import { useState } from "react";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Shield, UserCog } from "lucide-react";
import Link from "next/link";

export default function LoginPage() {
  const [role, setRole] = useState<"admin" | "superadmin">("admin");

  return (
    <div className="space-y-6 text-center">
      <h2 className="text-2xl font-semibold">Login</h2>
      <p className="text-sm opacity-80">
        Login untuk melanjutkan ke sistem GMP.
      </p>

      {/* ROLE SELECT */}
      <div className="grid grid-cols-2 gap-3">
        {/* ADMIN */}
        <button
          type="button"
          onClick={() => setRole("admin")}
          className={`flex flex-col items-center justify-center p-4 rounded-xl border transition ${role === "admin"
            ? "border-primary bg-primary/10"
            : "border-gray-200 hover:border-primary/50"
            }`}
        >
          <UserCog className="w-6 h-6 mb-2" />
          <span className="text-sm font-medium">Admin</span>
        </button>

        {/* SUPER ADMIN */}
        <button
          type="button"
          onClick={() => setRole("superadmin")}
          className={`flex flex-col items-center justify-center p-4 rounded-xl border transition ${role === "superadmin"
            ? "border-primary bg-primary/10"
            : "border-gray-200 hover:border-primary/50"
            }`}
        >
          <Shield className="w-6 h-6 mb-2" />
          <span className="text-sm font-medium">Super Admin</span>
        </button>
      </div>

      {/* FORM */}
      <form className="space-y-4">
        <Input type="email" placeholder="Email" />
        <Input type="password" placeholder="Password" />

        {/* Hidden role (biar bisa dikirim ke backend) */}
        <input type="hidden" value={role} name="role" />

        <Link href="/admin">
          <Button className="w-full py-6 text-lg hover:bg-primary/90 cursor-pointer">
            Login sebagai {role === "admin" ? "Admin" : "Super Admin"}
          </Button>
        </Link>
      </form>
    </div>
  );
}