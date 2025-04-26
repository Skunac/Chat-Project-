import { MessagePreview } from "@/types/message";
import { ApiPlatformUser } from "@/types/user";

export interface Conversation {
  id: string;
  name: string;
  avatarUrl: string | null;
  createdAt: string;
  updatedAt: string;
  creator: ApiPlatformUser;
  participants: Participant[];
}

export interface Participant {
  id: string;
  user: ApiPlatformUser;
  role: string;
  joinedAt: string;
}

export interface ConversationPreview {
  id: string;
  name: string;
  avatarUrl: string | null;
  createdAt: string;
  updatedAt: string;
  role: string;
  lastMessage: MessagePreview | null;
}
