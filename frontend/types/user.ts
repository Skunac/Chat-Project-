import { ConversationPreview } from "@/types/conversation";

export interface User {
  id: string;
  email: string;
  displayName: string;
  avatarUrl: string;
  roles: string[];
  isVerified: boolean;
  lastSeen: Date;
  conversations: ConversationPreview[] | null;
}
