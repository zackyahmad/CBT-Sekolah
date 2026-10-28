/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./index.php",
    "./pages/**/*.php",    // Ini akan men-scan semua file PHP di folder pages
    "./layouts/**/*.php",
    "./proses/**/*.php",
    "./src/**/*.css",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}