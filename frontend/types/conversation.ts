import { MessagePreview } from "@/types/message";

export interface Conversation {}

export interface ConversationPreview {
  id: string;
  name: string;
  avatarUrl: string | null;
  createdAt: string;
  updatedAt: string;
  role: string;
  lastMessage: MessagePreview | null;
}
