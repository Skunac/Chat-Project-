'use client';

import { useState, useEffect } from 'react';
import axios from 'axios';

// Simple types for TypeScript
interface Message {
  id: string;
  sender: string;
  content: string;
  timestamp: string;
}

export default function MercureDemo() {
  const [username, setUsername] = useState('');
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const [messages, setMessages] = useState<Message[]>([]);
  const [newMessage, setNewMessage] = useState('');
  const [connectionStatus, setConnectionStatus] = useState('Disconnected');

  // API URLs
  const apiUrl = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';
  const mercureUrl = process.env.NEXT_PUBLIC_MERCURE_HUB_URL || 'http://localhost:9090/.well-known/mercure';

  // JWT token
  const token = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE3NDE0NzMyMDYsImV4cCI6MTc0MTQ3NjgwNiwicm9sZXMiOlsiUk9MRV9VU0VSIl0sInVzZXJuYW1lIjoidGVzdDJAdGVzdC5mciJ9.KRFwwDmkLSqSKT17Aw9xFqGDBALVsSGLLNhzUaQvsBsRXOX27GWs1nDHDL4YsSPa-sxqBJpUXGlJgJ7QX56v3UIkjrF53GOW9WYZnyPWC9i2qbrbb_h8lfKbu0mxURkSsKrhxXrPt6n99Dhd3bto5tDFG9xlVvzWM-QnpN_S92FhWMQ3gz3Deb0DsIOw3mw-BGB_s9Ua-ckIYeHYI5syh3m-qC3uSW6MmuuR9qALgDJAMCiv6ftM8JtpOp5f2Et6sWjcBfIEg7dQ_O4N2nNLIH8r-M0ERfAbGAaeEbriip3v1vOvNNskALCebl1QqJr0FuXDoH12B0gxzg7VRdu7Cw';

  // Connect to Mercure
  useEffect(() => {
    if (!isLoggedIn) return;

    setConnectionStatus('Connecting...');

    try {
      // Create URL with topic
      const url = new URL(mercureUrl);
      url.searchParams.append('topic', 'chat/test32');

      console.log(`[${username}] Connecting to Mercure at:`, url.toString());

      // Create EventSource
      const eventSource = new EventSource(url.toString());

      // Handle connection events
      eventSource.onopen = () => {
        console.log(`[${username}] Connected to Mercure`);
        setConnectionStatus('Connected');
      };

      eventSource.onerror = () => {
        console.error(`[${username}] Mercure connection error`);
        setConnectionStatus('Connection error');
      };

      // Handle incoming messages
      eventSource.onmessage = (event) => {
        try {
          const data = JSON.parse(event.data);
          console.log(`[${username}] Received message:`, data);

          // Add message to list
          setMessages(prev => [
            ...prev,
            {
              id: Date.now().toString(),
              sender: data.sender || 'Anonymous',
              content: data.message,
              timestamp: data.timestamp
            }
          ]);
        } catch (err) {
          console.error(`[${username}] Error parsing message:`, err);
        }
      };

      // Cleanup on unmount
      return () => {
        eventSource.close();
        setConnectionStatus('Disconnected');
      };
    } catch (error) {
      console.error(`[${username}] Failed to connect:`, error);
      setConnectionStatus('Setup failed');
    }
  }, [isLoggedIn, username, mercureUrl]);

  // Handle login
  const handleLogin = (e) => {
    e.preventDefault();
    if (!username.trim()) return;
    setIsLoggedIn(true);
  };

  // Send a message
  const sendMessage = async (e) => {
    e.preventDefault();
    if (!newMessage.trim() || !isLoggedIn) return;

    try {
      await axios.post(`${apiUrl}/mercure-test/publish`, {
        topic: 'chat/test32',
        message: newMessage,
        sender: username
      }, {
        headers: {
          'Content-Type': 'application/json',
          'Authorization': token
        }
      });

      setNewMessage('');
    } catch (error) {
      console.error('Failed to send message:', error);
      alert('Failed to send message');
    }
  };

  // Login form
  if (!isLoggedIn) {
    return (
        <div className="max-w-md mx-auto p-4">
          <h1 className="text-xl font-bold mb-4">Simple Mercure Chat</h1>
          <form onSubmit={handleLogin}>
            <input
                type="text"
                value={username}
                onChange={(e) => setUsername(e.target.value)}
                className="w-full p-2 border rounded mb-2"
                placeholder="Enter your username"
                required
            />
            <button
                type="submit"
                className="w-full bg-blue-500 text-white p-2 rounded"
            >
              Join Chat
            </button>
          </form>
        </div>
    );
  }

  // Chat interface
  return (
      <div className="max-w-md mx-auto p-4">
        <div className="flex justify-between mb-4">
          <h1 className="text-xl font-bold">Mercure Chat</h1>
          <div>Status: {connectionStatus}</div>
        </div>

        <div className="border rounded p-4 h-80 overflow-y-auto mb-4 bg-gray-50">
          {messages.length === 0 ? (
              <div className="text-gray-400 text-center">No messages yet</div>
          ) : (
              messages.map((msg) => (
                  <div key={msg.id} className="mb-2 p-2 border-b">
                    <div className="font-bold text-gray-700">{msg.sender}</div>
                    <div className="text-gray-600">{msg.content}</div>
                    <div className="text-xs text-gray-500">
                      {new Date(msg.timestamp).toLocaleTimeString()}
                    </div>
                  </div>
              ))
          )}
        </div>

        <form onSubmit={sendMessage} className="flex gap-2">
          <input
              type="text"
              value={newMessage}
              onChange={(e) => setNewMessage(e.target.value)}
              className="flex-1 p-2 border rounded"
              placeholder="Type a message..."
          />
          <button
              type="submit"
              className="bg-blue-500 text-white px-4 py-2 rounded"
          >
            Send
          </button>
        </form>
      </div>
  );
}