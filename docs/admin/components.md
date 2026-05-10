# TailAdmin Components

This guide covers the TailAdmin component classes available for admin page styling. All components support dark mode via the `.dark:` prefix in Tailwind CSS.

## Containers & Cards

### Card
Container with rounded border and shadow, used to group related content.

```html
<div class="card mb-4">
  <div class="card-header fw-bold">Card Title</div>
  <div class="card-body">
    <!-- Content here -->
  </div>
</div>
```

**Variants:**
- `.card` - Standard card with border and shadow
- `.card-header` - Header section with border-bottom
- `.card-body` - Content section with padding

**Dark Mode:** Automatically adjusts border and background colors.

---

## Buttons

### Primary Button
Main action button, blue with white text.

```html
<button type="submit" class="btn btn-primary">Submit</button>
```

### Secondary Button
Alternative action, bordered style.

```html
<button type="button" class="btn btn-secondary">Cancel</button>
```

### Success Button
Confirmation actions, green background.

```html
<button type="button" class="btn btn-success">Confirm</button>
```

### Danger Button
Destructive actions, red background.

```html
<button type="button" class="btn btn-danger">Delete</button>
```

### Warning Button
Cautionary actions, yellow background.

```html
<button type="button" class="btn btn-warning">Caution</button>
```

### Outline Buttons
Bordered variants with transparent background.

```html
<button type="button" class="btn btn-outline-warning">Set Default</button>
<button type="button" class="btn btn-outline-danger">Revoke</button>
```

### Small Button
Compact button for inline actions.

```html
<button type="submit" class="btn btn-sm btn-success">Enable</button>
```

---

## Forms

### Form Label
Semantic label for form fields.

```html
<label for="field_name" class="form-label">Field Name</label>
```

### Form Input
Text, email, password, URL, and other input types.

```html
<input type="text" class="form-input" id="field_name" name="field_name" placeholder="Enter value">
```

**Features:**
- Full width
- Rounded border
- Transparent background (inherits from parent)
- Focus state with primary color border
- Dark mode support

### Form Select
Dropdown select element.

```html
<select class="form-select" id="field_type" name="field_type">
  <option value="">Choose...</option>
  <option value="openai">OpenAI</option>
  <option value="gemini">Gemini</option>
</select>
```

### Form Checkbox
Checkbox input with label.

```html
<div class="form-check">
  <input type="checkbox" class="form-check-input" id="field_enabled" name="enabled">
  <label class="form-check-label" for="field_enabled">Enable this option</label>
</div>
```

### Form Text
Helper text below form fields.

```html
<input type="text" class="form-control" id="field" name="field">
<div class="form-text">Small helper text for this field</div>
```

---

## Tables

### Table with Striped Rows
Display tabular data with alternating row colors for readability.

```html
<div class="table-responsive">
  <table class="table table-striped table-hover">
    <thead class="table-dark">
      <tr>
        <th scope="col">Column 1</th>
        <th scope="col">Column 2</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>Data 1</td>
        <td>Data 2</td>
      </tr>
    </tbody>
  </table>
</div>
```

**Variants:**
- `.table` - Base table styling
- `.table-striped` - Alternating row background colors
- `.table-hover` - Highlight row on hover
- `.table-dark` - Dark header background
- `.table-responsive` - Horizontal scrolling on small screens

---

## Badges

### Badge with Color
Status indicator badge with semantic colors.

```html
<span class="badge bg-success">Active</span>
<span class="badge bg-warning text-dark">Pending</span>
<span class="badge bg-danger">Inactive</span>
```

**Color Options:**
- `.bg-primary` - Blue (default)
- `.bg-secondary` - Gray
- `.bg-success` - Green
- `.bg-danger` - Red
- `.bg-warning` - Yellow
- `.bg-info` - Light blue

---

## Alerts

### Alert Messages
Prominent message boxes for notifications.

```html
<div class="alert alert-success fade show" role="alert">
  <div class="flex items-start justify-between gap-3">
    <span>Operation completed successfully.</span>
    <button type="button" class="btn-close" @click="show = false" aria-label="Close"></button>
  </div>
</div>
```

**Variants:**
- `.alert-success` - Green, for successful operations
- `.alert-danger` - Red, for errors
- `.alert-warning` - Yellow, for warnings
- `.alert-info` - Blue, for informational messages

**Classes:**
- `.fade` - Fade in/out animation
- `.show` - Visible state (Alpine.js controls visibility)

---

## Breadcrumbs

### Navigation Breadcrumb
Show current location in page hierarchy.

```html
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="./">Home</a></li>
    <li class="breadcrumb-item"><a href="./admin/">Admin</a></li>
    <li class="breadcrumb-item active" aria-current="page">Current Page</li>
  </ol>
</nav>
```

**Features:**
- Semantic `<nav>` and `<ol>` elements
- Last item marked `.active` with `aria-current="page"`
- Links as `.breadcrumb-item`
- Automatic forward slash separator between items

---

## Utilities

### Flexbox Utilities
Layout and alignment helpers.

```html
<div class="d-flex justify-content-between align-items-center gap-4">
  <span>Left item</span>
  <span>Right item</span>
</div>
```

**Classes:**
- `.d-flex` - Enable flexbox
- `.justify-content-between`, `.justify-content-center` - Horizontal alignment
- `.align-items-center` - Vertical alignment
- `.gap-4` - Spacing between flex items

### Spacing Utilities
Margin and padding helpers.

```html
<div class="mb-4">Content with bottom margin</div>
<div class="p-3">Content with padding</div>
<div class="ms-auto">Auto left margin (push right)</div>
```

**Classes:**
- `.mb-4`, `.mb-2` - Margin bottom
- `.p-0`, `.p-3` - Padding
- `.ms-auto` - Margin start (left)
- `.w-100` - Width 100%
- `.gap-3`, `.gap-4` - Gap between flex/grid items

### Text Utilities

```html
<p class="text-muted">Muted/secondary text</p>
<p class="small">Small text</p>
<p class="fw-bold">Bold text</p>
<p class="text-truncate">Long text will truncate...</p>
<p class="text-danger">Error message in red</p>
```

**Classes:**
- `.text-muted` - Gray secondary text
- `.small` - Reduced font size
- `.fw-bold` - Font weight bold
- `.text-truncate` - Single line with ellipsis
- `.text-danger`, `.text-success` - Semantic colors

---

## Grid (Responsive Layout)

### 12-Column Grid
Bootstrap-compatible 12-column layout system.

```html
<div class="row g-3">
  <div class="col-md-6">
    <!-- 50% width on medium+ screens, 100% on small -->
  </div>
  <div class="col-md-6">
    <!-- 50% width on medium+ screens, 100% on small -->
  </div>
</div>
```

**Column Widths:**
- `.col-md-3` - 25% (3 of 12 columns)
- `.col-md-4` - 33.33% (4 of 12 columns)
- `.col-md-6` - 50% (6 of 12 columns)
- `.col-12` - 100% (full width)

**Responsive Breakpoints:**
- Small screens (default): Full width (100%)
- Medium+ (`md:`) and above: Specified column width
- Use `.col-md-*` prefix for medium breakpoint

---

## Dark Mode

All components automatically adapt to dark mode. The `.dark` class on the `<html>` element triggers dark mode styles:

```css
/* Light mode */
.card { @apply bg-white border-stroke; }

/* Dark mode */
.dark .card { @apply bg-boxdark border-strokedark; }
```

**No additional work needed** - Just ensure you're using the proper component classes and dark mode colors will be handled automatically.

---

## Integration Example

Complete admin page example combining multiple components:

```html
<div class="card mb-4">
  <div class="card-header fw-bold">Feature Flags</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped table-hover">
        <thead class="table-dark">
          <tr>
            <th scope="col">Name</th>
            <th scope="col">Status</th>
            <th scope="col">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Feature A</td>
            <td><span class="badge bg-success">Active</span></td>
            <td>
              <button class="btn btn-sm btn-warning">Edit</button>
              <button class="btn btn-sm btn-outline-danger">Disable</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
```

---

**See Also:**
- [Styling Guide](styling-guide.md)
- [Dark Mode Details](dark-mode.md)
- [TailAdmin Official Docs](https://tailadmin.com)
