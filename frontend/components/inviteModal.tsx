"use client";

import { useState, useEffect } from "react";
import {
  Modal,
  ModalContent,
  ModalHeader,
  ModalBody,
  ModalFooter,
} from "@heroui/modal";
import { Button } from "@heroui/button";
import { Input } from "@heroui/input";
import { LuUserPlus } from "react-icons/lu";

import axiosInstance from "@/config/axios";
import { User } from "@/types/user";

interface InviteUserModalProps {
  isOpen: boolean;
  onClose: () => void;
  conversationId: string;
  onSuccess: () => void;
}

export default function InviteUserModal({
  isOpen,
  onClose,
  conversationId,
  onSuccess,
}: InviteUserModalProps) {
  const [email, setEmail] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState("");

  // Fetch all users when the modal opens to have them ready for validation
  const [users, setUsers] = useState<User[]>([]);

  // Clear form when modal closes
  useEffect(() => {
    if (!isOpen) {
      setEmail("");
      setError("");
    }
  }, [isOpen]);

  // Load users when modal opens
  useEffect(() => {
    if (isOpen) {
      loadUsers();
    }
  }, [isOpen]);

  const loadUsers = async () => {
    try {
      const response = await axiosInstance.get("/users");

      if (response.data && Array.isArray(response.data)) {
        setUsers(response.data);
      }
    } catch (error) {
      console.error("Error loading users:", error);
    }
  };

  // Handle modal close and clear form
  const handleClose = () => {
    setEmail("");
    setError("");
    onClose();
  };

  const handleSubmit = async () => {
    if (!email.trim()) {
      setError("Please enter an email address");

      return;
    }

    try {
      setIsLoading(true);
      setError("");

      // Check if user exists
      const userExists = users.find(
        (user: User) => user.email.toLowerCase() === email.toLowerCase(),
      );

      console.log("User exists:", userExists);

      if (!userExists) {
        setError("User with this email doesn't exist");
        setIsLoading(false);

        return;
      }

      // Create participant
      const participantData = {
        user: `/api/users/${userExists.id}`,
        conversation: `/api/conversations/${conversationId}`,
        role: "MEMBER",
      };

      await axiosInstance.post("/conversation_participants", participantData);

      setEmail("");
      onSuccess();
      handleClose();
    } catch (error: any) {
      console.error("Invitation error:", error);
      setError(
        error?.response?.data?.detail ||
          error?.message ||
          "Failed to invite user. Please try again.",
      );
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <Modal
      classNames={{
        base: "bg-background border border-default-200",
      }}
      isOpen={isOpen}
      onClose={handleClose}
      onOpenChange={(open) => {
        if (!open) {
          handleClose();
        } else {
          loadUsers();
        }
      }}
    >
      <ModalContent>
        <ModalHeader className="flex flex-col gap-1">
          <div className="flex items-center gap-2">
            <LuUserPlus className="h-5 w-5" />
            <span>Invite User to Conversation</span>
          </div>
        </ModalHeader>
        <ModalBody>
          {error && (
            <div className="bg-danger-50 border border-danger-200 text-danger p-2 mb-3 rounded-md text-sm">
              {error}
            </div>
          )}

          <div>
            <label className="text-sm font-medium mb-1 block" htmlFor={"email"}>
              User Email
            </label>
            <Input
              fullWidth
              placeholder="Enter user email address"
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
            />
            <p className="text-xs text-default-500 mt-1">
              The user must already have an account in the system.
            </p>
          </div>
        </ModalBody>
        <ModalFooter>
          <Button variant="bordered" onPress={handleClose}>
            Cancel
          </Button>
          <Button
            color="primary"
            isDisabled={isLoading || !email.trim()}
            isLoading={isLoading}
            onPress={handleSubmit}
          >
            Invite
          </Button>
        </ModalFooter>
      </ModalContent>
    </Modal>
  );
}
