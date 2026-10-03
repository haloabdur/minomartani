# AdminLTE 3 Flexbox Clearfix Gotcha (`::after` Flex Item Issue)

## The Issue
AdminLTE 3 adds a legacy clearfix pseudo-element to card containers:
```css
.card-header::after, .card-body::after, .card-footer::after {
    display: block;
    clear: both;
    content: "";
}
```

In CSS Flexbox specifications, **any pseudo-element (`::before` / `::after`) inside a flex container is treated as an anonymous flex child item**.

When developers apply `.d-flex.justify-content-between` directly to `.card-header`, `.card-body`, or `.card-footer`:
- Item 1 (left element / button) gets placed at 0% (far left)
- Item 2 (right element / button) gets pushed to ~50% (the center!)
- Item 3 (the invisible `::after` clearfix) takes the 100% spot (far right)!

This causes badges, action buttons, and tools to appear stuck in the middle of the card instead of at the far right.

## Mandatory Rules & Solution
1. **Global CSS Reset (in `app/Views/layouts/header.php`)**:
   Disable `::before` and `::after` on card elements that have `d-flex`:
   ```css
   .card-header.d-flex::before,
   .card-header.d-flex::after,
   .card-body.d-flex::before,
   .card-body.d-flex::after,
   .card-footer.d-flex::before,
   .card-footer.d-flex::after {
       display: none !important;
   }
   ```
2. **Component Markup Rules**:
   - In `.card-header`, always put right-aligned items/badges inside `<div class="card-tools">` or `<div class="card-tools ml-auto">`, NEVER bare `<span class="badge">` directly in `.card-header`.
   - In `.card-body` or `.card-footer`, do NOT put `.d-flex.justify-content-between` directly on the card container. Instead, wrap the items in an inner `<div class="d-flex justify-content-between align-items-center w-100">` and add `ml-auto` to the right-side button group.
