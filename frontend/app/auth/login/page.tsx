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

        setFormData(prev => ({
            ...prev,
            [name]: value,
        }));

        if (formErrors[name]) {
            setFormErrors(prev => {
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
                error.errors.forEach(err => {
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
                        variant="bordered"
                        className="w-full flex items-center justify-center gap-2 py-4 text-lg hover:bg-background/90 transition-all"
                        onClick={handleGoogleLogin}
                    >
                        <FcGoogle className="text-xl" />
                        Sign in with Google
                    </Button>

                    <div className="relative flex items-center py-4">
                        <div className="flex-grow border-t"></div>
                        <span className="mx-2 text-sm text-muted-foreground">OR</span>
                        <div className="flex-grow border-t"></div>
                    </div>

                    {/* Email/Password login form */}
                    <form onSubmit={handleSubmit} noValidate>
                        <div className="space-y-4">
                            {/* Email field */}
                            <div className="space-y-1">
                                <label
                                    htmlFor="email"
                                    className="flex items-center gap-1 text-sm font-medium"
                                >
                                    <MdAlternateEmail className="text-primary" />
                                    Email
                                </label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    placeholder="name@example.com"
                                    autoComplete="email"
                                    required
                                    value={formData.email}
                                    onChange={handleChange}
                                    className={`py-4 ${formErrors.email ? "border-red-500" : ""}`}
                                    aria-invalid={!!formErrors.email}
                                    aria-describedby={formErrors.email ? "email-error" : undefined}
                                />
                                {formErrors.email && (
                                    <p id="email-error" className="text-sm text-red-500 mt-1">
                                        {formErrors.email}
                                    </p>
                                )}
                            </div>

                            {/* Password field */}
                            <div className="space-y-1">
                                <div className="flex items-center justify-between">
                                    <label
                                        htmlFor="password"
                                        className="flex items-center gap-1 text-sm font-medium"
                                    >
                                        <RiLockPasswordLine className="text-primary" />
                                        Password
                                    </label>
                                    <Link
                                        href="/auth/forgot-password"
                                        className="text-xs text-primary hover:underline"
                                    >
                                        Forgot password?
                                    </Link>
                                </div>
                                <Input
                                    id="password"
                                    name="password"
                                    type="password"
                                    autoComplete="current-password"
                                    required
                                    value={formData.password}
                                    onChange={handleChange}
                                    className={`py-4 ${
                                        formErrors.password ? "border-red-500" : ""
                                    }`}
                                    aria-invalid={!!formErrors.password}
                                    aria-describedby={
                                        formErrors.password ? "password-error" : undefined
                                    }
                                />
                                {formErrors.password && (
                                    <p id="password-error" className="text-sm text-red-500 mt-1">
                                        {formErrors.password}
                                    </p>
                                )}
                            </div>

                            {/* Submit button */}
                            <div className="pt-4">
                                <Button
                                    type="submit"
                                    className="w-full py-6 text-lg"
                                    disabled={isSubmitting || loading}
                                    isLoading={isSubmitting || loading}
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
                        <Link href="/auth/register" className="font-medium text-primary hover:underline">
                            Sign up
                        </Link>
                    </p>
                </div>
            </Card>
        </div>
    );
}