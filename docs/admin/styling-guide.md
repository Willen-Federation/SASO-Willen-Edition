# Styling Guide

Best practices for styling admin pages using TailAdmin and Tailwind CSS.

## Color Palette

### Semantic Colors
Use semantic colors for consistent meaning:
- **Primary (Blue)** - Main actions, links, focus states
- **Success (Green)** - Positive actions, confirmations, enabled states
- **Danger (Red)** - Destructive actions, errors, disabled states
- **Warning (Yellow)** - Caution, pending states, warnings
- **Info (Light Blue)** - Informational messages

### CSS Variables
All colors use CSS custom properties that support dark mode:

```css
:root {
  --saso-text: #1e293b;
  --saso-body: #c4d4e4;
  --saso-card: #ffffff;
}

.dark {
  --saso-text: #f0f4f9;
  --saso-body: #08111f;
  --saso-card: #1a2b40;
}
```

Always use component classes (`.btn-primary`, `.alert-success`) rather than direct color utilities to ensure dark mode consistency.

## Spacing

Follow the spacing scale defined in Tailwind:

```
1 unit = 0.25rem = 4px
```

### Margin/Padding Classes
```
p-0   = 0
p-1   = 4px
p-2   = 8px
p-3   = 12px
p-4   = 16px
p-6   = 24px
p-8   = 32px
```

### Recommended Usage
- **Cards**: `.p-6` (24px padding)
- **Form groups**: `.mb-4` (16px bottom margin)
- **Sections**: `.mb-6` (24px bottom margin)
- **Button groups**: `.gap-3` or `.gap-4` (12-16px gap)

## Typography

### Font Family
All text uses the Japanese-first font stack defined in base styles:

```css
font-family: "Noto Sans JP", "Hiragino Kaku Gothic ProN", "Hiragino Sans",
             Meiryo, sans-serif;
```

No additional font classes needed.

### Text Sizes
```
text-xs    = 12px
text-sm    = 14px
text-base  = 16px
text-lg    = 18px
text-xl    = 20px
text-2xl   = 24px
```

### Text Styles
```html
<h3 class="text-lg font-semibold">Heading</h3>
<p class="text-base">Body text</p>
<small class="text-sm text-muted">Helper text</small>
```

## Layout Patterns

### Two-Column Form
```html
<div class="row g-3">
  <div class="col-md-6">
    <label for="field1" class="form-label">Field 1</label>
    <input type="text" class="form-input" id="field1" name="field1">
  </div>
  <div class="col-md-6">
    <label for="field2" class="form-label">Field 2</label>
    <input type="text" class="form-input" id="field2" name="field2">
  </div>
</div>
```

### Stacked Layout (Mobile First)
```html
<div class="row g-4">
  <div class="col-md-4">Content (33% on md+)</div>
  <div class="col-md-8">Content (66% on md+)</div>
</div>
```

### Header with Badge
```html
<div class="card-header fw-bold d-flex justify-content-between align-items-center">
  <span>List Title</span>
  <span class="badge bg-secondary">12 items</span>
</div>
```

## Component Patterns

### Form Section
```html
<div class="mb-6 rounded-sm border border-gray-200 bg-white shadow-default 
            dark:border-gray-800 dark:bg-boxdark">
  <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
    <h3 class="font-semibold text-black dark:text-white">Section Title</h3>
    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
      Subtitle or description
    </p>
  </div>
  <div class="p-6">
    <!-- Form fields -->
  </div>
</div>
```

### Data Table
```html
<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped table-hover">
        <!-- Table content -->
      </table>
    </div>
  </div>
</div>
```

### Action Buttons
```html
<div class="flex gap-3">
  <button class="btn btn-primary">Primary Action</button>
  <button class="btn btn-secondary">Secondary Action</button>
  <button class="btn btn-outline-danger">Dangerous Action</button>
</div>
```

## Responsive Design

### Mobile-First Approach
Start with mobile styles, then add breakpoints:

```html
<!-- Full width on small screens -->
<div class="col-12">
  <!-- 50% on medium screens and up -->
  <div class="md:col-span-6">Content</div>
</div>
```

### Breakpoints
```
sm  = 640px   (not heavily used)
md  = 768px   (typical "tablet" breakpoint)
lg  = 1024px  (typical "desktop" breakpoint)
xl  = 1280px  (large desktop)
2xl = 1536px  (extra large)
```

### Common Responsive Patterns
```html
<!-- Stack on small, side-by-side on medium+ -->
<div class="row g-3">
  <div class="col-md-6">Left</div>
  <div class="col-md-6">Right</div>
</div>

<!-- Show/hide based on screen size -->
<div class="hidden md:block">Shown on medium+ screens</div>
<div class="md:hidden">Shown only on small screens</div>
```

## Dark Mode

### Writing Styles for Dark Mode
Use Tailwind's `.dark:` prefix for dark mode styles:

```html
<div class="bg-white dark:bg-boxdark 
            border-gray-200 dark:border-gray-800
            text-black dark:text-white">
  Dark mode aware element
</div>
```

### Component Classes Handle Dark Mode
Most TailAdmin components already handle dark mode, so you don't need to add `.dark:` prefixes:

```html
<!-- ✓ Good - Component handles dark mode -->
<div class="card">Content</div>

<!-- ✗ Avoid - Redundant, already in component -->
<div class="bg-white dark:bg-boxdark">Content</div>
```

## Accessibility

### Semantic HTML
```html
<!-- ✓ Good - Semantic HTML -->
<button type="submit" class="btn btn-primary">Save</button>
<label for="field" class="form-label">Field Name</label>

<!-- ✗ Avoid - Non-semantic -->
<div class="btn" role="button">Save</div>
<span>Field Name</span>
```

### Form Labels
Always associate labels with form inputs:

```html
<label for="username" class="form-label">Username</label>
<input type="text" class="form-input" id="username" name="username">
```

### ARIA Labels
Use ARIA attributes for icon-only buttons:

```html
<button class="btn-close" aria-label="Close alert"></button>
<a href="./edit" aria-label="Edit this item">✎</a>
```

### Focus Indicators
All interactive elements show focus indicators automatically via Tailwind.

## Common Anti-patterns

### ✗ Don't
```html
<!-- Using Bootstrap classes -->
<div class="btn btn-danger">Not a button</div>

<!-- Mixing component systems -->
<div class="btn btn-danger btn-outline">Conflicting styles</div>

<!-- Inline styles -->
<div style="color: red; padding: 10px;">Use classes instead</div>

<!-- Overriding dark mode -->
<div class="bg-white">Ignores dark mode</div>
```

### ✓ Do
```html
<!-- Use semantic elements -->
<button type="button" class="btn btn-danger">Delete</button>

<!-- Use component classes -->
<div class="card bg-danger text-white">Alert</div>

<!-- Use Tailwind utilities -->
<div class="text-red-600 p-2.5">Use utilities</div>

<!-- Support dark mode -->
<div class="bg-white dark:bg-boxdark">Dark aware</div>
```

---

See Also: [Components](components.md), [Dark Mode](dark-mode.md), [Accessibility](accessibility.md)
