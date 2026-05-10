# Dark Mode Implementation

SASO admin pages support full dark mode with seamless light/dark theme switching. This guide explains how dark mode works and how to ensure your admin pages support it.

## How Dark Mode Works

### Theme Toggle
Users can toggle dark mode via the theme button in the header. The preference is:
1. Persisted to `localStorage` (key: `saso.theme`)
2. Applied immediately to all pages
3. Synchronized with OS preference on first visit

### Theme Management (Alpine.js)
The theme system is managed by Alpine.js in `/js/tailadmin.js`:

```javascript
// In the HTML root element:
<html x-data="taTheme()" :class="theme">
```

The `taTheme()` Alpine component:
- Reads/writes localStorage
- Detects OS theme preference
- Updates the `.dark` class on `<html>`
- Prevents flash-of-light-mode on page load

## CSS Variables System

All colors use CSS custom properties defined in `/css/app.css`:

### Light Mode (`:root`)
```css
:root {
  --saso-text: #1e293b;
  --saso-body: #c4d4e4;
  --saso-sidebar: #ffffff;
  --saso-card: #ffffff;
  /* ... more variables ... */
}
```

### Dark Mode (`.dark`)
```css
.dark {
  --saso-text: #f0f4f9;
  --saso-body: #08111f;
  --saso-sidebar: #1a2b40;
  --saso-card: #1a2b40;
  /* ... more variables ... */
}
```

### Color Token Reference
| Purpose | Light | Dark |
|---------|-------|------|
| Body BG | `#c4d4e4` | `#08111f` |
| Panel/Card | `#ffffff` | `#1a2b40` |
| Border | `#5a7090` | `#5a7d9f` |
| Primary Text | `#1e293b` | `#f0f4f9` |
| Secondary Text | `#64748b` | `#8ba4be` |

**All contrast ratios verified to WCAG 2.1 AA standards.**

## Using Dark Mode in Components

### Via Component Classes
Most TailAdmin components automatically support dark mode:

```html
<!-- ✓ Good - Component handles dark mode -->
<div class="card">Content</div>
<button class="btn btn-primary">Click me</button>
<div class="alert alert-success">Success!</div>
```

No additional work needed - components use the CSS variable system.

### Via Tailwind `.dark:` Prefix
For custom styling, use Tailwind's dark mode prefix:

```html
<!-- Light mode: white bg, dark mode: dark bg -->
<div class="bg-white dark:bg-boxdark">Content</div>

<!-- Light mode: gray text, dark mode: light text -->
<p class="text-gray-600 dark:text-gray-300">Description</p>
```

### Color Names in Dark Mode
Tailwind provides semantic color names that work in both modes:

```html
<!-- Background colors -->
<div class="bg-white dark:bg-boxdark">Card background</div>
<div class="bg-whiter dark:bg-boxdark-2">Lighter background</div>

<!-- Text colors -->
<p class="text-bodydark1 dark:text-bodydark2">Muted text</p>
<p class="text-black dark:text-white">Primary text</p>

<!-- Border colors -->
<div class="border-stroke dark:border-strokedark">Bordered element</div>
```

## Testing Dark Mode

### Manual Testing
1. Click the theme toggle button in the header
2. Page should immediately switch to dark mode
3. Reload the page - dark mode preference should persist
4. Check all:
   - Text contrast
   - Button visibility
   - Table readability
   - Form input visibility
   - Alert colors
   - Badge visibility

### Color Contrast Verification
Use a color contrast checker:
1. Chrome DevTools: Inspect element → Styles → Check contrast ratio
2. Lighthouse Accessibility audit
3. axe DevTools browser extension

**Minimum WCAG AA requirements:**
- Large text (18pt+): 3:1 contrast
- Normal text: 4.5:1 contrast

## Best Practices

### ✓ Do Support Dark Mode
```html
<!-- Define light AND dark colors -->
<div class="bg-white dark:bg-boxdark 
            text-black dark:text-white
            border-gray-200 dark:border-gray-800">
  Content
</div>
```

### ✗ Don't Ignore Dark Mode
```html
<!-- Only light mode - fails in dark mode -->
<div class="bg-white text-black">Text will be invisible</div>

<!-- Using hex colors - ignores CSS variables -->
<div style="background: #ffffff; color: #000000;">Content</div>
```

### ✓ Use CSS Variables
```css
/* Good - uses CSS variable system */
color: var(--saso-text);
background-color: var(--saso-card);
border-color: var(--saso-card-bdr);
```

### ✗ Hardcode Colors
```css
/* Bad - ignores dark mode */
color: #1e293b;
background-color: #ffffff;
```

## Accessible Color Combinations

All color combinations used in TailAdmin have been verified for WCAG 2.1 AA compliance:

### Text on Body Background
- **Light mode**: #1e293b (text) on #c4d4e4 (body) = 9.7:1 (AAA)
- **Dark mode**: #f0f4f9 (text) on #08111f (body) = 17:1 (AAA)

### Text on Card Background
- **Light mode**: #1e293b (text) on #ffffff (card) = 12.6:1 (AAA)
- **Dark mode**: #f0f4f9 (text) on #1a2b40 (card) = 13:1 (AAA)

### Borders vs Backgrounds
- **Light**: #5a7090 (border) on #ffffff (card) = 5.1:1 (AA)
- **Dark**: #5a7d9f (border) on #1a2b40 (card) = 3.3:1 (AA-Large)

## Common Pitfalls

### 1. Text Becomes Invisible
```html
<!-- ✗ White text in dark mode becomes invisible on dark background -->
<div class="dark:bg-boxdark text-white">Invisible</div>

<!-- ✓ Use dark-aware text color -->
<div class="dark:bg-boxdark dark:text-white">Visible</div>
```

### 2. Borders Disappear
```html
<!-- ✗ Gray border on dark background becomes invisible -->
<div class="border-gray-300 dark:bg-boxdark">Invisible border</div>

<!-- ✓ Use dark-aware border color -->
<div class="border-gray-300 dark:border-gray-700 dark:bg-boxdark">Visible</div>
```

### 3. Inputs Are Hard to Read
```html
<!-- ✗ Light background input in dark theme -->
<input type="text" class="bg-white">

<!-- ✓ Dark-aware input styling -->
<input type="text" class="form-input">
<!-- Component handles dark mode automatically -->
```

## Debugging Dark Mode Issues

### Browser Console
Check if dark mode is applied:
```javascript
// Check if .dark class is on html element
document.documentElement.classList.contains('dark')

// Check localStorage value
localStorage.getItem('saso.theme')
```

### DevTools Tips
1. **Toggle device mode**: Ctrl+Shift+M (Windows/Linux) or Cmd+Shift+M (Mac)
2. **Check media query**: F12 → 3 dots → More tools → Rendering → Emulate CSS media feature prefers-color-scheme
3. **Inspect computed colors**: Right-click element → Inspect → Computed styles

### Lighthouse Audit
Run Lighthouse accessibility audit in both light and dark modes:
1. F12 → Lighthouse tab
2. Run Accessibility audit
3. Check contrast ratio violations
4. Fix if any appear

## Technical Details

### Synchronous Theme Application
The theme is applied synchronously before the page renders to prevent white flash:

```html
<script>
  // Applied immediately, before page loads
  const theme = localStorage.getItem('saso.theme');
  if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
  }
</script>
```

### Alpine.js Integration
Alpine.js manages dynamic theme switching after page load:

```html
<html x-data="taTheme()" :class="theme">
  <!-- taTheme() provides reactive 'theme' property -->
  <!-- Clicking toggle calls toggle() to switch theme -->
</html>
```

---

**See Also:** [Components](components.md), [Accessibility](accessibility.md), [Styling Guide](styling-guide.md)
