"use client";
import { useState } from "react";
import { useRouter } from "next/navigation";
import { Link } from "@heroui/link";
import { Button } from "@heroui/button";
import { Input } from "@heroui/input";
import { Card } from "@heroui/card";
import { FcGoogle } from "react-icons/fc";
import { MdAlternateEmail } from "react-icons/md";
import { RiLockPasswordLine, RiUserLine } from "react-icons/ri";
import { z } from "zod";

import { useAuth } from "@/context/authContext";

// Define types for form data
interface FormData {
  email: string;
  password: string;
  confirmPassword: string;
  displayName: string;
}

// Define type for form errors
interface FormErrors {
  [key: string]: string;
}

// Define type for API error responses
interface ApiError {
  response?: {
    data?: {
      message?: string;
      error?: string;
    };
  };
}

// Create schema for form validation
const registerSchema = z
  .object({
    email: z.string().email("Please enter a valid email address"),
    password: z
      .string()
      .min(8, "Password must be at least 8 characters")
      .regex(/[A-Z]/, "Password must contain at least one uppercase letter")
      .regex(/[a-z]/, "Password must contain at least one lowercase letter")
      .regex(/[0-9]/, "Password must contain at least one number")
      .regex(
        /[^A-Za-z0-9]/,
        "Password must contain at least one special character",
      ),
    confirmPassword: z.string(),
    displayName: z.string().optional(),
  })
  .refine((data) => data.password === data.confirmPassword, {
    message: "Passwords do not match",
    path: ["confirmPassword"],
  });

export default function Register() {
  const auth = useAuth();
  const router = useRouter();

  const [formData, setFormData] = useState<FormData>({
    email: "",
    password: "",
    confirmPassword: "",
    displayName: "",
  });

  const [errors, setErrors] = useState<FormErrors>({});
  const [isSubmitting, setIsSubmitting] = useState<boolean>(false);
  const [serverError, setServerError] = useState<string>("");

  // Fixed handleChange function with proper event typing
  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;

    // Update form data
    setFormData((prev) => ({
      ...prev,
      [name]: value,
    }));

    // Clear error when field is being edited
    if (errors[name]) {
      setErrors((prev) => {
        const newErrors = { ...prev };

        delete newErrors[name];

        return newErrors;
      });
    }

    // Clear server error when user starts typing again
    if (serverError) {
      setServerError("");
    }
  };

  const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setIsSubmitting(true);
    setServerError("");

    try {
      // Validate the form data
      const validatedData = registerSchema.parse(formData);

      // Call register from auth service
      const response = await auth.register(validatedData);

      router.push("/");
    } catch (error) {
      // Handle validation errors
      if (error instanceof z.ZodError) {
        const formattedErrors: FormErrors = {};

        error.errors.forEach((err) => {
          const path = err.path[0];

          formattedErrors[path as string] = err.message;
        });
        setErrors(formattedErrors);
      } else {
        // Handle API errors
        console.error("Registration error:", error);

        // Extract the error message from the API response
        const apiError = error as ApiError;
        const errorMessage =
          apiError.response?.data?.message ||
          apiError.response?.data?.error ||
          "Registration failed. Please try again.";

        setServerError(errorMessage);
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  // Helper to check if a field has an error
  const getErrorMessage = (fieldName: keyof FormData): string => {
    return errors[fieldName] || "";
  };

  return (
    <div className="flex items-center justify-center">
      <Card className="w-full max-w-md backdrop-blur-sm bg-background/80 shadow-xl border-0">
        <div className="space-y-1 p-6 pb-0">
          <h2 className="text-3xl font-bold text-center">Create an account</h2>
          <p className="text-center text-sm text-muted-foreground">
            Sign up using one of the methods below
          </p>
        </div>

        <div className="p-6">
          <Button
            className="w-full flex items-center justify-center gap-2 py-4 text-lg hover:bg-background/90 transition-all"
            type="button"
            variant="bordered"
            onClick={() => console.log("Google sign up")}
          >
            <FcGoogle className="text-xl" />
            Sign up with Google
          </Button>

          <div className="relative flex items-center py-4">
            <div className="flex-grow border-t" />
            <span className="mx-2 text-sm text-muted-foreground">OR</span>
            <div className="flex-grow border-t" />
          </div>

          {/* Server error alert */}
          {serverError && (
            <div className="mb-4 p-3 bg-red-50 border border-red-200 text-red-600 rounded-md">
              {serverError}
            </div>
          )}

          <form noValidate onSubmit={handleSubmit}>
            <div className="space-y-4">
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
                    getErrorMessage("email") ? "email-error" : undefined
                  }
                  aria-invalid={!!getErrorMessage("email")}
                  className={`py-4 ${getErrorMessage("email") ? "border-red-500" : ""}`}
                  id="email"
                  name="email"
                  placeholder="name@example.com"
                  type="email"
                  value={formData.email}
                  onChange={handleChange}
                />
                {getErrorMessage("email") && (
                  <p className="text-sm text-red-500 mt-1" id="email-error">
                    {getErrorMessage("email")}
                  </p>
                )}
              </div>

              <div className="space-y-1">
                <label
                  className="flex items-center gap-1 text-sm font-medium"
                  htmlFor="password"
                >
                  <RiLockPasswordLine className="text-primary" />
                  Password
                </label>
                <Input
                  required
                  aria-describedby={
                    getErrorMessage("password") ? "password-error" : undefined
                  }
                  aria-invalid={!!getErrorMessage("password")}
                  className={`py-4 ${getErrorMessage("password") ? "border-red-500" : ""}`}
                  id="password"
                  name="password"
                  type="password"
                  value={formData.password}
                  onChange={handleChange}
                />
                {getErrorMessage("password") && (
                  <p className="text-sm text-red-500 mt-1" id="password-error">
                    {getErrorMessage("password")}
                  </p>
                )}
              </div>

              <div className="space-y-1">
                <label
                  className="flex items-center gap-1 text-sm font-medium"
                  htmlFor="confirmPassword"
                >
                  <RiLockPasswordLine className="text-primary" />
                  Confirm Password
                </label>
                <Input
                  required
                  aria-describedby={
                    getErrorMessage("confirmPassword")
                      ? "confirmPassword-error"
                      : undefined
                  }
                  aria-invalid={!!getErrorMessage("confirmPassword")}
                  className={`py-4 ${getErrorMessage("confirmPassword") ? "border-red-500" : ""}`}
                  id="confirmPassword"
                  name="confirmPassword"
                  type="password"
                  value={formData.confirmPassword}
                  onChange={handleChange}
                />
                {getErrorMessage("confirmPassword") && (
                  <p
                    className="text-sm text-red-500 mt-1"
                    id="confirmPassword-error"
                  >
                    {getErrorMessage("confirmPassword")}
                  </p>
                )}
              </div>

              <div className="space-y-1">
                <label
                  className="flex items-center gap-1 text-sm font-medium"
                  htmlFor="displayName"
                >
                  <RiUserLine className="text-primary" />
                  Display Name{" "}
                  <span className="text-xs text-muted-foreground">
                    (optional)
                  </span>
                </label>
                <Input
                  className="py-4"
                  id="displayName"
                  name="displayName"
                  type="text"
                  value={formData.displayName}
                  onChange={handleChange}
                />
              </div>

              <div className="pt-4">
                <Button
                  className="w-full py-6 text-lg"
                  disabled={
                    isSubmitting || (auth.loading !== undefined && auth.loading)
                  }
                  type="submit"
                >
                  {isSubmitting || (auth.loading !== undefined && auth.loading)
                    ? "Creating Account..."
                    : "Create Account"}
                </Button>
              </div>
            </div>
          </form>
        </div>

        <div className="flex justify-center border-t p-6">
          <p className="text-sm text-muted-foreground">
            Already have an account?{" "}
            <Link
              className="font-medium text-primary hover:underline"
              href="/auth/login"
            >
              Sign in
            </Link>
          </p>
        </div>
      </Card>
    </div>
  );
}
