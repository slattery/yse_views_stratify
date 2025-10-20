# Twig Suggestions Implementation

This document describes the twig theme suggestions added to the yse_views_stratify module for customizing view templates.

## Overview

The yse_views_stratify module implements three Drupal theme suggestion hooks to provide flexible template override capabilities for views that use stratification:

- `hook_theme_suggestions_views_view()`
- `hook_theme_suggestions_views_view_fields()`
- `hook_theme_suggestions_views_view_field()`

These hooks are implemented in `yse_views_stratify.module` and automatically add suggestions whenever a view contains an `embed_stratify_query` display.

## How It Works

For any view with an `embed_stratify_query` display, the module adds custom twig suggestions in addition to Drupal's standard suggestions. This allows you to:

1. Create generic templates that apply to any stratified view
2. Create specific templates for particular views
3. Create display-specific templates for exclusive or remainder displays
4. Override individual field templates based on display type

## Available Suggestions

### 1. Views View Templates

These suggestions apply to the main view rendering template.

**Generic suggestions (all views with embed_stratify_query):**
- `views-view--views-stratify.html.twig`

**For exclusive_rows displays:**
- `views-view--[viewid]--exclusive_rows.html.twig` (specific view)
- `views-view--views-stratify--exclusive_rows.html.twig` (generic)

**For remainder_rows displays:**
- `views-view--[viewid]--remainder_rows.html.twig` (specific view)
- `views-view--views-stratify--remainder_rows.html.twig` (generic)

**Example:**
If you have a view called `articles` with displays `featured_exclusive_rows` and `regular_remainder_rows`:

- `views-view--articles--exclusive_rows.html.twig` (for featured articles)
- `views-view--views-stratify--exclusive_rows.html.twig` (generic featured articles template)
- `views-view--articles--remainder_rows.html.twig` (for regular articles)
- `views-view--views-stratify--remainder_rows.html.twig` (generic remainder template)

### 2. Views View Fields Templates

These suggestions apply to the fields wrapper template (the container for all fields in a row).

**Generic suggestions (all views with embed_stratify_query):**
- `views-view-fields--views-stratify.html.twig`

**For exclusive_rows displays:**
- `views-view-fields--[viewid]--exclusive_rows.html.twig` (specific view)
- `views-view-fields--views-stratify--exclusive_rows.html.twig` (generic)

**For remainder_rows displays:**
- `views-view-fields--[viewid]--remainder_rows.html.twig` (specific view)
- `views-view-fields--views-stratify--remainder_rows.html.twig` (generic)

### 3. Views View Field Templates

These suggestions apply to individual field rendering templates.

**For exclusive_rows displays:**
- `views-view-field--views-stratify--exclusive_rows.html.twig` (generic)
- `views-view-field--[viewid]--exclusive_rows.html.twig` (specific view)

**For remainder_rows displays:**
- `views-view-field--views-stratify--remainder_rows.html.twig` (generic)
- `views-view-field--[viewid]--remainder_rows.html.twig` (specific view)

### 4. Style-Specific View Templates

The module also provides suggestions for different view styles (unformatted, table, grid, list). These allow you to customize how stratified content is rendered with different formatting styles.

#### Unformatted Style
These suggestions apply when a view uses the "Unformatted list" style.

**Generic suggestions (all views with embed_stratify_query):**
- `views-view-unformatted--views-stratify.html.twig`

**For exclusive_rows displays:**
- `views-view-unformatted--[viewid]--exclusive_rows.html.twig` (specific view)
- `views-view-unformatted--views-stratify--exclusive_rows.html.twig` (generic)

**For remainder_rows displays:**
- `views-view-unformatted--[viewid]--remainder_rows.html.twig` (specific view)
- `views-view-unformatted--views-stratify--remainder_rows.html.twig` (generic)

#### Table Style
These suggestions apply when a view uses the "Table" style.

**Generic suggestions (all views with embed_stratify_query):**
- `views-view-table--views-stratify.html.twig`

**For exclusive_rows displays:**
- `views-view-table--[viewid]--exclusive_rows.html.twig` (specific view)
- `views-view-table--views-stratify--exclusive_rows.html.twig` (generic)

**For remainder_rows displays:**
- `views-view-table--[viewid]--remainder_rows.html.twig` (specific view)
- `views-view-table--views-stratify--remainder_rows.html.twig` (generic)

#### Grid Style
These suggestions apply when a view uses the "Grid" style.

**Generic suggestions (all views with embed_stratify_query):**
- `views-view-grid--views-stratify.html.twig`

**For exclusive_rows displays:**
- `views-view-grid--[viewid]--exclusive_rows.html.twig` (specific view)
- `views-view-grid--views-stratify--exclusive_rows.html.twig` (generic)

**For remainder_rows displays:**
- `views-view-grid--[viewid]--remainder_rows.html.twig` (specific view)
- `views-view-grid--views-stratify--remainder_rows.html.twig` (generic)

#### List Style
These suggestions apply when a view uses the "HTML list" style.

**Generic suggestions (all views with embed_stratify_query):**
- `views-view-list--views-stratify.html.twig`

**For exclusive_rows displays:**
- `views-view-list--[viewid]--exclusive_rows.html.twig` (specific view)
- `views-view-list--views-stratify--exclusive_rows.html.twig` (generic)

**For remainder_rows displays:**
- `views-view-list--[viewid]--remainder_rows.html.twig` (specific view)
- `views-view-list--views-stratify--remainder_rows.html.twig` (generic)

#### YSE Slider Style
These suggestions apply when a view uses the "YSE Slider" style.

**Generic suggestions (all views with embed_stratify_query):**
- `views-view-yse-slider--views-stratify.html.twig`

**For exclusive_rows displays:**
- `views-view-yse-slider--[viewid]--exclusive_rows.html.twig` (specific view)
- `views-view-yse-slider--views-stratify--exclusive_rows.html.twig` (generic)

**For remainder_rows displays:**
- `views-view-yse-slider--[viewid]--remainder_rows.html.twig` (specific view)
- `views-view-yse-slider--views-stratify--remainder_rows.html.twig` (generic)

## Template Location

All custom twig templates should be placed in your theme directory:

```
your-theme/
├── templates/
│   └── views/
│       ├── views-view--views-stratify.html.twig
│       ├── views-view--views-stratify--exclusive_rows.html.twig
│       ├── views-view--views-stratify--remainder_rows.html.twig
│       ├── views-view-fields--views-stratify.html.twig
│       ├── views-view-fields--views-stratify--exclusive_rows.html.twig
│       ├── views-view-fields--views-stratify--remainder_rows.html.twig
│       ├── views-view-field--views-stratify--exclusive_rows.html.twig
│       ├── views-view-field--views-stratify--remainder_rows.html.twig
│       ├── views-view-unformatted--views-stratify.html.twig
│       ├── views-view-unformatted--views-stratify--exclusive_rows.html.twig
│       ├── views-view-unformatted--views-stratify--remainder_rows.html.twig
│       ├── views-view-table--views-stratify.html.twig
│       ├── views-view-table--views-stratify--exclusive_rows.html.twig
│       ├── views-view-table--views-stratify--remainder_rows.html.twig
│       ├── views-view-grid--views-stratify.html.twig
│       ├── views-view-grid--views-stratify--exclusive_rows.html.twig
│       ├── views-view-grid--views-stratify--remainder_rows.html.twig
│       ├── views-view-list--views-stratify.html.twig
│       ├── views-view-list--views-stratify--exclusive_rows.html.twig
│       ├── views-view-list--views-stratify--remainder_rows.html.twig
│       ├── views-view-yse-slider--views-stratify.html.twig
│       ├── views-view-yse-slider--views-stratify--exclusive_rows.html.twig
│       └── views-view-yse-slider--views-stratify--remainder_rows.html.twig
```

Or in a custom module's templates directory:

```
your-module/
├── templates/
│   └── views/
│       └── [template files]
```

## Template Hierarchy & Specificity

Drupal's theme engine uses template suggestions in order of specificity, loading the first one it finds. The order matters:

For a view named `articles` on the `featured_exclusive_rows` display:

1. `views-view--articles--featured-exclusive-rows.html.twig` (stock suggestion - exact display match)
2. `views-view--articles--exclusive-rows.html.twig` (module suggestion - specific view + exclusive marker)
3. `views-view--views-stratify--exclusive-rows.html.twig` (module suggestion - generic exclusive marker)
4. `views-view--articles.html.twig` (stock suggestion - specific view)
5. `views-view--views-stratify.html.twig` (module suggestion - generic)
6. `views-view.html.twig` (core fallback)

This hierarchy allows you to:
- Use the generic `views-stratify` templates as a catch-all for all stratified views
- Override with specific view templates when needed
- Mix and match depending on your theme requirements

## Variables Available in Templates

### views-view templates
- `view` - The view object
- `rows` - The rendered view rows
- `header` - Header output
- `footer` - Footer output
- `pager` - Pager output
- `title` - View title
- `exposed` - Exposed filters
- And more (see Drupal Views documentation)

### views-view-fields templates
- `fields` - Array of field objects
- `rows` - The rendered rows

### views-view-field templates
- `field` - The field object
- `field_name` - Name of the field
- `label` - Field label (if configured)
- `content` - Rendered field content
- And more (see Drupal Views documentation)

## Performance Considerations

- Template suggestions are only added if the view contains an `embed_stratify_query` display
- The checks are minimal and use cached display information
- No additional database queries are executed to determine suggestions

## Troubleshooting

### Suggestions Not Appearing

1. **Verify the display exists:**
   - Ensure the view has an `embed_stratify_query` display
   - Check the display machine name is spelled correctly (exact match required)

2. **Check display name markers:**
   - For exclusive suggestions: verify display name contains `exclusive_rows`
   - For remainder suggestions: verify display name contains `remainder_rows`
   - Example: `featured_exclusive_rows` or `regular_remainder_rows`

3. **Clear theme cache:**
   ```bash
   drush cache:rebuild
   ```

4. **Debug theme suggestions:**
   Add to theme settings to see all available suggestions:
   ```php
   // Add to settings.local.php
   $settings['theme_debug'] = TRUE;
   ```
   Then check HTML source for theme suggestions in comments.

### Template Not Loading

1. **File naming:**
   - Use hyphens in filenames: `views-view--views-stratify.html.twig`
   - Replace underscores in suggestions with hyphens in filenames

2. **File location:**
   - Templates must be in `templates/views/` directory
   - Check your theme's `templates/` folder structure

3. **Clear cache:**
   ```bash
   drush cache:rebuild
   ```

## Integration with Custom Modules

If you're creating a custom module that works with stratified views, you can:

1. **Create module-level templates** in your module's `templates/` directory
2. **Implement your own suggestions** in addition to these
3. **Use template_preprocess hooks** to modify variables before rendering

Example in a custom module:

```php
/**
 * Implements template_preprocess_views_view().
 */
function my_module_preprocess_views_view(&$variables) {
  $view = $variables['view'];

  // Check if this is a stratified view
  $displays = $view->storage->get('display');
  if (isset($displays['embed_stratify_query'])) {
    // Add custom variables or logic
    $variables['is_stratified'] = TRUE;
  }
}
```

## See Also

- [Drupal Views Template Suggestions](https://www.drupal.org/docs/drupal-apis/render-api/theme-system/theme-suggestions)
- [Drupal Twig Documentation](https://www.drupal.org/docs/8/theming/twig)
- [yse_views_stratify Module README](README.md)
