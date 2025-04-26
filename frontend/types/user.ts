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

export interface ApiPlatformUser {
  "@context": string;
  "@id": string;
  "@type": string;
  email: string;
  displayName: string;
  avatarUrl: string | null;
  lastSeen: Date;
}
