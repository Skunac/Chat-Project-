"use client";

import { useState, useEffect, useRef, Suspense } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { Card } from "@heroui/card";

import { useAuth } from "@/context/authContext";
import authService from "@/services/authService";

function GoogleCallbackWithParams() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { refreshUserData } = useAuth();
  const [status, setStatus] = useState("loading");
  const [errorMessage, setErrorMessage] = useState("");
  const hasProcessedCode = useRef(false);

  useEffect(() => {
    const exchangeCodeForToken = async () => {
      if (hasProcessedCode.current) {
        return;
      }

      try {
        const code = searchParams.get("code");

        if (!code) {
          setStatus("error");
          setErrorMessage("No authorization code received");

          return;
        }

        hasProcessedCode.current = true;

        const response = await authService.handleGoogleCallback(code);

        localStorage.setItem("accessToken", response.token);

        if (response.refresh_token) {
          localStorage.setItem("refreshToken", response.refresh_token);
        }

        await refreshUserData();
        setStatus("success");
        setTimeout(() => router.push("/"), 1000);
      } catch (error) {
        setStatus("error");
        setErrorMessage("Failed to complete authentication");
      }
    };

    exchangeCodeForToken();
  }, [searchParams, router, refreshUserData]);

  return (
    <div className="flex items-center justify-center min-h-[calc(100vh-150px)]">
      <Card className="w-full max-w-md shadow-xl border-0 bg-background/80 backdrop-blur-sm p-6 text-center">
        {status === "loading" && (
          <>
            <div className="animate-spin h-10 w-10 border-4 border-primary border-t-transparent rounded-full mx-auto mb-4" />
            <h2 className="text-xl font-medium mb-2">
              Completing authentication...
            </h2>
            <p className="text-default-500">Please wait while we log you in.</p>
          </>
        )}

        {status === "success" && (
          <>
            <div className="text-green-500 text-5xl mb-4">✓</div>
            <h2 className="text-xl font-medium mb-2">
              Successfully authenticated!
            </h2>
            <p className="text-default-500">
              Redirecting you to the dashboard...
            </p>
          </>
        )}

        {status === "error" && (
          <>
            <div className="text-red-500 text-5xl mb-4">✗</div>
            <h2 className="text-xl font-medium mb-2">Authentication failed</h2>
            <p className="text-red-500">{errorMessage}</p>
            <button
              className="mt-4 px-4 py-2 bg-primary text-white rounded-md"
              onClick={() => router.push("/auth/login")}
            >
              Back to login
            </button>
          </>
        )}
      </Card>
    </div>
  );
}

function CallbackLoading() {
  return (
    <div className="flex items-center justify-center min-h-[calc(100vh-150px)]">
      <Card className="w-full max-w-md shadow-xl border-0 bg-background/80 backdrop-blur-sm p-6 text-center">
        <div className="animate-spin h-10 w-10 border-4 border-primary border-t-transparent rounded-full mx-auto mb-4" />
        <h2 className="text-xl font-medium mb-2">
          Preparing authentication...
        </h2>
        <p className="text-default-500">Loading authentication params...</p>
      </Card>
    </div>
  );
}

export default function GoogleCallback() {
  return (
    <Suspense fallback={<CallbackLoading />}>
      <GoogleCallbackWithParams />
    </Suspense>
  );
}
