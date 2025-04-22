import { axiosInstance } from "@/config/axios";
import { User } from "@/types/user";

export interface AuthResponse {
    token: string;
    refreshToken?: string;
    user: User;
}

export interface LoginCredentials {
    email: string;
    password: string;
}

export interface RegisterCredentials {
    email: string;
    password: string;
    displayName?: string;
}

class AuthService {
    /**
     * Get the current authenticated user
     */
    async getCurrentUser(): Promise<User> {
        const response = await axiosInstance.get("/auth/me");
        return response.data.data;
    }

    /**
     * Log in a user with email and password
     */
    async login(credentials: LoginCredentials): Promise<AuthResponse> {
        const response = await axiosInstance.post("/auth/login", credentials);
        return response.data;
    }

    /**
     * Register a new user
     */
    async register(credentials: RegisterCredentials): Promise<AuthResponse> {
        const response = await axiosInstance.post("/auth/register", credentials);
        return response.data;
    }

    /**
     * Refresh the authentication token
     */
    async refreshToken(): Promise<AuthResponse> {
        const response = await axiosInstance.post("/auth/refresh");
        return response.data;
    }

    /**
     * Log out the current user
     */
    async logout(): Promise<void> {
        await axiosInstance.post("/auth/logout");
        localStorage.removeItem("accessToken");
        localStorage.removeItem("refreshToken");
    }
}

// Create a singleton instance
const authService = new AuthService();

export default authService;