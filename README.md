# YSE Views Stratify Module

A Drupal module that provides **convention-based view stratification** to split view results into two exclusive sets: featured/promoted items and remaining items.

## Overview

The **yse_views_stratify** module enables developers to easily stratify Drupal views by splitting results into two non-overlapping groups based on display naming conventions. This is useful for layouts where you want to display featured content prominently and remaining content separately.

### What is Stratification?

Stratification is the process of dividing a dataset into distinct, non-overlapping subsets. In this module:

- **Exclusive rows** - Contains ONLY the items matching the stratification criteria
- **Remainder rows** - Contains ALL other items (excluding the stratified set)

This approach ensures every item appears in exactly one display, making it ideal for layouts with featured and non-featured content sections.

## Features

- **Convention-based configuration** - No module-level settings needed; configuration lives entirely within the view
- **Self-contained** - All stratification logic is handled within the view definition
- **Context-aware** - Respects contextual filters and route arguments from the current request
- **Per-request caching** - Multiple displays sharing the same stratified results benefit from request-level caching
- **Multi-entity support** - Works with nodes, users, taxonomy terms, media, comments, and custom entities
- **Automatic cache invalidation** - Manages cache tags to ensure proper cache invalidation
- **Error handling** - Validates display configurations and logs errors gracefully

## How It Works

### Architecture

The module works through a simple convention-based system:

1. **Stratify Query Display** - An embedded view display named `embed_stratify_query` that defines which items should be stratified
2. **Exclusive Displays** - Any display with `exclusive_rows` in its machine name shows ONLY items from the stratify query
3. **Remainder Displays** - Any display with `remainder_rows` in its machine name shows ALL items EXCEPT those from the stratify query

### Query Alteration

When a view is executed:

1. The module's `hook_views_query_alter()` checks if the view has an `embed_stratify_query` display
2. If the current display is an exclusive or remainder display, the stratify query is executed
3. Entity IDs from the stratify query results are extracted
4. SQL WHERE conditions (IN or NOT IN clauses) are added to filter results appropriately

### Contextual Filter Inheritance

The module automatically inherits contextual filters from the current display to the stratify query. This ensures that if you're viewing filtered results (e.g., "featured articles in category X"), the stratify query respects that same filtering.

### Caching Strategy

- **Request-level caching** - Stratified entity IDs are cached per request to avoid redundant queries
- **Cache key generation** - Based on view ID and serialized arguments
- **Cache tags** - Proper cache tags are added to ensure invalidation when configurations change

## Usage Example

### Setup

1. Create a view with multiple displays:

   - **Base display** - Shows all articles
   - **embed_stratify_query** - Embedded display with filters to identify featured articles (e.g., tagged with "Featured")
   - **featured_exclusive_rows** - Shows only articles from the stratify query (featured articles)
   - **remaining_remainder_rows** - Shows all other articles (non-featured)

2. Configure filters on `embed_stratify_query` to define what makes an article "featured"

3. The module handles the rest automatically

### Configuration Details

**Embed Stratify Query Display:**
- Machine name: `embed_stratify_query` (required - exact match)
- Display plugin: Must be "Embed"
- Configuration: Add filters/conditions to define stratified items
- Example: Add a "Promoted" or "Featured" taxonomy term filter

**Exclusive Rows Displays:**
- Machine name must contain: `exclusive_rows`
- Examples: `page_exclusive_rows`, `featured_exclusive_rows`, `exclusive_rows_hero`
- Result: Shows ONLY items matching stratify query conditions

**Remainder Rows Displays:**
- Machine name must contain: `remainder_rows`
- Examples: `page_remainder_rows`, `regular_remainder_rows`, `remainder_rows_grid`
- Result: Shows ALL items EXCEPT those matching stratify query conditions

## Use Cases

### 1. Featured Articles Homepage

Display featured articles prominently with remaining articles below:

- Featured articles section: Uses exclusive_rows display (only promoted articles)
- Main articles section: Uses remainder_rows display (all other articles)

### 2. Category Page with Promotions

Show promoted items first, then regular category content:

- Hero slider section: Featured items from current category
- Content grid: All other items in current category
- Respects category URL parameter via contextual filters

### 3. User Bookmarks + Recommendations

Separate user content from suggestions:

- Bookmarked content section: User's own bookmarked items (exclusive)
- Recommendations section: Suggested items not in bookmarks (remainder)

### 4. Breaking News Header

Display breaking news separately from regular news:

- Breaking news banner: Only items tagged with "Breaking"
- News feed: Regular news articles

## Technical Implementation

### File Structure

```
yse_views_stratify/
├── yse_views_stratify.info.yml          # Module registration
├── yse_views_stratify.module            # Hook implementations
├── yse_views_stratify.services.yml      # Service definition
├── src/
│   └── ViewsStratifyService.php         # Core stratification service
└── tests/
    └── src/Unit/
        └── ViewsStratifyServiceTest.php # Unit tests
```

### Core Components

**ViewsStratifyService** (`src/ViewsStratifyService.php`)

Main service class handling all stratification logic:

- `alterQuery()` - Entry point for query modifications (called via hook_views_query_alter)
- `addCacheTags()` - Manages cache invalidation (called via hook_views_pre_render)
- `shouldApplyStratification()` - Detects if a view has stratification enabled
- `isExclusiveDisplay()` / `isRemainderDisplay()` - Pattern matching for display types
- `getStratifiedEntityIds()` - Executes the stratify query and caches results
- `applyExclusiveFilter()` - Adds IN WHERE clause for exclusive displays
- `applyRemainderFilter()` - Adds NOT IN WHERE clause for remainder displays
- `inheritContextualFilters()` - Passes contextual arguments from parent display
- `getContextualArguments()` - Retrieves arguments respecting request context
- `getBaseField()` - Maps base tables to ID field names (nid, uid, tid, etc.)

**Hook Implementations** (`yse_views_stratify.module`)

- `hook_views_query_alter()` - Applies stratification filters to view queries
- `hook_views_pre_render()` - Adds appropriate cache tags
- `hook_theme_suggestions_views_view()` - Adds theme suggestions for view templates
- `hook_theme_suggestions_views_view_fields()` - Adds theme suggestions for field wrapper templates
- `hook_theme_suggestions_views_view_field()` - Adds theme suggestions for individual field templates
- `hook_theme_suggestions_views_view_unformatted()` - Adds theme suggestions for unformatted style templates
- `hook_theme_suggestions_views_view_table()` - Adds theme suggestions for table style templates
- `hook_theme_suggestions_views_view_grid()` - Adds theme suggestions for grid style templates
- `hook_theme_suggestions_views_view_list()` - Adds theme suggestions for list style templates

See [TWIG_SUGGESTIONS_IMPLEMENTATION.md](TWIG_SUGGESTIONS_IMPLEMENTATION.md) for details on how to create custom templates for stratified views, including style-specific templates.

### Dependency Injection

The service receives two dependencies:

- `LoggerChannelFactoryInterface` - For error logging
- `RequestStack` - For accessing current request context

### Entity Type Support

The module automatically handles multiple entity types:

| Entity Type | Base Table | ID Field |
|---|---|---|
| Node | node_field_data | nid |
| User | users_field_data | uid |
| Taxonomy Term | taxonomy_term_field_data | tid |
| Media | media_field_data | mid |
| Comment | comment_field_data | cid |
| Custom | [table_name] | id |

## Error Handling

The module includes comprehensive error handling:

### Validation

- **Display type validation** - Ensures `embed_stratify_query` is actually an embed display
- **Conflict detection** - Prevents displays from having both `exclusive_rows` and `remainder_rows`
- **Display existence** - Checks that required displays exist

### Logging

Error conditions are logged to the `yse_views_stratify` logger channel:

- **Warnings** - Invalid display types, missing views, missing displays
- **Errors** - Query execution failures, filter application errors

### Graceful Degradation

If an error occurs:

1. The view continues to display without stratification
2. Error is logged for debugging
3. No exception is thrown (prevents white screen of death)

## Performance Considerations

### Caching

- **Request-level caching** - Stratified IDs are cached per request
- **Multiple displays** - If three displays use the same stratify query, it's only executed once per request
- **Cache key** - Includes view ID and argument hash for proper cache separation

### Query Optimization

- **Single pass** - The stratify query is executed once per request, not per display
- **SQL filtering** - IN/NOT IN clauses are efficient for filtering large result sets
- **No extra joins** - Stratification uses simple WHERE conditions

### Recommendations

For optimal performance:

1. Keep the stratify query filters simple and indexed
2. Avoid complex relationships in the stratify query
3. Limit the stratify query result set with reasonable filters
4. Consider pagination if dealing with large datasets

## Advanced Topics

### Contextual Filter Inheritance

When using the module with nested views or contextual filters:

1. Contextual filters from the current display are automatically detected
2. The stratify query inherits these filters via `setArguments()`
3. If the stratify query has matching argument handlers, they're applied automatically
4. If not, arguments are applied directly via query WHERE conditions

### Nested View Contexts

For nested views (using the Views area handler):

1. The module checks the current request's route parameters
2. Arguments are extracted from `_raw_variables` attributes
3. These are passed to the stratify query to maintain consistency

### Custom Entity Types

To use with custom entity types:

1. The module attempts to auto-detect the ID field
2. Falls back to common naming patterns (nid, uid, tid, mid, cid)
3. Falls back to 'id' as the last resort

If your entity uses a different ID field name, consider extending the service's `getBaseField()` method.

## Testing

The module includes comprehensive unit tests (`tests/src/Unit/ViewsStratifyServiceTest.php`):

- Display type detection (`isExclusiveDisplay`, `isRemainderDisplay`)
- Display validation (conflicting markers)
- Cache key generation
- Base field mapping for various entity types

Run tests with:

```bash
phpunit -c web/core/phpunit.xml.dist --filter ViewsStratifyServiceTest modules/custom/yse_views_stratify/tests/
```

## Troubleshooting

### Stratification Not Applied

**Check:**
1. Is `embed_stratify_query` display present and of type "Embed"?
2. Does the exclusive/remainder display name contain the correct keywords?
3. Are there any errors in the watchdog logs?

### Wrong Results Showing

**Check:**
1. Verify the stratify query filters are correct
2. Ensure contextual filters are properly configured
3. Check that the stratify query is actually returning results

### Performance Issues

**Check:**
1. Is the stratify query efficient? (Check query time)
2. Are indexes in place on filtered fields?
3. Is the result set too large? (Consider pagination)

### Display Name Issues

**Common mistakes:**
- Misspelling `embed_stratify_query` (must be exact)
- Using `exclusive` instead of `exclusive_rows`
- Using `remaining` instead of `remainder_rows`

## API Reference

### Service: `yse_views_stratify.views_stratify`

```php
// Inject the service
$service = \Drupal::service('yse_views_stratify.views_stratify');

// Note: Direct service calls are typically not needed; the module
// handles everything via hooks. These are documented for completeness.
```

### Hooks

**hook_views_query_alter()**

The module implements this hook to:
1. Check for inherited filters that need applying
2. Call the stratification service

**hook_views_pre_render()**

The module implements this hook to:
1. Add appropriate cache tags to stratified views

### Logger Channel

```php
// Access the module's logger channel
$logger = \Drupal::logger('yse_views_stratify');

// Logged events include:
// - Errors applying filters
// - Invalid display configurations
// - Query execution errors
```

## Contributing

When extending or modifying this module:

1. Follow Drupal coding standards (see CLAUDE.md)
2. Maintain PHP 8.3+ compatibility
3. Add tests for new functionality
4. Update this documentation for user-facing changes
5. Avoid debug logging in production code

## Module Dependencies

- Drupal Views module (required)
- Drupal Core (8.3+)

## Compatibility

- **Drupal versions:** 10.0+, 11.0+
- **PHP versions:** 8.3+

## License

[Check your project's LICENSE file]

## Author

YSE Project Team

---

## Summary

The yse_views_stratify module provides a clean, convention-based approach to stratifying views without requiring complex configuration. By following simple naming conventions in your view displays, you can automatically split results into featured and regular content groups, making it ideal for modern web layouts with promoted and regular content sections.
