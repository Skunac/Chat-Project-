"use client";

import React, { useState, useEffect, useRef } from "react";
import { Avatar } from "@heroui/avatar";
import { Input } from "@heroui/input";
import { Button } from "@heroui/button";
import { LuSend, LuLoader } from "react-icons/lu";

import { useAuth } from "@/context/authContext";
import { useConversation } from "@/context/conversationContext";
import { useMercureSubscription } from "@/hooks/useMercureSubscription";
import { Conversation } from "@/types/conversation";
import { ConversationMessage } from "@/types/message";
import conversationService from "@/services/conversationService";
import messageService from "@/services/messageService";

export default function ChatDisplay() {
  const { activeConversationId } = useConversation();
  const { user, refreshUserData } = useAuth();
  const [conversation, setConversation] = useState<Conversation | null>(null);
  const [messages, setMessages] = useState<ConversationMessage[]>([]);
  const [newMessage, setNewMessage] = useState("");
  const [loading, setLoading] = useState(false);
  const [sendingMessage, setSendingMessage] = useState(false);
  const messagesEndRef = useRef<HTMLDivElement>(null);

  // Setup Mercure subscription when conversation is active
  const mercureTopics = activeConversationId
    ? [`conversation/${activeConversationId}`]
    : [];

  // Handle incoming messages from Mercure
  const handleMercureMessage = (data: any) => {
    console.log("Received Mercure data:", data);

    // Check if it's a new message
    if (data.id && data.content && data.senderId) {
      console.log("Processing as message update");

      // Create a message object from the mercure data
      const mercureMessage: ConversationMessage = {
        id: data.id,
        conversation: activeConversationId || "",
        content: data.content,
        sentAt: data.sentAt || new Date().toISOString(),
        sender: {
          id: data.senderId,
          displayName: data.senderName || "User",
          avatarUrl: null,
        },
      };

      // Add to messages if it's not already there (avoid duplicates)
      setMessages((prevMessages) => {
        if (!prevMessages.some((msg) => msg.id === mercureMessage.id)) {
          console.log("Adding new message to state:", mercureMessage);

          return [...prevMessages, mercureMessage];
        }
        console.log("Message already exists, not adding duplicate");

        return prevMessages;
      });
    } else {
      console.log("Received data doesn't match expected message format:", data);
    }
  };

  // Subscribe to Mercure updates with error handling
  const { isConnected } = useMercureSubscription({
    topics: mercureTopics,
    onMessage: handleMercureMessage,
  });

  // Fetch conversation data when activeConversationId changes
  useEffect(() => {
    const fetchConversationData = async () => {
      if (!activeConversationId) {
        setConversation(null);
        setMessages([]);

        return;
      }

      setLoading(true);
      try {
        console.log("Fetching data for conversation:", activeConversationId);
        const [conversationData, messagesData] = await Promise.all([
          conversationService.getConversation(activeConversationId),
          conversationService.getMessagesOfAConversation(activeConversationId),
        ]);

        console.log("Fetched conversation:", conversationData);
        console.log("Fetched messages:", messagesData);

        setConversation(conversationData);
        setMessages(messagesData);
      } catch (error) {
        console.error("Failed to fetch conversation data:", error);
      } finally {
        setLoading(false);
      }
    };

    fetchConversationData();
  }, [activeConversationId]);

  // Scroll to bottom when messages change
  useEffect(() => {
    if (messages.length > 0) {
      messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
    }
  }, [messages]);

  const handleSendMessage = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newMessage.trim() || !activeConversationId || !user) return;

    console.log("Sending message:", newMessage);
    setSendingMessage(true);
    try {
      const sentMessage = await messageService.sendMessage(
        activeConversationId,
        newMessage,
      );

      await refreshUserData();
      console.log("Message sent successfully:", sentMessage);

      // Add optimistically for better UX - will be handled by Mercure otherwise
      setMessages((prev) => {
        if (!prev.some((msg) => msg.id === sentMessage.id)) {
          return [...prev, sentMessage];
        }

        return prev;
      });

      setNewMessage("");
    } catch (error) {
      console.error("Failed to send message:", error);
    } finally {
      setSendingMessage(false);
    }
  };

  // Render the connection status with more detail for debugging
  const renderConnectionStatus = () => {
    if (!activeConversationId) return null;

    return (
      <div
        className={`px-2 py-1 text-xs rounded ${
          isConnected
            ? "bg-green-100 text-green-600"
            : "bg-yellow-100 text-yellow-600"
        }`}
      >
        {isConnected ? "Live updates active" : "Connecting to live updates..."}
      </div>
    );
  };

  // Show empty state when no conversation is selected
  if (!activeConversationId) {
    return (
      <div className="flex-1 flex items-center justify-center bg-default-50 p-6">
        <div className="text-center max-w-md">
          <h2 className="text-2xl font-bold mb-3">Welcome to the Chat App</h2>
          <p className="text-default-600">
            Select a conversation from the sidebar or create a new one to get
            started.
          </p>
        </div>
      </div>
    );
  }

  // Show loading state
  if (loading) {
    return (
      <div className="flex-1 flex items-center justify-center bg-default-50">
        <div className="flex flex-col items-center">
          <LuLoader className="h-8 w-8 animate-spin text-primary mb-4" />
          <p>Loading conversation...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="flex-1 flex flex-col h-full bg-default-50">
      {/* Conversation header */}
      {conversation && (
        <div className="p-4 border-b bg-background flex items-center gap-3">
          <Avatar
            fallback={conversation.name?.[0] || "C"}
            size="md"
            src={conversation.avatarUrl || undefined}
          />
          <div className="flex-1">
            <h2 className="font-medium">{conversation.name}</h2>
            <p className="text-xs text-default-500">
              {conversation.participants?.length || 0} participants
            </p>
          </div>
          {renderConnectionStatus()}
        </div>
      )}

      {/* Messages area */}
      <div className="flex-1 overflow-y-auto p-4 space-y-4">
        {messages.length === 0 ? (
          <div className="flex items-center justify-center h-full">
            <p className="text-default-400">
              No messages yet. Start a conversation!
            </p>
          </div>
        ) : (
          messages.map((message) => (
            <div
              key={message.id}
              className={`flex items-start gap-2 max-w-[80%] ${
                message.sender.id === user?.id
                  ? "ml-auto flex-row-reverse"
                  : "mr-auto"
              }`}
            >
              <Avatar
                fallback={message.sender.displayName?.[0] || "U"}
                size="sm"
                src={message.sender.avatarUrl || undefined}
              />
              <div
                className={`rounded-lg p-3 ${
                  message.sender.id === user?.id
                    ? "bg-primary text-white"
                    : "bg-default-100"
                }`}
              >
                <p className="text-xs mb-1">
                  {message.sender.id !== user?.id && (
                    <span className="font-medium">
                      {message.sender.displayName || "User"}
                    </span>
                  )}
                  <span className="text-xs opacity-70 ml-2">
                    {new Date(message.sentAt).toLocaleTimeString([], {
                      hour: "2-digit",
                      minute: "2-digit",
                    })}
                  </span>
                </p>
                <p>{message.content}</p>
              </div>
            </div>
          ))
        )}
        <div ref={messagesEndRef} />
      </div>

      {/* Message input */}
      <form className="p-4 border-t bg-background" onSubmit={handleSendMessage}>
        <div className="flex gap-2">
          <Input
            fullWidth
            disabled={sendingMessage}
            placeholder="Type a message..."
            value={newMessage}
            onChange={(e) => setNewMessage(e.target.value)}
          />
          <Button
            isIconOnly
            color="primary"
            isDisabled={!newMessage.trim() || sendingMessage}
            isLoading={sendingMessage}
            type="submit"
          >
            <LuSend />
          </Button>
        </div>
        {!isConnected && (
          <p className="text-xs text-yellow-500 mt-1">
            Live updates not connected. Messages might be delayed.
          </p>
        )}
      </form>
    </div>
  );
}
