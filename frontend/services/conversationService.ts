import axiosInstance from "@/config/axios";

class ConversationService {
  async getConversation(id: string): Promise<any> {
    try {
      const response = await axiosInstance.get(`/conversations/${id}`, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
        },
      });

      if (response.status !== 200) {
        throw new Error("Failed to fetch conversation");
      }

      return response.data;
    } catch (error) {
      console.error("Error fetching conversation:", error);
      throw error;
    }
  }

  async getMessagesOfAConversation(id: string): Promise<any> {
    try {
      const response = await axiosInstance.get(
        `/conversations/${id}/messages`,
        {
          method: "GET",
          headers: {
            "Content-Type": "application/json",
          },
        },
      );

      if (response.status !== 200) {
        throw new Error("Failed to fetch messages");
      }

      return response.data;
    } catch (error) {
      console.error("Error fetching messages:", error);
      throw error;
    }
  }
}

const conversationService = new ConversationService();

export default conversationService;
