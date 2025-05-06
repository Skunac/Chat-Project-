import axiosInstance from "@/config/axios";
import { ConversationMessage } from "@/types/message";

class MessageService {
  /**
   * Send a new message
   */
  async sendMessage(
    conversationId: string,
    content: string,
  ): Promise<ConversationMessage> {
    try {
      const response = await axiosInstance.post("/messages", {
        conversation: `/api/conversations/${conversationId}`,
        content,
      });

      if (response.status !== 201) {
        throw new Error("Failed to send message");
      }

      return response.data;
    } catch (error) {
      console.error("Error sending message:", error);
      throw error;
    }
  }
}

const messageService = new MessageService();

export default messageService;
