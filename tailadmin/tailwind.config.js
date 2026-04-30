/**
 * SASO TailAdmin — Tailwind v3 configuration.
 *
 * Color tokens, breakpoints, typography sizes lifted from TailAdmin Free
 * (https://github.com/TailAdmin/tailadmin-free-tailwind-dashboard-template),
 * translated from upstream's v4 `@theme` block to v3 `theme.extend`.
 * Build via `make tailadmin-build`; the output `css/tailadmin.css` is committed
 * so production servers do not need a Node toolchain.
 */
module.exports = {
  content: [
    './tailadmin/components/**/*.html',
    './root/template/**/*.php',
    './*/template/**/*.php',
    './*/template/**/*.js.php',
  ],
  darkMode: 'class',
  theme: {
    screens: {
      '2xsm': '375px',
      'xsm':  '425px',
      'sm':   '640px',
      'md':   '768px',
      'lg':   '1024px',
      'xl':   '1280px',
      '2xl':  '1536px',
      '3xl':  '2000px',
    },
    extend: {
      fontFamily: {
        outfit: ['Outfit', 'sans-serif'],
      },
      fontSize: {
        // TailAdmin v2 sizes
        'title-2xl': ['72px', '90px'],
        'title-xl':  ['60px', '72px'],
        'title-lg':  ['48px', '60px'],
        'title-md':  ['36px', '44px'],
        'title-sm':  ['30px', '38px'],
        'theme-xl':  ['20px', '30px'],
        'theme-sm':  ['14px', '20px'],
        'theme-xs':  ['12px', '18px'],
        // v1 compat aliases
        'title-xxl':  ['44px', '55px'],
        'title-xl2':  ['33px', '45px'],
        'title-md2':  ['26px', '30px'],
        'title-xsm':  ['18px', '24px'],
      },
      colors: {
        // TailAdmin v2 (new) tokens
        brand: {
          25:  '#f2f7ff', 50:  '#ecf3ff', 100: '#dde9ff', 200: '#c2d6ff',
          300: '#9cb9ff', 400: '#7592ff', 500: '#465fff', 600: '#3641f5',
          700: '#2a31d8', 800: '#252dae', 900: '#262e89', 950: '#161950',
        },
        'blue-light': {
          25:  '#f5fbff', 50:  '#f0f9ff', 100: '#e0f2fe', 200: '#b9e6fe',
          300: '#7cd4fd', 400: '#36bffa', 500: '#0ba5ec', 600: '#0086c9',
          700: '#026aa2', 800: '#065986', 900: '#0b4a6f', 950: '#062c41',
        },
        gray: {
          25:  '#fcfcfd', 50:  '#f9fafb', 100: '#f2f4f7', 200: '#e4e7ec',
          300: '#d0d5dd', 400: '#98a2b3', 500: '#667085', 600: '#475467',
          700: '#344054', 800: '#1d2939', 900: '#101828', 950: '#0c111d',
          dark: '#1a2231',
          // v1 compat aliases
          2: '#F7F9FC',
          3: '#FAFAFA',
        },
        orange: {
          25:  '#fffaf5', 50:  '#fff6ed', 100: '#ffead5', 200: '#fddcab',
          300: '#feb273', 400: '#fd853a', 500: '#fb6514', 600: '#ec4a0a',
          700: '#c4320a', 800: '#9c2a10', 900: '#7e2410', 950: '#511c10',
        },
        success: {
          25:  '#f6fef9', 50:  '#ecfdf3', 100: '#d1fadf', 200: '#a6f4c5',
          300: '#6ce9a6', 400: '#32d583', 500: '#12b76a', 600: '#039855',
          700: '#027a48', 800: '#05603a', 900: '#054f31', 950: '#053321',
        },
        error: {
          25:  '#fffbfa', 50:  '#fef3f2', 100: '#fee4e2', 200: '#fecdca',
          300: '#fda29b', 400: '#f97066', 500: '#f04438', 600: '#d92d20',
          700: '#b42318', 800: '#912018', 900: '#7a271a', 950: '#55160c',
        },
        warning: {
          25:  '#fffcf5', 50:  '#fffaeb', 100: '#fef0c7', 200: '#fedf89',
          300: '#fec84b', 400: '#fdb022', 500: '#f79009', 600: '#dc6803',
          700: '#b54708', 800: '#93370d', 900: '#7a2e0e', 950: '#4e1d09',
        },
        // ── TailAdmin v1 backward-compat tokens ──────────────────────────────
        // These keep old templates working while they are gradually migrated
        // to the v2 palette above. Do not add new usage of these in new templates.
        primary:    '#3C50E0',
        secondary:  '#80CAEE',
        stroke:     '#E2E8F0',
        strokedark: '#2E3A47',
        'form-strokedark': '#3d4d60',
        'form-input':      '#1d2a39',
        body:        '#64748B',
        bodydark:    '#AEB7C0',
        bodydark1:   '#DEE4EE',
        bodydark2:   '#8A99AF',
        graydark:    '#333A48',
        whiten:      '#F1F5F9',
        whiter:      '#F5F7FD',
        boxdark:     '#24303F',
        'boxdark-2': '#1A222C',
        'meta-1':    '#DC3545',
        'meta-2':    '#EFF2F7',
        'meta-3':    '#10B981',
        'meta-4':    '#313D4A',
        'meta-5':    '#259AE6',
        'meta-6':    '#FFBA00',
        'meta-7':    '#FF6766',
        'meta-8':    '#F0950C',
        'meta-9':    '#E5E7EB',
        'meta-10':   '#0EA5E9',
        danger:      '#D34053',
      },
      // v1 compat spacing (used across many un-migrated templates)
      spacing: {
        4.5:  '1.125rem',
        5.5:  '1.375rem',
        6.5:  '1.625rem',
        7.5:  '1.875rem',
        8.5:  '2.125rem',
        9.5:  '2.375rem',
        10.5: '2.625rem',
        11.5: '2.875rem',
        12.5: '3.125rem',
        13:   '3.25rem',
        13.5: '3.375rem',
        14:   '3.5rem',
        14.5: '3.625rem',
        15:   '3.75rem',
        17.5: '4.375rem',
        18:   '4.5rem',
        21:   '5.25rem',
        22.5: '5.625rem',
        25:   '6.25rem',
        27.5: '6.875rem',
        30:   '7.5rem',
        37.5: '9.375rem',
        42.5: '10.625rem',
        45:   '11.25rem',
        67.5: '16.875rem',
      },
      boxShadow: {
        'theme-sm':  '0px 1px 3px rgba(16,24,40,0.10), 0px 1px 2px rgba(16,24,40,0.06)',
        'theme-md':  '0px 4px 8px -2px rgba(16,24,40,0.10), 0px 2px 4px -2px rgba(16,24,40,0.06)',
        'theme-lg':  '0px 12px 16px -4px rgba(16,24,40,0.08), 0px 4px 6px -2px rgba(16,24,40,0.03)',
        'theme-xl':  '0px 20px 24px -4px rgba(16,24,40,0.08), 0px 8px 8px -4px rgba(16,24,40,0.03)',
      },
      zIndex: {
        '9999': '9999',
        '99999': '99999',
      },
    },
  },
  plugins: [],
};
