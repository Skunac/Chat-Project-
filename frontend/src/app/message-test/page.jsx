'use client';

import { useState, useEffect } from 'react';
import axios from 'axios';

// API configuration
const API_URL = 'http://localhost:8000/api';
const MERCURE_HUB_URL = 'http://localhost:9090/.well-known/mercure';

// Test credentials
const TEST_EMAIL = 'test2@test.fr';
const TEST_PASSWORD = 'Aa123456';

export default function MessageTest() {
    const [authToken, setAuthToken] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [loggedIn, setLoggedIn] = useState(false);
    const [conversations, setConversations] = useState([]);
    const [selectedConversation, setSelectedConversation] = useState(null);
    const [messages, setMessages] = useState([]);
    const [newMessage, setNewMessage] = useState('');
    const [eventSource, setEventSource] = useState(null);
    const [connectionStatus, setConnectionStatus] = useState('Disconnected');

    // Login automatically on component mount
    useEffect(() => {
        login();
    }, []);

    // Handle conversation selection
    useEffect(() => {
        if (selectedConversation) {
            fetchMessages(selectedConversation);
            subscribeToConversation(selectedConversation);
        }

        return () => {
            // Clean up event source when conversation changes
            if (eventSource) {
                eventSource.close();
                setConnectionStatus('Disconnected');
            }
        };
    }, [selectedConversation, authToken]);

    // Login function
    const login = async () => {
        setLoading(true);
        setError('');

        try {
            const response = await axios.post(`${API_URL}/auth/login`, {
                email: TEST_EMAIL,
                password: TEST_PASSWORD
            });

            setAuthToken(response.data.token);
            setLoggedIn(true);
            setLoading(false);

            // Fetch conversations after login
            fetchConversations(response.data.token);
        } catch (err) {
            setError('Login failed: ' + (err.response?.data?.message || err.message));
            setLoading(false);
        }
    };

    // Fetch user's conversations
    const fetchConversations = async (token) => {
        try {
            const response = await axios.get(`${API_URL}/conversations`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });

            setConversations(response.data['member'] || []);
        } catch (err) {
            setError('Failed to fetch conversations: ' + (err.response?.data?.message || err.message));
        }
    };

    // Fetch messages for a conversation
    const fetchMessages = async (conversationId) => {
        try {
            const response = await axios.get(`${API_URL}/conversations/${conversationId}/messages`, {
                headers: {
                    'Authorization': `Bearer ${authToken}`
                }
            });

            // The response has a "member" array containing the messages
            console.log(response.data.member);
            setMessages(response.data.member || []);
        } catch (err) {
            setError('Failed to fetch messages: ' + (err.response?.data?.message || err.message));
        }
    };

    // Subscribe to Mercure updates for a conversation
    const subscribeToConversation = (conversationId) => {
        // Close existing connection if any
        if (eventSource) {
            eventSource.close();
        }

        setConnectionStatus('Connecting...');

        try {
            // Create URL with topic - using the same format that worked before
            const url = new URL(MERCURE_HUB_URL);
            //url.searchParams.append('topic', 'chat/test32');
            // Also try the conversation-specific topic
            url.searchParams.append('topic', `conversation/${conversationId}`);

            console.log(`[${TEST_EMAIL}] Connecting to Mercure at:`, url.toString());

            // Create EventSource - without credentials since that worked before
            const newEventSource = new EventSource(url.toString());

            // Handle connection events
            newEventSource.onopen = () => {
                console.log(`[${TEST_EMAIL}] Connected to Mercure`);
                setConnectionStatus('Connected');
            };

            newEventSource.onerror = (err) => {
                console.error(`[${TEST_EMAIL}] Mercure connection error:`, err);
                setConnectionStatus('Connection error');
            };

            // Handle incoming messages - using the format from your working demo
            newEventSource.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    console.log(`[${TEST_EMAIL}] Received message:`, data);

                    // First try to handle the format from your working example
                    if (data.message) {
                        setMessages(prev => [
                            ...prev,
                            {
                                id: Date.now().toString(),
                                sender: data.sender?.username || data.sender || 'Anonymous',
                                content: data.message,
                                timestamp: data.timestamp || new Date().toISOString(),
                                sentAt: data.timestamp || new Date().toISOString()
                            }
                        ]);
                    }
                    // Then try to handle the API format
                    else if (data.id && data.content) {
                        // Add message to list if it's not already there
                        setMessages(prevMessages => {
                            if (prevMessages.some(msg => msg.id === data.id)) {
                                return prevMessages;
                            }

                            return [...prevMessages, data];
                        });
                    }
                } catch (err) {
                    console.error(`[${TEST_EMAIL}] Error parsing message:`, err);
                }
            };

            setEventSource(newEventSource);
        } catch (error) {
            console.error(`[${TEST_EMAIL}] Failed to connect:`, error);
            setConnectionStatus('Setup failed');
        }
    };

    // Send a new message
    const sendMessage = async (e) => {
        e.preventDefault();
        if (!newMessage.trim() || !selectedConversation) return;

        try {
            await axios.post(`${API_URL}/messages`, {
                conversation: `/api/conversations/${selectedConversation}`,
                content: newMessage,
                metadata: {"type": "text"}
            }, {
                headers: {
                    'Content-Type': 'application/ld+json',
                    'Authorization': `Bearer ${authToken}`
                }
            });

            setNewMessage('');
            // The new message should come in via Mercure, but we could also add it directly
        } catch (error) {
            setError('Failed to send message: ' + (error.response?.data?.message || error.message));
        }
    };

    // Handle direct Mercure test
    const testMercure = async () => {
        try {
            await axios.post(`${API_URL}/mercure-test/publish`, {
                topic: `conversation/${selectedConversation}`,
                message: "Test message via Mercure controller",
                sender: "System"
            }, {
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${authToken}`
                }
            });
        } catch (error) {
            setError('Failed to send test message: ' + (error.response?.data?.message || error.message));
        }
    };

    if (loading) {
        return <div className="p-4">Loading...</div>;
    }

    if (!loggedIn) {
        return (
            <div className="p-4">
                <h1 className="text-2xl mb-4">Message Testing</h1>
                {error && <div className="bg-red-100 p-3 mb-4 text-red-700 rounded">{error}</div>}
                <button
                    onClick={login}
                    className="bg-blue-500 text-white px-4 py-2 rounded"
                >
                    Login as test2@test.fr
                </button>
            </div>
        );
    }

    return (
        <div className="p-4 max-w-4xl mx-auto">
            <h1 className="text-2xl mb-4">Message Testing</h1>
            {error && <div className="bg-red-100 p-3 mb-4 text-red-700 rounded">{error}</div>}

            <div className="mb-4">
                <p>Logged in as: {TEST_EMAIL}</p>
                <p>Token: <span className="text-xs text-gray-500">{authToken.substring(0, 20)}...</span></p>
                <p>Mercure status: <span className={connectionStatus === 'Connected' ? 'text-green-600' : 'text-orange-500'}>{connectionStatus}</span></p>
            </div>

            <div className="grid grid-cols-3 gap-4">
                <div className="col-span-1 border p-3 rounded">
                    <h2 className="font-bold mb-2">Conversations</h2>
                    {conversations.length === 0 ? (
                        <p className="text-gray-500">No conversations found</p>
                    ) : (
                        <ul>
                            {conversations.map(conv => (
                                <li
                                    key={conv.id}
                                    className={`p-2 my-1 rounded cursor-pointer ${selectedConversation === conv.id ? 'bg-blue-100' : 'hover:bg-gray-100'}`}
                                    onClick={() => setSelectedConversation(conv.id)}
                                >
                                    {conv.name || `Conversation ${conv.id.substring(0, 8)}`}
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                <div className="col-span-2 border rounded">
                    {selectedConversation ? (
                        <>
                            <div className="p-3 border-b">
                                <h2 className="font-bold">Messages</h2>
                                <div className="flex mt-2">
                                    <button
                                        onClick={() => fetchMessages(selectedConversation)}
                                        className="bg-gray-200 px-3 py-1 rounded mr-2 text-sm"
                                    >
                                        Refresh
                                    </button>
                                    <button
                                        onClick={testMercure}
                                        className="bg-purple-200 px-3 py-1 rounded text-sm"
                                    >
                                        Send Test Mercure Message
                                    </button>
                                </div>
                            </div>

                            <div className="h-64 overflow-y-auto p-3">
                                {messages.length === 0 ? (
                                    <p className="text-gray-500">No messages yet</p>
                                ) : (
                                    messages.map(msg => (
                                        <div key={msg.id} className="mb-3 p-2 border-b">
                                            <div className="font-semibold">{msg.sender?.displayName || 'Unknown'}</div>
                                            <div className="my-1">{msg.content}</div>
                                            <div className="text-xs text-gray-500">
                                                {new Date(msg.sentAt).toLocaleString()}
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>

                            <div className="p-3 border-t">
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
                        </>
                    ) : (
                        <div className="p-4 text-center text-gray-500">
                            Select a conversation to view messages
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}