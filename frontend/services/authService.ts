import { axiosInstance } from "@/config/axios";
import { User } from "@/types/user";

export interface TokenResponse {
  token: string;
  refresh_token?: string;
}

export interface GoogleLoginResponse {
  redirect_url: string;
}

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface RegisterCredentials {
  email: string;
  password: string;
  displayName?: string;
  avatarUrl?: string;
}

/**
 * Service for handling authentication-related API calls
 */
class AuthService {
  /**
   * Get the current authenticated user's data
   */
  async getCurrentUser(): Promise<User> {
    const response = await axiosInstance.get("/auth/me");

    return response.data.data;
  }

  /**
   * Login with email and password
   * Only returns tokens, not user data
   */
  async login(credentials: LoginCredentials): Promise<TokenResponse> {
    const response = await axiosInstance.post("/auth/login", credentials);

    return {
      token: response.data.token,
      refresh_token: response.data.refresh_token,
    };
  }

  /**
   * Process Google OAuth callback code
   */
  async handleGoogleCallback(code: string): Promise<TokenResponse> {
    const response = await axiosInstance.get(
      `/auth/google/callback?code=${code}`,
    );

    return {
      token: response.data.data.token,
      refresh_token: response.data.data.refresh_token,
    };
  }

  /**
   * Register a new user
   * Only returns tokens, not user data
   */
  async register(userData: RegisterCredentials): Promise<TokenResponse> {
    const response = await axiosInstance.post("/auth/register", userData);

    return {
      token: response.data.data.token,
      refresh_token: response.data.data.refresh_token,
    };
  }

  /**
   * Refresh the authentication token
   */
  async refreshToken(refreshToken: string): Promise<TokenResponse> {
    const response = await axiosInstance.post("/auth/refresh", {
      refresh_token: refreshToken,
    });

    return {
      token: response.data.token,
      refresh_token: response.data.refresh_token,
    };
  }
}

// Create and export a singleton instance
const authService = new AuthService();

export default authService;
