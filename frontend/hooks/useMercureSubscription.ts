"use client";

import { useEffect, useState, useRef, useCallback } from "react";

type MercureOptions = {
  topics: string[]; // Keeping array to match your current implementation
  onMessage?: (data: any) => void;
};

export function useMercureSubscription<T>({
  topics,
  onMessage,
}: MercureOptions) {
  const [data, setData] = useState<T | null>(null);
  const [isConnected, setIsConnected] = useState(false);
  const eventSourceRef = useRef<EventSource | null>(null);

  // Cleanup function to close EventSource
  const cleanupEventSource = useCallback(() => {
    if (eventSourceRef.current) {
      console.log("Closing Mercure connection");
      eventSourceRef.current.close();
      eventSourceRef.current = null;
    }
  }, []);

  useEffect(() => {
    // Don't continue if no topics are provided
    if (!topics.length || !process.env.NEXT_PUBLIC_MERCURE_HUB_URL) {
      setIsConnected(false);

      return cleanupEventSource();
    }

    // Clean up existing connection
    cleanupEventSource();

    try {
      // Create URL with topic
      const url = new URL(process.env.NEXT_PUBLIC_MERCURE_HUB_URL);

      // Add each topic as a separate parameter
      topics.forEach((topic) => {
        url.searchParams.append("topic", topic);
      });

      console.log("Connecting to Mercure at:", url.toString());

      // Create a new EventSource WITHOUT withCredentials
      const eventSource = new EventSource(url.toString());

      // Handle connection open
      eventSource.onopen = () => {
        console.log("Mercure connection established");
        setIsConnected(true);
      };

      // Handle incoming messages
      eventSource.onmessage = (event) => {
        try {
          const parsedData = JSON.parse(event.data);

          console.log("Received Mercure message:", parsedData);

          // Update internal state
          setData(parsedData);

          // Call the onMessage callback if provided
          if (onMessage) {
            onMessage(parsedData);
          }
        } catch (error) {
          console.error("Error parsing Mercure message:", error);
        }
      };

      // Handle errors
      eventSource.onerror = (error) => {
        console.error("Mercure connection error:", error);
        setIsConnected(false);

        // Don't reconnect here as it can cause infinite reconnection loops
      };

      // Store the reference
      eventSourceRef.current = eventSource;

      // Clean up on unmount
      return () => {
        cleanupEventSource();
      };
    } catch (error) {
      console.error("Failed to setup Mercure connection:", error);
      setIsConnected(false);

      return () => {}; // Return empty cleanup function
    }
  }, [topics.join(","), onMessage, cleanupEventSource]); // Only depend on topics string, not array

  return { data, isConnected };
}
