# Bootstrap to TailAdmin Migration Guide

If you're updating existing admin pages from Bootstrap 5 to TailAdmin/Tailwind CSS, this guide provides a reference for replacing Bootstrap classes with their TailAdmin equivalents.

## Quick Reference

### Navigation & Breadcrumbs
| Bootstrap | TailAdmin | Notes |
|-----------|-----------|-------|
| `.breadcrumb` | `.breadcrumb` | Flex layout with gap |
| `.breadcrumb-item` | `.breadcrumb-item` | List items with separators |
| `.breadcrumb-item.active` | `.breadcrumb-item.active` | Current page styling |

### Cards
| Bootstrap | TailAdmin | Notes |
|-----------|-----------|-------|
| `.card` | `.card` | Rounded border, shadow, dark mode support |
| `.card-header` | `.card-header` | Top section with border, padding |
| `.card-body` | `.card-body` | Content section with padding |
| `.card-footer` | `.card-body` | Use `.card-body` for additional sections |

### Forms
| Bootstrap | TailAdmin | Notes |
|-----------|-----------|-------|
| `.form-label` | `.form-label` | Display block with margin |
| `.form-control` | `.form-input` | Text inputs (email, password, URL, etc.) |
| `.form-select` | `.form-select` | Dropdown selects |
| `.form-check` | `.form-check` | Flex container for checkbox groups |
| `.form-check-input` | `.form-check-input` | Checkbox/radio input element |
| `.form-check-label` | `.form-check-label` | Label for checkbox/radio |
| `.form-text` | `.form-text` | Helper text below inputs |

### Buttons
| Bootstrap | TailAdmin | Notes |
|-----------|-----------|-------|
| `.btn .btn-primary` | `.btn-primary` | Use class directly on `<button>` |
| `.btn .btn-secondary` | `.btn-secondary` | Alternative style |
| `.btn .btn-success` | `.btn-success` | Green success button |
| `.btn .btn-danger` | `.btn-danger` | Red danger button |
| `.btn .btn-warning` | `.btn-warning` | Yellow caution button |
| `.btn .btn-outline-warning` | `.btn-outline-warning` | Bordered outline button |
| `.btn .btn-outline-danger` | `.btn-outline-danger` | Red outlined button |
| `.btn .btn-sm` | `.btn-sm` | Small compact button |

### Alerts
| Bootstrap | TailAdmin | Notes |
|-----------|-----------|-------|
| `.alert` | `.alert` | Base alert container |
| `.alert-success` | `.alert-success` | Green success alert |
| `.alert-warning` | `.alert-warning` | Yellow warning alert |
| `.alert-danger` | `.alert-danger` | Red error alert |
| `.alert-dismissible` | Remove attribute | Use Alpine.js with `x-show` |
| `.fade` | `.fade` | Transition animation |
| `.show` | `.show` | Visible state |
| `.btn-close` | `.btn-close` | Close button (now a component) |

### Badges
| Bootstrap | TailAdmin | Notes |
|-----------|-----------|-------|
| `.badge` | `.badge` | Inline status indicator |
| `.badge.bg-primary` | `.badge.bg-primary` | Blue badge |
| `.badge.bg-success` | `.badge.bg-success` | Green badge |
| `.badge.bg-danger` | `.badge.bg-danger` | Red badge |
| `.badge.bg-warning` | `.badge.bg-warning` | Yellow badge |
| `.badge.bg-secondary` | `.badge.bg-secondary` | Gray badge |
| `.text-dark` (on badge) | `.text-dark` | Dark text on light badge |

### Tables
| Bootstrap | TailAdmin | Notes |
|-----------|-----------|-------|
| `.table` | `.table` | Base table styling |
| `.table-striped` | `.table-striped` | Alternating row colors |
| `.table-hover` | `.table-hover` | Highlight on hover |
| `.table-dark` | `.table-dark` | Dark header background |
| `.table-responsive` | `.table-responsive` | Horizontal scroll on small screens |
| `.table-secondary` | `.table-secondary` | Row background color variant |
| `.table-warning` | `.table-warning` | Warning row background |

### Layout & Flexbox
| Bootstrap | TailAdmin | Notes |
|-----------|-----------|-------|
| `.d-flex` | `.d-flex` | Enable flexbox display |
| `.justify-content-between` | `.justify-content-between` | Space between items |
| `.justify-content-center` | `.justify-content-center` | Center items |
| `.align-items-center` | `.align-items-center` | Vertical center |
| `.gap-3` | `.gap-3` | 12px gap between items |
| `.gap-4` | `.gap-4` | 16px gap between items |

### Spacing
| Bootstrap | TailAdmin | Notes |
|-----------|-----------|-------|
| `.mb-0` | `.mb-0` | Margin bottom 0 |
| `.mb-2` | `.mb-2` | Margin bottom 8px |
| `.mb-3` | `.mb-3` | Margin bottom 12px |
| `.mb-4` | `.mb-4` | Margin bottom 16px |
| `.mb-6` | `.mb-6` | Margin bottom 24px |
| `.p-0` | `.p-0` | Padding 0 |
| `.p-3` | `.p-3` | Padding 12px |
| `.p-6` | `.p-6` | Padding 24px |
| `.ms-auto` | `.ms-auto` | Margin auto left (push right) |

### Text Utilities
| Bootstrap | TailAdmin | Notes |
|-----------|-----------|-------|
| `.text-muted` | `.text-muted` | Gray secondary text |
| `.text-dark` | `.text-dark` | Dark/black text |
| `.text-danger` | `.text-danger` | Red error text |
| `.text-truncate` | `.text-truncate` | Single line ellipsis |
| `.small` | `.small` | Reduced font size |
| `.fw-bold` | `.fw-bold` | Font weight bold |

### Grid System
| Bootstrap | TailAdmin | Notes |
|-----------|-----------|-------|
| `.row` | `.row` | 12-column flex grid |
| `.col-md-3` | `.col-md-3` | 25% width on md+ screens |
| `.col-md-4` | `.col-md-4` | 33.33% width on md+ screens |
| `.col-md-5` | `.col-md-5` | 41.67% width on md+ screens |
| `.col-md-6` | `.col-md-6` | 50% width on md+ screens |
| `.col-12` | `.col-12` | Full width |
| `.g-3` | `.g-3` | 12px gap in grid |
| `.g-4` | `.g-4` | 16px gap in grid |

## Migration Strategy

### Step 1: Update CSS File
Add the new TailAdmin component classes to `/css/input.css` (already done in this version).

### Step 2: Update HTML Structure
Replace Bootstrap classes with TailAdmin equivalents:

```php
<!-- Before (Bootstrap) -->
<div class="card mb-4">
  <div class="card-header fw-bold">Title</div>
  <div class="card-body">Content</div>
</div>

<!-- After (TailAdmin) -->
<div class="card mb-4">
  <div class="card-header fw-bold">Title</div>
  <div class="card-body">Content</div>
</div>
<!-- No HTML change needed - CSS handles it! -->
```

### Step 3: Update JavaScript References
Replace Bootstrap JavaScript with Alpine.js:

```php
<!-- Before (Bootstrap) -->
<div class="alert alert-success alert-dismissible fade show" role="alert">
  Message
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>

<!-- After (Alpine.js) -->
<div class="alert alert-success fade show" role="alert" 
  x-data="{ show: true }" x-show="show">
  <div class="flex items-start justify-between gap-3">
    <span>Message</span>
    <button type="button" class="btn-close" @click="show = false"></button>
  </div>
</div>
```

### Step 4: Test Dark Mode
1. Toggle dark mode in header
2. Verify all elements are visible
3. Check contrast ratios pass WCAG AA

### Step 5: Test Responsiveness
1. Test on mobile (375px)
2. Test on tablet (768px)
3. Test on desktop (1280px)

## Common Issues

### Issue: Styles Not Applied
**Solution:** Ensure you're using the correct class names. Compare with reference in this guide.

### Issue: Dark Mode Text Invisible
**Solution:** Add `.dark:` prefixes for dark mode colors:
```html
<p class="text-black dark:text-white">Text</p>
```

### Issue: Old Bootstrap Classes Still in CSS
**Solution:** Verify `/css/app.css` has all TailAdmin components. Check line count matches expected CSS size.

### Issue: Buttons Look Wrong
**Solution:** Don't mix button classes:
```html
<!-- ✗ Wrong - mixing classes -->
<button class="btn btn-primary btn-outline">Wrong</button>

<!-- ✓ Correct -->
<button class="btn-primary">Correct</button>
<!-- or -->
<button class="btn-outline-primary">Outlined</button>
```

## Bootstrap Components Not Directly Supported

### Modals
TailAdmin doesn't include modal components. Options:
- Use Alpine.js `x-show` with overlay
- Create custom modal CSS
- Use headless UI library

### Spinners/Loaders
Bootstrap spinners are not included. Options:
- Use SVG spinners
- Create CSS animations
- Use Alpine.js library

### Carousels
Bootstrap carousels not included. Options:
- Use Alpine.js for carousel logic
- Use dedicated carousel library
- Create custom CSS transitions

## Validation Checklist

After migrating a page:
- [ ] All text is visible in light mode
- [ ] All text is visible in dark mode
- [ ] Buttons have proper hover/focus states
- [ ] Forms are properly styled and functional
- [ ] Tables render correctly
- [ ] Badges show proper colors
- [ ] Alerts display and can be dismissed
- [ ] Layout is responsive on mobile/tablet/desktop
- [ ] No Bootstrap CDN CSS is loaded
- [ ] Dark mode toggle works

---

**See Also:** [Components](components.md), [Styling Guide](styling-guide.md), [Dark Mode](dark-mode.md)
