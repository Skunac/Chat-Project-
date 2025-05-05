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

  async createConversation(
    name: string,
    avatarUrl: string,
    participants?: string[],
  ): Promise<any> {
    try {
      const response = await axiosInstance.post("/conversations", {
        name,
        avatarUrl,
        participants,
      });

      if (response.status !== 201) {
        throw new Error("Failed to create conversation");
      }

      return response.data;
    } catch (error) {
      console.error("Error creating conversation:", error);
      throw error;
    }
  }

  async addParticipantToConversation(
    conversationId: string,
    email: string,
  ): Promise<any> {
    try {
      const response = await axiosInstance.get(`/users?email=${email}`);

      if (!response.data || response.data.length === 0) {
        throw new Error("User not found");
      }

      const userId = response.data[0].id;

      const participant = {
        user: `/api/users/${userId}`,
        conversation: `/api/conversations/${conversationId}`,
        role: "MEMBER",
      };

      const participantResponse = await axiosInstance.post(
        "/conversation_participants",
        participant,
      );

      if (participantResponse.status !== 201) {
        throw new Error("Failed to add participant");
      }

      return participantResponse.data;
    } catch (error) {
      console.error("Error adding participant:", error);
      throw error;
    }
  }
}

const conversationService = new ConversationService();

export default conversationService;
