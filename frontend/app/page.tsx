import Sidebar from "@/components/SideBar";
import ChatDisplay from "@/components/ChatDisplay";

export default function Home() {
  return (
    <section className="flex flex-col h-[calc(100vh-80px)] w-full">
      <div className="flex h-full w-full rounded-lg shadow-md overflow-hidden border border-default-200">
        <Sidebar />
        <ChatDisplay />
      </div>
    </section>
  );
}
