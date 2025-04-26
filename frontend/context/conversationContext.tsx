"use client";

import React, { createContext, useContext, useState, ReactNode } from "react";

type ConversationContextType = {
  activeConversationId: string | null;
  setActiveConversation: (conversationId: string) => void;
  clearActiveConversation: () => void;
};

const ConversationContext = createContext<ConversationContextType | undefined>(
  undefined,
);

export const ConversationProvider = ({ children }: { children: ReactNode }) => {
  const [activeConversationId, setActiveConversationId] = useState<
    string | null
  >(null);

  const setActiveConversation = (conversationId: string) => {
    setActiveConversationId(conversationId);
  };

  const clearActiveConversation = () => {
    setActiveConversationId(null);
  };

  return (
    <ConversationContext.Provider
      value={{
        activeConversationId,
        setActiveConversation,
        clearActiveConversation,
      }}
    >
      {children}
    </ConversationContext.Provider>
  );
};

export const useConversation = () => {
  const context = useContext(ConversationContext);

  if (context === undefined) {
    throw new Error(
      "useConversation must be used within a ConversationProvider",
    );
  }

  return context;
};
