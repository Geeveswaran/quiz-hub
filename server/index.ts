import { spawn } from "child_process";

console.log("Starting PHP Server on port 5000...");

// Start PHP built-in server
const php = spawn("php", ["-S", "0.0.0.0:5000", "-t", "."], {
  stdio: "inherit",
});

php.on("close", (code) => {
  console.log(`PHP server exited with code ${code}`);
  process.exit(code || 0);
});

// Handle termination signals
process.on("SIGTERM", () => {
  php.kill("SIGTERM");
});
process.on("SIGINT", () => {
  php.kill("SIGINT");
});
