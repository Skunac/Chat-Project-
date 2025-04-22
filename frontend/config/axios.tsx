"use client";

import axios, { AxiosRequestConfig, AxiosResponse, AxiosError } from 'axios';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

const STORAGE_KEYS = {
    USER: 'user',
    ACCESS_TOKEN: 'accessToken',
    REFRESH_TOKEN: 'refreshToken',
};

const getFromStorage = (key: string): string | null => {
    if (typeof window === 'undefined') return null;
    return localStorage.getItem(key);
};

const setInStorage = (key: string, value: string): void => {
    if (typeof window === 'undefined') return;
    localStorage.setItem(key, value);
};

const removeFromStorage = (key: string): void => {
    if (typeof window === 'undefined') return;
    localStorage.removeItem(key);
};

export const axiosInstance = axios.create({
    baseURL: API_URL,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    timeout: 10000, // 10 seconds timeout
});

let isRefreshing = false;
let refreshSubscribers: ((token: string) => void)[] = [];

/**
 * Function to add a callback to the refresh subscribers
 */
function subscribeTokenRefresh(callback: (token: string) => void) {
    refreshSubscribers.push(callback);
}

/**
 * Function to notify all subscribers that token has been refreshed
 */
function onTokenRefreshed(token: string) {
    refreshSubscribers.forEach(callback => callback(token));
    refreshSubscribers = [];
}

/**
 * Function to reject all subscribers when token refresh fails
 */
function onRefreshError(error: any) {
    refreshSubscribers.forEach(callback => callback(''));
    refreshSubscribers = [];

    if (typeof window !== 'undefined') {
        removeFromStorage(STORAGE_KEYS.USER);
        removeFromStorage(STORAGE_KEYS.ACCESS_TOKEN);
        removeFromStorage(STORAGE_KEYS.REFRESH_TOKEN);

        window.location.href = '/auth/login?session=expired';
    }
}

axiosInstance.interceptors.request.use(
    (config) => {
        if (typeof window !== 'undefined') {
            const token = getFromStorage(STORAGE_KEYS.ACCESS_TOKEN);
            if (token && config.headers) {
                config.headers.Authorization = `Bearer ${token}`;
            }
        }
        return config;
    },
    (error: AxiosError) => {
        return Promise.reject(error);
    }
);

if (typeof window !== 'undefined') {
    axiosInstance.interceptors.response.use(
        (response: AxiosResponse) => {
            return response;
        },
        async (error: AxiosError) => {
            const originalRequest = error.config as AxiosRequestConfig & { _retry?: boolean };

            if (error.response?.status === 401 && !originalRequest._retry) {
                if (isRefreshing) {
                    try {
                        const newToken = await new Promise<string>((resolve, reject) => {
                            subscribeTokenRefresh((token: string) => {
                                if (token === '') {
                                    reject(new Error('Token refresh failed'));
                                } else {
                                    resolve(token);
                                }
                            });
                        });

                        if (originalRequest.headers) {
                            originalRequest.headers.Authorization = `Bearer ${newToken}`;
                        }

                        return axiosInstance(originalRequest);
                    } catch (refreshError) {
                        return Promise.reject(refreshError);
                    }
                }

                originalRequest._retry = true;
                isRefreshing = true;

                try {
                    const refreshToken = getFromStorage(STORAGE_KEYS.REFRESH_TOKEN);
                    if (!refreshToken) {
                        throw new Error('No refresh token available');
                    }

                    const response = await axios.post(`${API_URL}/auth/refresh`, {
                        refresh_token: refreshToken
                    });

                    let newToken = '';
                    let newRefreshToken = '';

                    if (response.data.data) {
                        newToken = response.data.data.token;
                        newRefreshToken = response.data.data.refresh_token;
                    } else {
                        newToken = response.data.token;
                        newRefreshToken = response.data.refresh_token;
                    }

                    if (!newToken) {
                        throw new Error('No token received from refresh endpoint');
                    }

                    setInStorage(STORAGE_KEYS.ACCESS_TOKEN, newToken);
                    if (newRefreshToken) {
                        setInStorage(STORAGE_KEYS.REFRESH_TOKEN, newRefreshToken);
                    }

                    if (originalRequest.headers) {
                        originalRequest.headers.Authorization = `Bearer ${newToken}`;
                    }

                    try {
                        const userResponse = await axios.get(`${API_URL}/auth/me`, {
                            headers: {
                                Authorization: `Bearer ${newToken}`
                            }
                        });

                        const userData = userResponse.data.data || userResponse.data;
                        setInStorage(STORAGE_KEYS.USER, JSON.stringify(userData));
                    } catch (userError) {
                        console.error('Failed to update user data after token refresh:', userError);
                    }

                    onTokenRefreshed(newToken);

                    isRefreshing = false;

                    return axiosInstance(originalRequest);
                } catch (refreshError) {
                    isRefreshing = false;
                    onRefreshError(refreshError);
                    return Promise.reject(refreshError);
                }
            }

            return Promise.reject(error);
        }
    );
}

export default axiosInstance;