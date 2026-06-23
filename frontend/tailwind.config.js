/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js}'],
  theme: {
    extend: {
      colors: {
        // Surface — warm paper, not pure white
        paper: '#FBFAF6',
        cork: '#F3EFE6',
        ink: '#1F2421',
        graphite: '#5B635D',
        hairline: '#E5E0D4',
        // Primary — muted forest green (matches team mockups)
        pine: {
          50: '#EBF4EF',
          100: '#D2E7DB',
          200: '#A6CFB8',
          300: '#6FB390',
          400: '#3E9670',
          500: '#1E7D5A',
          600: '#166349',
          700: '#114D3A',
          800: '#0C3A2C',
          900: '#082A20',
        },
        // Sticky note materials (also the in-app color palette)
        sticky: {
          yellow: '#FFE89C',
          pink: '#FFC7D3',
          blue: '#BFE0FB',
          green: '#C9EDC1',
          purple: '#D9CDF7',
          orange: '#FFD3A8',
        },
      },
      fontFamily: {
        // display: characterful humanist grotesque, used with restraint
        display: ['"Bricolage Grotesque"', 'system-ui', 'sans-serif'],
        // body: clean, readable
        sans: ['"Inter"', 'system-ui', 'sans-serif'],
        // mono: note metadata / labels
        mono: ['"JetBrains Mono"', 'ui-monospace', 'monospace'],
      },
      boxShadow: {
        // soft lift for cards
        lift: '0 1px 2px rgba(31,36,33,.04), 0 8px 24px -12px rgba(31,36,33,.18)',
        // the "pinned paper" shadow for sticky notes
        note: '0 1px 1px rgba(31,36,33,.05), 0 6px 14px -6px rgba(31,36,33,.22)',
        noteLift: '0 4px 6px rgba(31,36,33,.08), 0 18px 40px -12px rgba(31,36,33,.35)',
      },
      borderRadius: {
        note: '4px',
      },
      keyframes: {
        'fade-up': {
          '0%': { opacity: '0', transform: 'translateY(8px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        'pop-in': {
          '0%': { opacity: '0', transform: 'scale(.94)' },
          '100%': { opacity: '1', transform: 'scale(1)' },
        },
      },
      animation: {
        'fade-up': 'fade-up .35s cubic-bezier(.21,1.02,.73,1) both',
        'pop-in': 'pop-in .25s cubic-bezier(.21,1.02,.73,1) both',
      },
    },
  },
  plugins: [],
}
