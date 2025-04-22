import React, { useState } from 'react';
import { useAuth } from '@/context/authContext';
import { Card } from '@heroui/card';
import { Button } from '@heroui/button';
import { Link } from '@heroui/link';
import { Avatar } from '@heroui/avatar';
import { Divider } from '@heroui/divider';
import { Input } from '@heroui/input';
import {
    LuSearch,
    LuPlus,
    LuUsers,
    LuUser,
    LuMessageSquare,
    LuSettings,
    LuLogOut,
    LuRefreshCw
} from 'react-icons/lu';
import { Dropdown, DropdownTrigger, DropdownMenu, DropdownItem } from '@heroui/dropdown';

export default function Sidebar() {
    const { user, loadingInitial, validating, loading, logout, refreshUserData } = useAuth();
    const [searchQuery, setSearchQuery] = useState('');

    // Mock conversation data for demonstration
    const conversations = [
        {
            id: '1',
            name: 'General Chat',
            lastMessage: 'Latest project updates',
            unreadCount: 3,
            isGroup: true
        },
        {
            id: '2',
            name: 'Sarah Johnson',
            lastMessage: 'Are you available for a call?',
            unreadCount: 0,
            isGroup: false
        },
        {
            id: '3',
            name: 'Development Team',
            lastMessage: 'New release scheduled for Friday',
            unreadCount: 5,
            isGroup: true
        }
    ];

    // Filter conversations based on search query
    const filteredConversations = searchQuery
        ? conversations.filter(
            conversation => conversation.name.toLowerCase().includes(searchQuery.toLowerCase())
        )
        : conversations;

    const handleLogout = async () => {
        try {
            await logout();
        } catch (error) {
            console.error('Logout failed:', error);
        }
    };

    const handleRefreshData = async () => {
        try {
            await refreshUserData();
        } catch (error) {
            console.error('Failed to refresh user data:', error);
        }
    };

    // Show loading state for initial load
    if (loadingInitial) {
        return (
            <div className="w-72 h-full bg-background border-r flex flex-col p-4">
                <div className="animate-pulse space-y-4">
                    <div className="flex items-center space-x-3">
                        <div className="rounded-full bg-default-200 h-10 w-10"></div>
                        <div className="flex-1">
                            <div className="h-4 bg-default-200 rounded w-3/4 mb-2"></div>
                            <div className="h-3 bg-default-200 rounded w-1/2"></div>
                        </div>
                    </div>
                    <Divider />
                    <div className="h-8 bg-default-200 rounded w-full"></div>
                    <div className="space-y-2">
                        <div className="h-4 bg-default-200 rounded w-1/3"></div>
                        <div className="h-14 bg-default-200 rounded w-full"></div>
                        <div className="h-14 bg-default-200 rounded w-full"></div>
                        <div className="h-14 bg-default-200 rounded w-full"></div>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="w-72 h-full bg-background border-r flex flex-col">
            {/* User Profile Section */}
            <div className="p-4">
                {user ? (
                    <div className="flex items-center justify-between">
                        <div className="flex items-center space-x-3">
                            <div className="relative">
                                <Avatar
                                    src={user.avatarUrl}
                                    fallback={user.displayName?.[0] || user.email[0].toUpperCase()}
                                    className="h-10 w-10"
                                />
                                {validating && (
                                    <div className="absolute -bottom-1 -right-1 bg-default-100 rounded-full p-0.5">
                                        <LuRefreshCw className="h-3 w-3 text-default-500 animate-spin" />
                                    </div>
                                )}
                            </div>
                            <div className="flex-1 min-w-0">
                                <p className="text-sm font-medium truncate">
                                    {user.displayName || 'User'}
                                </p>
                                <p className="text-xs text-default-500 truncate">
                                    {user.email}
                                </p>
                            </div>
                        </div>

                        <Dropdown>
                            <DropdownTrigger>
                                <Button isIconOnly size="sm" variant="light">
                                    <LuSettings className="h-4 w-4" />
                                </Button>
                            </DropdownTrigger>
                            <DropdownMenu aria-label="User actions">
                                <DropdownItem key="profile">Profile settings</DropdownItem>
                                <DropdownItem
                                    key="refresh"
                                    startContent={<LuRefreshCw className="h-4 w-4" />}
                                    onClick={handleRefreshData}
                                    isDisabled={loading || validating}
                                >
                                    Refresh data
                                </DropdownItem>
                                <DropdownItem
                                    key="logout"
                                    className="text-danger"
                                    color="danger"
                                    startContent={<LuLogOut className="h-4 w-4" />}
                                    onClick={handleLogout}
                                    isDisabled={loading}
                                >
                                    Logout
                                </DropdownItem>
                            </DropdownMenu>
                        </Dropdown>
                    </div>
                ) : (
                    <Card className="p-3 bg-default-50">
                        <h3 className="text-sm font-medium mb-2">Not signed in</h3>
                        <Link href="/auth/login">
                            <Button size="sm" className="w-full" color="primary">
                                Sign In
                            </Button>
                        </Link>
                    </Card>
                )}
            </div>

            <Divider />

            {/* Only show conversations section if user is logged in */}
            {user && (
                <>
                    {/* Conversations search */}
                    <div className="p-3">
                        <Input
                            placeholder="Search conversations..."
                            size="sm"
                            startContent={<LuSearch className="text-default-400 h-4 w-4" />}
                            type="search"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            classNames={{
                                inputWrapper: "bg-default-100"
                            }}
                        />
                    </div>

                    {/* Conversations list */}
                    <div className="flex-1 overflow-y-auto px-3">
                        <div className="flex items-center justify-between mb-2">
                            <h2 className="text-xs font-semibold text-default-600">Conversations</h2>
                            <Button isIconOnly size="sm" variant="light">
                                <LuPlus className="h-4 w-4" />
                            </Button>
                        </div>

                        {filteredConversations.length > 0 ? (
                            <div className="space-y-1">
                                {filteredConversations.map(conversation => (
                                    <div
                                        key={conversation.id}
                                        className="flex items-center gap-3 p-2 rounded-lg cursor-pointer hover:bg-default-100"
                                    >
                                        <Avatar
                                            size="sm"
                                            fallback={
                                                conversation.isGroup
                                                    ? <LuUsers className="h-4 w-4" />
                                                    : conversation.name[0]
                                            }
                                        />

                                        <div className="flex-1 min-w-0">
                                            <div className="flex justify-between items-center">
                                                <p className="text-sm font-medium truncate">
                                                    {conversation.name}
                                                </p>
                                                {conversation.unreadCount > 0 && (
                                                    <span className="inline-flex items-center justify-center w-5 h-5 text-xs font-medium bg-primary text-white rounded-full">
                            {conversation.unreadCount}
                          </span>
                                                )}
                                            </div>
                                            <p className="text-xs text-default-500 truncate">
                                                {conversation.lastMessage}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-center py-8 text-default-400">
                                <LuMessageSquare className="mx-auto h-8 w-8 mb-2" />
                                <p className="text-sm">No conversations found</p>
                            </div>
                        )}
                    </div>

                    {/* Create new conversation button */}
                    <div className="p-3 mt-auto">
                        <Button
                            className="w-full"
                            color="primary"
                            size="sm"
                            startContent={<LuPlus className="h-4 w-4" />}
                        >
                            New Conversation
                        </Button>
                    </div>
                </>
            )}
        </div>
    );
}