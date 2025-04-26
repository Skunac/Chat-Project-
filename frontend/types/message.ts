export interface Message {}

export interface MessagePreview {
  content: string;
  senderName: string;
  sentAt: string;
}

export interface ConversationMessage {
  id: string;
  conversation: string;
  sender: {
    id: string;
    displayName: string;
    avatarUrl: string | null;
  };
  content: string;
  sentAt: string;
}

export interface MessageRequest {
  conversation: string;
  content: string;
}
