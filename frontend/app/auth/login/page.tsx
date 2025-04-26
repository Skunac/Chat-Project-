"use client";

import { useState, useEffect } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { Link } from "@heroui/link";
import { Button } from "@heroui/button";
import { Input } from "@heroui/input";
import { Card } from "@heroui/card";
import { FcGoogle } from "react-icons/fc";
import { MdAlternateEmail } from "react-icons/md";
import { RiLockPasswordLine } from "react-icons/ri";
import { z } from "zod";

import { useAuth } from "@/context/authContext";

const loginSchema = z.object({
  email: z.string().email("Please enter a valid email address"),
  password: z.string().min(1, "Password is required"),
});

interface FormData {
  email: string;
  password: string;
}

interface FormErrors {
  [key: string]: string;
}

export default function Login() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { login, user, loading, error, clearError } = useAuth();

  const [formData, setFormData] = useState<FormData>({
    email: "",
    password: "",
  });

  const [formErrors, setFormErrors] = useState<FormErrors>({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [statusMessage, setStatusMessage] = useState<{
    type: "error" | "info" | "success";
    message: string;
  } | null>(null);

  useEffect(() => {
    if (error) {
      setStatusMessage({
        type: "error",
        message: error,
      });
    }
  }, [error]);

  useEffect(() => {
    if (user && !loading) {
      router.push("/");
    }
  }, [user, loading, router]);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;

    setFormData((prev) => ({
      ...prev,
      [name]: value,
    }));

    if (formErrors[name]) {
      setFormErrors((prev) => {
        const newErrors = { ...prev };

        delete newErrors[name];

        return newErrors;
      });
    }

    if (statusMessage) {
      setStatusMessage(null);
      clearError();
    }
  };

  const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setIsSubmitting(true);
    setStatusMessage(null);
    clearError();

    try {
      const validatedData = loginSchema.parse(formData);

      await login({
        email: validatedData.email,
        password: validatedData.password,
      });
    } catch (error) {
      if (error instanceof z.ZodError) {
        const formattedErrors: FormErrors = {};

        error.errors.forEach((err) => {
          const path = err.path[0];

          formattedErrors[path as string] = err.message;
        });
        setFormErrors(formattedErrors);
      } else {
        console.error("Login error:", error);
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleGoogleLogin = () => {
    window.location.href = `${process.env.NEXT_PUBLIC_API_URL}/auth/google/connect`;
  };

  return (
    <div className="flex items-center justify-center min-h-[calc(100vh-150px)]">
      <Card className="w-full max-w-md shadow-xl border-0 bg-background/80 backdrop-blur-sm">
        <div className="space-y-1 p-6 pb-0">
          <h2 className="text-3xl font-bold text-center">Welcome back</h2>
          <p className="text-center text-sm text-muted-foreground">
            Sign in to your account
          </p>
        </div>

        <div className="p-6">
          {/* Status message display */}
          {statusMessage && (
            <div
              className={`p-3 rounded-md mb-4 ${
                statusMessage.type === "error"
                  ? "bg-red-50 border border-red-200 text-red-600"
                  : statusMessage.type === "success"
                    ? "bg-green-50 border border-green-200 text-green-600"
                    : "bg-blue-50 border border-blue-200 text-blue-600"
              }`}
            >
              {statusMessage.message}
            </div>
          )}

          {/* Google sign in button */}
          <Button
            className="w-full flex items-center justify-center gap-2 py-4 text-lg hover:bg-background/90 transition-all"
            variant="bordered"
            onClick={handleGoogleLogin}
          >
            <FcGoogle className="text-xl" />
            Sign in with Google
          </Button>

          <div className="relative flex items-center py-4">
            <div className="flex-grow border-t" />
            <span className="mx-2 text-sm text-muted-foreground">OR</span>
            <div className="flex-grow border-t" />
          </div>

          {/* Email/Password login form */}
          <form noValidate onSubmit={handleSubmit}>
            <div className="space-y-4">
              {/* Email field */}
              <div className="space-y-1">
                <label
                  className="flex items-center gap-1 text-sm font-medium"
                  htmlFor="email"
                >
                  <MdAlternateEmail className="text-primary" />
                  Email
                </label>
                <Input
                  required
                  aria-describedby={
                    formErrors.email ? "email-error" : undefined
                  }
                  aria-invalid={!!formErrors.email}
                  autoComplete="email"
                  className={`py-4 ${formErrors.email ? "border-red-500" : ""}`}
                  id="email"
                  name="email"
                  placeholder="name@example.com"
                  type="email"
                  value={formData.email}
                  onChange={handleChange}
                />
                {formErrors.email && (
                  <p className="text-sm text-red-500 mt-1" id="email-error">
                    {formErrors.email}
                  </p>
                )}
              </div>

              {/* Password field */}
              <div className="space-y-1">
                <div className="flex items-center justify-between">
                  <label
                    className="flex items-center gap-1 text-sm font-medium"
                    htmlFor="password"
                  >
                    <RiLockPasswordLine className="text-primary" />
                    Password
                  </label>
                  <Link
                    className="text-xs text-primary hover:underline"
                    href="/auth/forgot-password"
                  >
                    Forgot password?
                  </Link>
                </div>
                <Input
                  required
                  aria-describedby={
                    formErrors.password ? "password-error" : undefined
                  }
                  aria-invalid={!!formErrors.password}
                  autoComplete="current-password"
                  className={`py-4 ${
                    formErrors.password ? "border-red-500" : ""
                  }`}
                  id="password"
                  name="password"
                  type="password"
                  value={formData.password}
                  onChange={handleChange}
                />
                {formErrors.password && (
                  <p className="text-sm text-red-500 mt-1" id="password-error">
                    {formErrors.password}
                  </p>
                )}
              </div>

              {/* Submit button */}
              <div className="pt-4">
                <Button
                  className="w-full py-6 text-lg"
                  disabled={isSubmitting || loading}
                  isLoading={isSubmitting || loading}
                  type="submit"
                >
                  {isSubmitting || loading ? "Signing in..." : "Sign in"}
                </Button>
              </div>
            </div>
          </form>
        </div>

        {/* Register link */}
        <div className="flex justify-center border-t p-6">
          <p className="text-sm text-muted-foreground">
            Don't have an account?{" "}
            <Link
              className="font-medium text-primary hover:underline"
              href="/auth/register"
            >
              Sign up
            </Link>
          </p>
        </div>
      </Card>
    </div>
  );
}
