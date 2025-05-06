"use client";

import React, { useState } from "react";
import { Card } from "@heroui/card";
import { Button } from "@heroui/button";
import { Link } from "@heroui/link";
import { Avatar } from "@heroui/avatar";
import { Divider } from "@heroui/divider";
import { Input } from "@heroui/input";
import {
  Modal,
  ModalContent,
  ModalHeader,
  ModalBody,
  ModalFooter,
} from "@heroui/modal";
import {
  LuSearch,
  LuPlus,
  LuUsers,
  LuMessageSquare,
  LuSettings,
  LuLogOut,
  LuRefreshCw,
} from "react-icons/lu";
import {
  Dropdown,
  DropdownTrigger,
  DropdownMenu,
  DropdownItem,
} from "@heroui/dropdown";

import { useAuth } from "@/context/authContext";
import { useConversation } from "@/context/conversationContext";
import ConversationService from "@/services/conversationService";

export default function Sidebar() {
  const { user, loadingInitial, validating, loading, logout, refreshUserData } =
    useAuth();
  const [searchQuery, setSearchQuery] = useState("");
  const { setActiveConversation } = useConversation();
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [newConversationData, setNewConversationData] = useState({
    name: "",
    avatarUrl: "",
  });
  const [creating, setCreating] = useState(false);
  const [error, setError] = useState("");

  // Filter conversations based on search query
  const filteredConversations = user?.conversations
    ? user.conversations.filter((conversation) =>
        conversation.name.toLowerCase().includes(searchQuery.toLowerCase()),
      )
    : [];

  const handleLogout = async () => {
    try {
      setActiveConversation("");
      await logout();
    } catch (error) {
      console.error("Logout failed:", error);
    }
  };

  const handleRefreshData = async () => {
    try {
      await refreshUserData();
    } catch (error) {
      console.error("Failed to refresh user data:", error);
    }
  };

  const handleOpenCreateModal = () => {
    setNewConversationData({
      name: "",
      avatarUrl: "",
    });
    setError("");
    setIsCreateModalOpen(true);
  };

  const handleCloseCreateModal = () => {
    setIsCreateModalOpen(false);
  };

  const handleConversationInputChange = (
    e: React.ChangeEvent<HTMLInputElement>,
  ) => {
    const { name, value } = e.target;

    setNewConversationData((prev) => ({
      ...prev,
      [name]: value,
    }));
  };

  const handleCreateConversation = async () => {
    if (!newConversationData.name.trim()) {
      setError("Conversation name is required");

      return;
    }

    try {
      setCreating(true);
      setError("");

      const newConversation = await ConversationService.createConversation(
        newConversationData.name,
        newConversationData.avatarUrl,
      );

      await refreshUserData();
      setIsCreateModalOpen(false);

      // Select the new conversation
      if (newConversation && newConversation.id) {
        setActiveConversation(newConversation.id);
      }
    } catch (e) {
      console.error("Failed to create new conversation:", e);
      setError("Failed to create conversation. Please try again.");
    } finally {
      setCreating(false);
    }
  };

  // Format timestamp for messages
  const formatMessageTime = (timestamp: string) => {
    const date = new Date(timestamp);
    const now = new Date();

    // Check if the message is from today
    if (date.toDateString() === now.toDateString()) {
      return date.toLocaleTimeString([], {
        hour: "2-digit",
        minute: "2-digit",
      });
    }

    // Check if the message is from yesterday
    const yesterday = new Date(now);

    yesterday.setDate(now.getDate() - 1);
    if (date.toDateString() === yesterday.toDateString()) {
      return "Yesterday";
    }

    // Otherwise return the date
    return date.toLocaleDateString();
  };

  // Show loading state for initial load
  if (loadingInitial) {
    return (
      <div className="w-72 h-full bg-background border-r flex flex-col p-4">
        <div className="animate-pulse space-y-4">
          <div className="flex items-center space-x-3">
            <div className="rounded-full bg-default-200 h-10 w-10" />
            <div className="flex-1">
              <div className="h-4 bg-default-200 rounded w-3/4 mb-2" />
              <div className="h-3 bg-default-200 rounded w-1/2" />
            </div>
          </div>
          <Divider />
          <div className="h-8 bg-default-200 rounded w-full" />
          <div className="space-y-2">
            <div className="h-4 bg-default-200 rounded w-1/3" />
            <div className="h-14 bg-default-200 rounded w-full" />
            <div className="h-14 bg-default-200 rounded w-full" />
            <div className="h-14 bg-default-200 rounded w-full" />
          </div>
        </div>
      </div>
    );
  }

  return (
    <>
      <div className="w-72 h-full bg-background border-r flex flex-col">
        {/* User Profile Section */}
        <div className="p-4">
          {user ? (
            <div className="flex items-center justify-between">
              <div className="flex items-center space-x-3">
                <div className="relative">
                  <Avatar
                    className="h-10 w-10"
                    fallback={
                      user.displayName?.[0] || user.email[0].toUpperCase()
                    }
                    src={user.avatarUrl}
                  />
                  {validating && (
                    <div className="absolute -bottom-1 -right-1 bg-default-100 rounded-full p-0.5">
                      <LuRefreshCw className="h-3 w-3 text-default-500 animate-spin" />
                    </div>
                  )}
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium truncate">
                    {user.displayName || "User"}
                  </p>
                  <p className="text-xs text-default-500 truncate">
                    {user.email}
                  </p>
                </div>
              </div>

              <Dropdown>
                <DropdownTrigger>
                  <Button isIconOnly size="sm" variant="light">
                    <LuSettings className="h-4 w-4" />
                  </Button>
                </DropdownTrigger>
                <DropdownMenu aria-label="User actions">
                  <DropdownItem key="profile">Profile settings</DropdownItem>
                  <DropdownItem
                    key="refresh"
                    isDisabled={loading || validating}
                    startContent={<LuRefreshCw className="h-4 w-4" />}
                    onClick={handleRefreshData}
                  >
                    Refresh data
                  </DropdownItem>
                  <DropdownItem
                    key="logout"
                    className="text-danger"
                    color="danger"
                    isDisabled={loading}
                    startContent={<LuLogOut className="h-4 w-4" />}
                    onClick={handleLogout}
                  >
                    Logout
                  </DropdownItem>
                </DropdownMenu>
              </Dropdown>
            </div>
          ) : (
            <Card className="p-3 bg-default-50">
              <h3 className="text-sm font-medium mb-2">Not signed in</h3>
              <Link href="/auth/login">
                <Button className="w-full" color="primary" size="sm">
                  Sign In
                </Button>
              </Link>
            </Card>
          )}
        </div>

        <Divider />

        {/* Only show conversations section if user is logged in */}
        {user && (
          <>
            {/* Conversations search */}
            <div className="p-3">
              <Input
                classNames={{
                  inputWrapper: "bg-default-100",
                }}
                placeholder="Search conversations..."
                size="sm"
                startContent={<LuSearch className="text-default-400 h-4 w-4" />}
                type="search"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
              />
            </div>

            {/* Conversations list */}
            <div className="flex-1 overflow-y-auto px-3">
              <div className="flex items-center justify-between mb-2">
                <h2 className="text-xs font-semibold text-default-600">
                  Conversations
                </h2>
                <Button
                  isIconOnly
                  size="sm"
                  variant="light"
                  onClick={handleOpenCreateModal}
                >
                  <LuPlus className="h-4 w-4" />
                </Button>
              </div>

              {user.conversations && user.conversations.length > 0 ? (
                <div className="space-y-1">
                  {filteredConversations.map((conversation) => (
                    <button
                      key={conversation.id}
                      className="flex items-center gap-3 p-2 rounded-lg cursor-pointer hover:bg-default-100 w-full"
                      onClick={() => {
                        setActiveConversation(conversation.id);
                      }}
                    >
                      <Avatar
                        fallback={
                          conversation.name?.[0] || (
                            <LuUsers className="h-4 w-4" />
                          )
                        }
                        size="sm"
                        src={conversation.avatarUrl || undefined}
                      />

                      <div className="flex-1 min-w-0">
                        <div className="flex justify-between items-center">
                          <p className="text-sm font-medium truncate">
                            {conversation.name}
                          </p>
                          {conversation.lastMessage && (
                            <span className="text-xs text-default-400">
                              {formatMessageTime(
                                conversation.lastMessage.sentAt,
                              )}
                            </span>
                          )}
                        </div>
                        <div className="flex items-center gap-1">
                          {conversation.role === "ADMIN" && (
                            <span className="text-[0.65rem] bg-primary/10 text-primary px-1 rounded">
                              Admin
                            </span>
                          )}
                          <p className="text-xs text-default-500 truncate">
                            {conversation.lastMessage
                              ? `${conversation.lastMessage.senderName}: ${conversation.lastMessage.content}`
                              : "No messages yet"}
                          </p>
                        </div>
                      </div>
                    </button>
                  ))}
                </div>
              ) : (
                <div className="text-center py-8 text-default-400">
                  <LuMessageSquare className="mx-auto h-8 w-8 mb-2" />
                  <p className="text-sm">No conversations found</p>
                </div>
              )}
            </div>
          </>
        )}
      </div>

      {/* Create Conversation Modal */}
      <Modal
        classNames={{
          base: "bg-background border border-default-200",
        }}
        isOpen={isCreateModalOpen}
        placement="center"
        onClose={handleCloseCreateModal}
      >
        <ModalContent>
          <ModalHeader className="flex flex-col gap-1">
            Create New Conversation
          </ModalHeader>
          <ModalBody>
            {error && (
              <div className="bg-danger-50 border border-danger-200 text-danger p-2 mb-3 rounded-md text-sm">
                {error}
              </div>
            )}
            <div className="space-y-4">
              <div>
                <label
                  className="text-sm font-medium mb-1 block"
                  htmlFor={"conversationName"}
                >
                  Conversation Name
                </label>
                <Input
                  name="name"
                  placeholder="Enter a name for the conversation"
                  value={newConversationData.name}
                  onChange={handleConversationInputChange}
                />
              </div>
              <div>
                <label
                  className="text-sm font-medium mb-1 block"
                  htmlFor={"avatarUrl"}
                >
                  Avatar URL (optional)
                </label>
                <Input
                  name="avatarUrl"
                  placeholder="https://example.com/avatar.jpg"
                  value={newConversationData.avatarUrl}
                  onChange={handleConversationInputChange}
                />
              </div>
            </div>
          </ModalBody>
          <ModalFooter>
            <Button variant="bordered" onClick={handleCloseCreateModal}>
              Cancel
            </Button>
            <Button
              color="primary"
              isDisabled={creating || !newConversationData.name.trim()}
              isLoading={creating}
              onClick={handleCreateConversation}
            >
              Create
            </Button>
          </ModalFooter>
        </ModalContent>
      </Modal>
    </>
  );
}
