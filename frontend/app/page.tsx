"use client";

import Sidebar from "@/components/SideBar";

export default function Home() {
  return (
    <section className="flex flex-col h-[calc(100vh-80px)] w-full">
      <div className="flex h-full w-full rounded-lg shadow-md overflow-hidden border border-default-200">
        <Sidebar />
        <div className="flex-1 flex items-center justify-center bg-default-50 p-6">
          <div className="text-center max-w-md">
            <h2 className="text-2xl font-bold mb-3">Welcome to the Chat App</h2>
            <p className="text-default-600">
              Select a conversation from the sidebar or create a new one to get
              started.
            </p>
          </div>
        </div>
      </div>
    </section>
  );
}
