import {heroui} from "@heroui/theme"

/** @type {import('tailwindcss').Config} */
const config = {
  content: [
    './components/**/*.{js,ts,jsx,tsx,mdx}',
    './app/**/*.{js,ts,jsx,tsx,mdx}',
    "./node_modules/@heroui/theme/dist/**/*.{js,ts,jsx,tsx}"
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ["var(--font-sans)"],
        mono: ["var(--font-mono)"],
      },
    },
    backgroundImage: {
      'custom-radial': 'radial-gradient(circle, rgba(14,88,145,1) 0%, rgba(13,38,59,1) 80%, rgba(2,0,36,1) 100%)',
    }
  },
  darkMode: "class",
  plugins: [heroui()],
}

module.exports = config;