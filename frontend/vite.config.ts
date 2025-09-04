import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react({ jsxRuntime: "classic" })],
  optimizeDeps: {
    exclude: ["lucide-react"],
  },
  server: {
    proxy: {
      // forward requests starting with /school to the Apache server
      "/school": {
        target: "http://localhost",
        changeOrigin: true,
        secure: false,
        rewrite: (path) => path, // keep the same path
      },
    },
  },
});
