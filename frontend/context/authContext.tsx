"use client";

import { createContext, useContext, useState, useEffect } from "react";
import { User } from "@/types/user";
import authService, {AuthResponse} from "@/services/authService";

type AuthContextType = {
    user: User | null;
    loading: boolean;
    error: string | null;
    login: (email: string, password: string) => Promise<AuthResponse>;
    register: (email: string, password: string, displayName?: string) => Promise<AuthResponse>;
    logout: () => Promise<void>;
};

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
    const [user, setUser] = useState<User | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const checkAuthStatus = async () => {
            try {
                setLoading(true);
                const userData = await authService.getCurrentUser();
                setUser(userData);
            } catch (err) {
                localStorage.removeItem("accessToken");
                localStorage.removeItem("refreshToken");
            } finally {
                setLoading(false);
            }
        };

        checkAuthStatus();
    }, []);

    const login = async (email: string, password: string) => {
        try {
            setLoading(true);
            setError(null);

            const response = await authService.login({ email, password });
            setUser(response.user);

            return response;
        } catch (err: any) {
            setError(err.response?.data?.message || "Failed to login");
            throw err;
        } finally {
            setLoading(false);
        }
    };

    const register = async (email: string, password: string, displayName?: string) => {
        try {
            setLoading(true);
            setError(null);

            const response = await authService.register({
                email,
                password,
                displayName
            });

            return response;
        } catch (err: any) {
            setError(err.response?.data?.message || "Registration failed");
            throw err;
        } finally {
            setLoading(false);
        }
    };

    const logout = async () => {
        try {
            setLoading(true);
            await authService.logout();
            setUser(null);
        } catch (err) {
            console.error("Logout failed:", err);
        } finally {
            setLoading(false);
        }
    };

    const value = {
        user,
        loading,
        error,
        login,
        register,
        logout
    };

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export const useAuth = () => {
    const context = useContext(AuthContext);
    if (context === undefined) {
        throw new Error("useAuth must be used within an AuthProvider");
    }
    return context;
};