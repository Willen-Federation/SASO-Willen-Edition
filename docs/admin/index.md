# Admin Console

The SASO admin console provides a centralized interface for managing system configuration, users, and settings. This section documents the admin interface architecture, styling patterns, and best practices for developing new admin pages.

## Overview

The admin console is built with:
- **PHP** - Server-side templating and logic
- **TailAdmin** - Tailwind CSS-based dashboard template
- **Alpine.js** - Client-side interactivity and theme management
- **Material for MkDocs** - Documentation (this site)

## Key Features

### Dark Mode Support
All admin pages support light and dark mode, toggled via the theme button in the header. The theme preference is persisted in localStorage and syncs with OS preferences.

### Responsive Design
Admin pages adapt to mobile, tablet, and desktop viewports using Tailwind's responsive utilities (e.g., `sm:`, `md:`, `lg:` breakpoints).

### Accessibility
Admin pages follow WCAG 2.1 AA standards with:
- Semantic HTML
- Keyboard navigation
- Focus indicators
- Color contrast requirements
- ARIA labels for screen readers

### Color Tokens
All colors use CSS custom properties (CSS variables) that respect light/dark mode:
- `--saso-text` - Primary text color
- `--saso-body` - Body background color
- `--saso-card` - Card background color
- And many more (see `/css/app.css`)

## Admin Pages

### Built-in Admin Pages

| Page | URL | Purpose |
|------|-----|---------|
| Authentication Providers | `/admin/auth/` | Manage OIDC, SAML, and local auth providers |
| Feature Flags | `/admin/flags/` | Toggle and configure feature flags |
| Mobile Devices | `/admin/mobile/` | Manage paired mobile devices and tokens |
| AI Settings | `/admin/ai-settings/` | Configure AI providers and API keys |
| Firebase Settings | `/admin/firebase/` | Configure Firebase project settings |

## Architecture

### View Layer
Admin pages are implemented as PHP View classes in the `/admin/` directory:

```php
<?php
namespace saso\admin;

final class AuthView implements View {
  private string $title = '';
  private \Closure $content;

  public function display(): void {
    // Logic: fetch data, handle actions, etc.
    $this->title = 'Page Title';
    $this->content = function($v) {
      // HTML/template code
    };
  }

  public function getTitle(): string { return $this->title; }
  public function getContent(): \Closure { return $this->content; }
}
```

### Layout
All admin pages inherit the root layout (`/root/template/root.php`):
- Sidebar navigation
- Header with theme toggle and user menu
- Main content area
- Footer

### Styling
Admin pages use TailAdmin components defined in `/css/input.css`:
- `.card`, `.card-header`, `.card-body` - Card layout
- `.btn-primary`, `.btn-danger`, etc. - Button styles
- `.form-input`, `.form-label` - Form elements
- `.table`, `.table-striped` - Tables
- `.badge` - Status badges
- And many more...

## Getting Started

### Adding a New Admin Page

1. **Create the View class** in `/admin/YourPageView.php`:
```php
<?php
namespace saso\admin;

final class YourPageView implements View {
  private string $title = '';
  private \Closure $content;

  public function display(): void {
    $this->title = 'Your Page Title';
    $this->content = function($v) { 
      // Your HTML here
    };
  }

  // ... getTitle(), getContent(), onRoot() methods
}
```

2. **Register the route** in the router configuration
3. **Use TailAdmin components** for styling (see `components.md`)
4. **Test in light and dark modes**
5. **Document** the page purpose and features

### Styling Guidelines

See `styling-guide.md` for comprehensive guidelines on:
- Spacing and sizing
- Color usage
- Typography
- Component patterns
- Responsive design

## Common Patterns

### Flash Messages
Display temporary success/error messages:

```php
<?php if ($flashMsg !== null): ?>
<div class="alert alert-<?php echo $flashType; ?> fade show mb-4" role="alert" 
  x-data="{ show: true }" x-show="show">
  <div class="flex items-start justify-between gap-3">
    <span><?php echo $flashMsg; ?></span>
    <button type="button" class="btn-close" @click="show = false" aria-label="閉じる"></button>
  </div>
</div>
<?php endif; ?>
```

### Data Tables
Display tabular data with striped rows and hover effects:

```php
<div class="table-responsive">
  <table class="table table-striped table-hover" aria-label="Items">
    <thead class="table-dark">
      <tr>
        <th scope="col">Column 1</th>
        <th scope="col">Column 2</th>
      </tr>
    </thead>
    <tbody>
      <!-- rows -->
    </tbody>
  </table>
</div>
```

### Forms
Use semantic form structure with proper labels and validation:

```php
<div class="col-md-4">
  <label for="field_name" class="form-label">Field Name</label>
  <input type="text" class="form-control" id="field_name" name="field_name" required>
</div>
```

## References

- [TailAdmin Documentation](https://tailadmin.com)
- [Tailwind CSS Utilities](https://tailwindcss.com/docs)
- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [Alpine.js Documentation](https://alpinejs.dev)

---

**Next Steps:**
- Learn about [TailAdmin Components](components.md)
- Follow the [Styling Guide](styling-guide.md)
- Understand [Dark Mode](dark-mode.md)
- Check the [Migration Guide](migration-guide.md) (Bootstrap to TailAdmin)
- Review [Accessibility Guidelines](accessibility.md)
