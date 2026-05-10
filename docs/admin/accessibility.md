# Accessibility Guidelines

All admin pages must meet WCAG 2.1 Level AA accessibility standards. These guidelines ensure your admin pages are usable by everyone, including people with disabilities.

## Standards & Compliance

### WCAG 2.1 Level AA
The admin console targets **WCAG 2.1 Level AA** compliance:
- **Perceivable**: Content is visible and distinguishable
- **Operable**: Keyboard navigation and interactive controls work
- **Understandable**: Content is clear and language is appropriate
- **Robust**: Compatible with assistive technologies

### Color Contrast
All color combinations have been tested for contrast compliance:
- **Normal text**: Minimum 4.5:1 contrast ratio
- **Large text** (18pt+): Minimum 3:1 contrast ratio
- Verify with: DevTools → Inspect → Computed styles → Check contrast

## Semantic HTML

### Use Proper Elements
```html
<!-- ✓ Good - Semantic elements -->
<button type="submit">Submit</button>
<label for="email">Email</label>
<table>
  <thead><tr><th>Name</th></tr></thead>
  <tbody><tr><td>Data</td></tr></tbody>
</table>

<!-- ✗ Avoid - Non-semantic -->
<div role="button">Submit</div>
<span>Email</span>
<div>
  <div>Name</div>
  <div>Data</div>
</div>
```

### Heading Hierarchy
Use headings in logical order (h1 → h2 → h3), not for styling:

```html
<!-- ✓ Good -->
<h1>Page Title</h1>
<h2>Section One</h2>
<h3>Subsection</h3>
<h2>Section Two</h2>

<!-- ✗ Avoid -->
<h1>Page Title</h1>
<h3>Section One</h3>
<!-- Skipped h2 -->
<h2>Section Two</h2>
```

## Form Accessibility

### Label Every Input
Always associate labels with form inputs:

```html
<!-- ✓ Good -->
<label for="username" class="form-label">Username</label>
<input type="text" class="form-input" id="username" name="username">

<!-- ✗ Bad - Floating label (needs aria-label) -->
<input type="text" placeholder="Username">
```

### Indicate Required Fields
```html
<!-- ✓ Good - Visual and text indicator -->
<label for="name" class="form-label">Name <span class="text-danger">*</span></label>
<input type="text" id="name" name="name" required aria-required="true">

<!-- Or use asterisk with pattern -->
<label for="name" class="form-label">Name <abbr title="required">*</abbr></label>
```

### Help Text for Complex Fields
```html
<label for="api_key" class="form-label">API Key</label>
<input type="password" class="form-input" id="api_key" 
  aria-describedby="api_help">
<div id="api_help" class="form-text">
  Format: sk-... (keep secret)
</div>
```

### Form Validation
Provide clear error messages linked to fields:

```html
<div>
  <label for="email" class="form-label">Email</label>
  <input type="email" class="form-input" id="email" 
    aria-invalid="true" aria-describedby="email_error">
  <p id="email_error" class="text-danger text-sm mt-1">
    Please enter a valid email address
  </p>
</div>
```

## Keyboard Navigation

### Keyboard Shortcuts
All interactive elements must be keyboard accessible:

```html
<!-- ✓ Good - Tabbing order and interaction -->
<button type="submit" class="btn btn-primary">Submit</button>
<a href="/admin/users">Manage Users</a>
<input type="text" class="form-input">

<!-- ✗ Bad - Not keyboard accessible -->
<div class="btn" role="button">Submit</div>
<div class="link">Manage Users</div>
```

### Skip Links
Main layout includes skip links to jump over navigation:

```html
<?php require __DIR__ . '/_layout/skip_link.php'; ?>
<!-- Provided in root layout -->
<!-- Users press Alt+1 (or configurable) to skip to main -->
```

### Focus Indicators
All elements show focus indicator via Tailwind. Don't remove it:

```css
/* ✓ Good - Focus visible -->
button:focus-visible { outline: 2px solid #3c50e0; }

/* ✗ Bad - Removing focus indicator -->
button:focus { outline: none; }
```

## Interactive Components

### Buttons
Every button must have:
1. **Semantic `<button>` element** (or `<a>` for links)
2. **Clear label text** (screen readers read this)
3. **Focus indicator** (automatic with Tailwind)

```html
<!-- ✓ Good -->
<button type="submit" class="btn btn-primary">Save Changes</button>
<button type="button" class="btn btn-secondary" @click="cancel()">Cancel</button>

<!-- ✗ Bad -->
<div class="btn" role="button" @click="submit()">Save</div>
```

### Links vs Buttons
```html
<!-- ✓ Navigation (use <a>) -->
<a href="/admin/users" class="btn btn-secondary">Go to Users</a>

<!-- ✓ Action (use <button>) -->
<button type="submit" class="btn btn-primary">Delete User</button>

<!-- ✗ Mixed up -->
<a href="#" class="btn" @click.prevent="deleteUser()">Delete</a>
<!-- Should be <button> instead -->
```

### Icon-Only Buttons
Always provide accessible label:

```html
<!-- ✓ Good -->
<button type="button" aria-label="Close dialog" @click="close()">
  <svg><!-- icon --></svg>
</button>

<!-- ✗ Bad -->
<button type="button" @click="close()">
  <svg><!-- icon, no label --></svg>
</button>
```

## Tables

### Semantic Structure
```html
<!-- ✓ Good - Proper table semantics -->
<table aria-label="Users list">
  <thead>
    <tr>
      <th scope="col">Name</th>
      <th scope="col">Email</th>
      <th scope="col">Role</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>John</td>
      <td>john@example.com</td>
      <td>Admin</td>
    </tr>
  </tbody>
</table>

<!-- ✗ Avoid - Non-semantic -->
<div class="table">
  <div class="row">
    <div class="col">Name</div>
    <div class="col">Email</div>
  </div>
</div>
```

### Table Headers
Always use `<th>` with `scope` attribute:

```html
<table>
  <thead>
    <tr>
      <th scope="col">Column 1</th>
      <th scope="col">Column 2</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <th scope="row">Row Header</th>
      <td>Data</td>
    </tr>
  </tbody>
</table>
```

## Lists

### Proper List Markup
```html
<!-- ✓ Good -->
<ul>
  <li>Item 1</li>
  <li>Item 2</li>
  <li>Item 3</li>
</ul>

<!-- ✗ Avoid - Not a list -->
<div>• Item 1</div>
<div>• Item 2</div>
<div>• Item 3</div>
```

## Images & Icons

### Alternative Text
```html
<!-- ✓ Good - Meaningful alt text -->
<img src="chart.png" alt="Sales trend chart showing 20% growth in Q4">

<!-- ✗ Bad - Useless alt text -->
<img src="chart.png" alt="chart">
<img src="chart.png" alt="">
<!-- Empty alt is better than bad alt -->

<!-- For decorative images -->
<img src="decoration.png" alt="" aria-hidden="true">
```

### SVG Icons
```html
<!-- ✓ Good - Icon with label -->
<button aria-label="Delete item" @click="delete()">
  <svg><!-- trash icon --></svg>
</button>

<!-- Alternative: title in SVG -->
<button @click="delete()">
  <svg><title>Delete item</title><!-- icon --></svg>
</button>
```

## Alerts & Notifications

### Live Regions (ARIA)
For alerts that appear dynamically, use ARIA live regions:

```html
<!-- ✓ Good - Alert announced to screen readers -->
<div role="alert" aria-live="polite" aria-atomic="true">
  {{ message }}
</div>

<!-- For high-priority alerts -->
<div role="alert" aria-live="assertive">
  Error: Action failed!
</div>
```

### Alert Dialog
For critical messages:

```html
<!-- Modal alert that steals focus -->
<div role="alertdialog" aria-modal="true" aria-labelledby="alert-title">
  <h2 id="alert-title">Confirm Deletion</h2>
  <p>This action cannot be undone.</p>
  <button>Cancel</button>
  <button autofocus>Delete</button>
</div>
```

## Language & Text

### Language Specification
```html
<!-- ✓ Specify document language -->
<html lang="ja"><!-- Japanese -->

<!-- Or specify parts of page -->
<div lang="en">English text</div>
```

### Clear Language
- Use simple, direct language
- Define abbreviations on first use: <abbr title="Application Programming Interface">API</abbr>
- Use consistent terminology
- Break content into logical sections with headings

### Emphasis
```html
<!-- ✓ Semantic emphasis -->
<em>Important word</em>
<strong>Very important</strong>

<!-- ✗ Avoid visual-only -->
<span style="font-style: italic;">Important word</span>
```

## Testing

### Keyboard Navigation
Test without mouse:
1. Tab through all interactive elements
2. All controls reachable
3. Focus order is logical
4. Focus indicator always visible
5. Enter/Space activates buttons
6. Arrow keys work for lists/menus

### Screen Reader Testing
Test with screen reader (NVDA, JAWS, VoiceOver):
1. Page headings are announced
2. Form labels associated with inputs
3. Button purposes clear
4. Table headers announced
5. Alternative text for images
6. Error messages announced

### Color Contrast
Verify in DevTools:
1. Right-click element
2. Inspect → Computed
3. Scroll down to color properties
4. Check contrast ratio (should show ≥4.5:1)

### Lighthouse Audit
Run in DevTools:
1. F12 → Lighthouse tab
2. Select Accessibility
3. Generate report
4. Fix any violations

### Responsive Design
Test on multiple devices:
- Desktop (1280px+)
- Tablet (768px)
- Mobile (375px)
- Zoom to 200%

## Common Issues & Fixes

### Issue: Form Label Not Associated
```html
<!-- ✗ Wrong -->
<label>Username</label>
<input type="text" name="username">

<!-- ✓ Correct -->
<label for="username">Username</label>
<input type="text" id="username" name="username">
```

### Issue: Button Has No Keyboard Focus
```html
<!-- ✗ Removed focus (bad accessibility) -->
button { outline: none; }

<!-- ✓ Let browser handle focus -->
/* No outline override */
```

### Issue: Color Only Indicates Status
```html
<!-- ✗ Only red = error (colorblind users won't see it) -->
<div class="text-red-600">Error occurred</div>

<!-- ✓ Text + color -->
<div class="text-red-600">❌ Error occurred</div>
```

### Issue: Icon-Only Navigation
```html
<!-- ✗ No label -->
<a href="/admin"><i class="icon-users"></i></a>

<!-- ✓ With label -->
<a href="/admin" aria-label="Admin Panel"><i class="icon-users"></i></a>
```

## Resources

- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [MDN Accessibility](https://developer.mozilla.org/en-US/docs/Web/Accessibility)
- [WebAIM - Resources](https://webaim.org/)
- [Color Contrast Analyzer](https://www.tpgi.com/color-contrast-checker/)
- [axe DevTools Browser Extension](https://www.deque.com/axe/devtools/)

---

**See Also:** [Styling Guide](styling-guide.md), [Dark Mode](dark-mode.md), [Components](components.md)
