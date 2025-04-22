import axios from "axios";

const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000/api";

export const axiosInstance = axios.create({
    baseURL: API_URL,
    headers: {
        "Content-Type": "application/json",
    },
});

// Add auth token to requests
axiosInstance.interceptors.request.use(
    (config) => {
        if (typeof window !== "undefined") {
            const token = localStorage.getItem("accessToken");
            if (token) {
                config.headers.Authorization = `Bearer ${token}`;
            }
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Store tokens from responses and handle token refresh
axiosInstance.interceptors.response.use(
    (response) => {
        // Handle nested data structure if it exists
        if (response.data?.data) {
            if (response.data.data.token) {
                localStorage.setItem("accessToken", response.data.data.token);
                axiosInstance.defaults.headers.common["Authorization"] = `Bearer ${response.data.data.token}`;
            }

            if (response.data.data.refresh_token) {
                localStorage.setItem("refreshToken", response.data.data.refresh_token);
            }
        }

        return response;
    },
    async (error) => {
        const originalRequest = error.config;
        const refreshToken = localStorage.getItem("refreshToken");

        // Handle 401 errors by attempting to refresh the token
        if (error.response?.status === 401 && !originalRequest._retry && refreshToken) {
            originalRequest._retry = true;

            try {
                const response = await axiosInstance.post("/auth/refresh");
                const newToken = response.data.token || (response.data.data && response.data.data.token);
                const newRefreshToken = response.data.refreshToken || (response.data.data && response.data.data.refreshToken);

                if (newToken) {
                    // Update localStorage and headers with new tokens
                    localStorage.setItem("accessToken", newToken);
                    axiosInstance.defaults.headers.common["Authorization"] = `Bearer ${newToken}`;

                    if (newRefreshToken) {
                        localStorage.setItem("refreshToken", newRefreshToken);
                    }

                    // Retry the original request with the new token
                    originalRequest.headers.Authorization = `Bearer ${newToken}`;
                    return axiosInstance(originalRequest);
                }
            } catch (refreshError) {
                // If refresh fails, clear tokens and redirect to login
                if (typeof window !== "undefined") {
                    localStorage.removeItem("accessToken");
                    localStorage.removeItem("refreshToken");
                    window.location.href = "/auth/login?session=expired";
                }
                return Promise.reject(refreshError);
            }
        }

        return Promise.reject(error);
    }
);