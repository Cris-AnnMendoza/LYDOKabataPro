/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,jsx}'],
  theme: {
    extend: {
      colors: {
        primary:  { DEFAULT: '#1565c0', dark: '#0d3b6e', light: '#1e88e5', pale: '#e3f2fd' },
        success:  { DEFAULT: '#2e7d32', light: '#43a047', pale: '#e8f5e9' },
      },
      fontFamily: { sans: ['Inter', 'sans-serif'] },
    },
  },
  plugins: [],
}
