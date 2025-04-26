"use client";

import React, {
  createContext,
  useContext,
  useState,
  useEffect,
  ReactNode,
} from "react";
import { useRouter } from "next/navigation";

import { User } from "@/types/user";
import authService, {
  LoginCredentials,
  RegisterCredentials,
} from "@/services/authService";

type AuthState = {
  user: User | null;
  loadingInitial: boolean;
  validating: boolean;
  loading: boolean;
  error: string | null;
};

type AuthContextType = AuthState & {
  login: (credentials: LoginCredentials) => Promise<void>;
  register: (userData: RegisterCredentials) => Promise<void>;
  logout: () => Promise<void>;
  refreshUserData: () => Promise<User | null>;
  clearError: () => void;
  isAuthenticated: boolean;
};

const AuthContext = createContext<AuthContextType | undefined>(undefined);

const STORAGE_KEYS = {
  USER: "user",
  ACCESS_TOKEN: "accessToken",
  REFRESH_TOKEN: "refreshToken",
};

const getFromStorage = (key: string) => {
  if (typeof window === "undefined") return null;

  return localStorage.getItem(key);
};

const setInStorage = (key: string, value: string) => {
  if (typeof window === "undefined") return;
  localStorage.setItem(key, value);
};

const removeFromStorage = (key: string) => {
  if (typeof window === "undefined") return;
  localStorage.removeItem(key);
};

export const AuthProvider = ({ children }: { children: ReactNode }) => {
  const [state, setState] = useState<AuthState>({
    user: null,
    loadingInitial: true,
    validating: false,
    loading: false,
    error: null,
  });
  const router = useRouter();

  useEffect(() => {
    const initializeAuth = async () => {
      try {
        if (typeof window === "undefined") return;

        const storedUser = getFromStorage(STORAGE_KEYS.USER);
        const accessToken = getFromStorage(STORAGE_KEYS.ACCESS_TOKEN);

        let parsedUser: User | null = null;

        if (storedUser) {
          try {
            parsedUser = JSON.parse(storedUser);

            setState((prev) => ({
              ...prev,
              user: parsedUser,
              loadingInitial: false,
              validating: true,
            }));
          } catch (e) {
            console.error("Failed to parse stored user data:", e);
            setState((prev) => ({ ...prev, loadingInitial: false }));
          }
        } else {
          setState((prev) => ({ ...prev, loadingInitial: false }));
        }

        if (accessToken) {
          try {
            const freshUserData = await authService.getCurrentUser();

            setInStorage(STORAGE_KEYS.USER, JSON.stringify(freshUserData));

            setState((prev) => ({
              ...prev,
              user: freshUserData,
              validating: false,
            }));
          } catch (error) {
            console.error("Failed to validate user data:", error);

            setState((prev) => ({ ...prev, validating: false }));
          }
        }
      } catch (error) {
        console.error("Auth initialization error:", error);
        setState((prev) => ({
          ...prev,
          loadingInitial: false,
          validating: false,
        }));
      }
    };

    initializeAuth();
  }, []);

  const refreshUserData = async (): Promise<User | null> => {
    try {
      const accessToken = getFromStorage(STORAGE_KEYS.ACCESS_TOKEN);

      if (!accessToken) return null;

      setState((prev) => ({ ...prev, loading: true }));

      const userData = await authService.getCurrentUser();

      setInStorage(STORAGE_KEYS.USER, JSON.stringify(userData));

      setState((prev) => ({
        ...prev,
        user: userData,
        loading: false,
        error: null,
      }));

      return userData;
    } catch (error) {
      console.error("Failed to refresh user data:", error);
      setState((prev) => ({ ...prev, loading: false }));

      return null;
    }
  };

  const clearAuthState = () => {
    removeFromStorage(STORAGE_KEYS.USER);
    removeFromStorage(STORAGE_KEYS.ACCESS_TOKEN);
    removeFromStorage(STORAGE_KEYS.REFRESH_TOKEN);

    setState((prev) => ({
      ...prev,
      user: null,
      loading: false,
      error: null,
    }));
  };

  const handleLogin = async (credentials: LoginCredentials): Promise<void> => {
    try {
      setState((prev) => ({ ...prev, loading: true, error: null }));

      const response = await authService.login(credentials);

      setInStorage(STORAGE_KEYS.ACCESS_TOKEN, response.token);
      if (response.refresh_token) {
        setInStorage(STORAGE_KEYS.REFRESH_TOKEN, response.refresh_token);
      }

      const userData = await refreshUserData();

      if (!userData) {
        throw new Error("Failed to get user data after login");
      }

      router.push("/");
    } catch (error: any) {
      console.error("Login error:", error);
      setState((prev) => ({
        ...prev,
        error:
          error?.response?.data?.message || "Login failed. Please try again.",
        loading: false,
      }));
      throw error;
    }
  };

  const handleRegister = async (
    userData: RegisterCredentials,
  ): Promise<void> => {
    try {
      setState((prev) => ({ ...prev, loading: true, error: null }));

      const response = await authService.register(userData);

      setInStorage(STORAGE_KEYS.ACCESS_TOKEN, response.token);
      if (response.refresh_token) {
        setInStorage(STORAGE_KEYS.REFRESH_TOKEN, response.refresh_token);
      }

      const userProfile = await refreshUserData();

      if (!userProfile) {
        throw new Error("Failed to get user data after registration");
      }

      router.push("/");
    } catch (error: any) {
      console.error("Registration error:", error);
      setState((prev) => ({
        ...prev,
        error:
          error?.response?.data?.message ||
          "Registration failed. Please try again.",
        loading: false,
      }));
      throw error;
    }
  };

  const handleLogout = async (): Promise<void> => {
    try {
      setState((prev) => ({ ...prev, loading: true }));
    } finally {
      clearAuthState();
    }
  };

  const clearError = () => {
    setState((prev) => ({ ...prev, error: null }));
  };

  const getToken = (): string | null => {
    return getFromStorage(STORAGE_KEYS.ACCESS_TOKEN);
  };

  const isAuthenticated = !!state.user && !!getToken();

  const value = {
    ...state,
    login: handleLogin,
    register: handleRegister,
    logout: handleLogout,
    refreshUserData,
    clearError,
    isAuthenticated,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};

export const useAuth = () => {
  const context = useContext(AuthContext);

  if (context === undefined) {
    throw new Error("useAuth must be used within an AuthProvider");
  }

  return context;
};
