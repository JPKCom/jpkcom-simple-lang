# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

This is a WordPress plugin called **JPKCom Simple Lang** - a lightweight solution for per-post language selection that overrides the site-wide language setting in the frontend. It allows content editors to display individual pages or posts in different languages without the complexity of full multilingual plugins.

**Requirements:**
- WordPress 6.8+
- PHP 8.3+
- At least one additional language pack installed (optional but recommended)

**Optional:**
- Oxygen Builder for conditional logic support

## Architecture

### Core Plugin Structure

The plugin uses a **modular file loader pattern** with override capabilities. The main file `jpkcom-simple-lang.php` orchestrates loading via `jpkcom_simplelang_locate_file()` which searches for files in this priority:

1. Child theme: `/wp-content/themes/your-child-theme/jpkcom-simple-lang/`
2. Parent theme: `/wp-content/themes/your-theme/jpkcom-simple-lang/`
3. MU plugin overrides: `/wp-content/mu-plugins/jpkcom-simple-lang-overrides/`
4. Plugin itself: `/wp-content/plugins/jpkcom-simple-lang/includes/`

This override system allows developers to customize any functional file without modifying the plugin.

### Plugin Modules

Four core modules loaded in `jpkcom-simple-lang.php`:

1. **admin-settings.php** - Settings page for post type activation
2. **meta-box.php** - Language selection meta box in post editor
3. **frontend-language.php** - Locale switching and HTML attribute override
4. **oxygen-conditions.php** - Oxygen Builder conditional logic (optional)
5. **class-plugin-updater.php** - GitHub-based auto-updater with SHA256 verification

### Admin Settings (`includes/admin-settings.php`)

Provides a settings page under **Settings → Simple Lang** for controlling which post types have language selection enabled.

**Key Functions:**
- `jpkcom_simplelang_settings_page()` - Renders the settings page
- `jpkcom_simplelang_sanitize_post_types()` - Validates and sanitizes post type selections
- `jpkcom_simplelang_enabled_post_types_field()` - Renders checkbox list of all public post types

**Settings Storage:**
- Option key: `jpkcom_simplelang_enabled_post_types`
- Option type: Array of post type names
- Default value: `['post', 'page']`

**Behavior:**
- Lists all public post types with checkboxes
- Posts and Pages are enabled by default
- Unchecking a post type hides the meta box and disables frontend language override

### Meta Box (`includes/meta-box.php`)

Adds a "Frontend Language Select" dropdown to the post editor sidebar for enabled post types.

**Key Functions:**
- `jpkcom_simplelang_render_meta_box()` - Renders the language dropdown
- `jpkcom_simplelang_get_post_language()` - Retrieves the language set for a post

**Language Sources:**
- Uses `get_available_languages()` to fetch installed WordPress languages
- Uses `wp_get_available_translations()` for language names
- Always includes English (en_US) as an option

**Meta Storage:**
- Meta key: `_jpkcom_simplelang_language`
- Meta type: String (locale code like 'de_DE', 'fr_FR')
- Empty value = use site default language

**Security:**
- Nonce verification: `jpkcom_simplelang_nonce`
- Capability check: Uses post type's `edit_post` capability
- Locale validation: Regex pattern `/^[a-z]{2,3}_[A-Z]{2}$/`

### Frontend Language (`includes/frontend-language.php`)

Handles locale switching and HTML attribute modifications in the frontend.

**Key Functions:**
- `jpkcom_simplelang_get_current_language()` - Returns active frontend language
- `jpkcom_simplelang_get_language_code()` - Converts locale to language code (de_DE → de)

**Hooks Used:**

1. **`template_redirect` (priority 5)** - Switches locale before page rendering
   - Checks if current request is singular
   - Verifies post type is enabled
   - Retrieves post language meta
   - Calls `switch_to_locale()` to change WordPress locale
   - Stores language in global: `$GLOBALS['jpkcom_simplelang_current_language']`

2. **`language_attributes` filter** - Modifies HTML `<html lang="">` attribute
   - Uses regex to replace lang attribute value
   - Converts locale to language code (de_DE → de)

3. **`locale` filter** - Ensures locale consistency throughout rendering
   - Returns custom language if set, otherwise returns default
   - SEO plugins automatically detect this via `get_locale()` and output correct `og:locale` meta tags

4. **`wp_footer` (priority 999)** - Restores original locale after rendering
   - Calls `restore_previous_locale()`
   - Cleans up global variable

**Language Cascade:**
1. Check for custom language in post meta
2. If found, switch locale for entire request
3. All WordPress functions respect the new locale (date formats, translations, etc.)
4. Theme and plugin translations load in selected language
5. Locale restored after page rendering completes

### Oxygen Builder Integration (`includes/oxygen-conditions.php`)

Provides three custom conditions for Oxygen Builder if `oxygen_vsb_register_condition()` function exists.

**Conditions:**

1. **"Post Language Is"** - Check if post has specific language
   - Function: `jpkcom_simplelang_post_language_is($language_code)`
   - Values: `jpkcom_simplelang_post_language_is_values()` (all available languages)
   - Use case: Show German-specific content only on German pages

2. **"Post Has Custom Language"** - Check if post has any custom language set
   - Function: `jpkcom_simplelang_has_custom_language()`
   - Values: None (boolean condition)
   - Use case: Show language indicator badge

3. **"Post Uses Default Language"** - Check if post uses site default
   - Function: `jpkcom_simplelang_uses_default_language()`
   - Values: None (boolean condition)
   - Use case: Show default language navigation

**Registration:**
- Hook: `init` (priority 20)
- Checks: `if ( ! function_exists( 'oxygen_vsb_register_condition' ) )` before registering
- All conditions work only on singular pages (`is_singular()`)

## Development Workflow

### Adding New Features

1. Create new file in `includes/` directory
2. Add file to module loader in `jpkcom-simple-lang.php`
3. Use WordPress hooks (actions/filters) for integration
4. Follow existing code style (strict types, named parameters)
5. Add comprehensive PHPDoc comments

### Modifying Existing Features

1. Check if file exists in override locations first
2. Prefer editing via override system rather than modifying plugin files
3. Use filters to extend functionality when possible

### Testing Language Selection

1. Install additional languages: Settings → General → Site Language
2. Enable post type: Settings → Simple Lang
3. Edit a post and select language from dropdown
4. Save and view in frontend
5. Verify HTML lang attribute and translations

## Version Management

Version numbers appear in:
- `jpkcom-simple-lang.php` (plugin header line 6)
- `jpkcom-simple-lang.php` (JPKCOM_SIMPLELANG_VERSION constant)
- `README.md` (header metadata and changelog)
- `phpdoc.xml` (version number attribute)

When releasing a new version, update all four locations.

## Release Process

The plugin uses GitHub Actions for automated releases (`.github/workflows/release.yml`):

### Workflow Steps:

1. **Trigger:** Publishing a GitHub release
2. **Extract metadata** from README.md
3. **Package plugin** excluding development files
4. **Generate SHA256 checksum** for security
5. **Upload to release** as ZIP attachment
6. **Generate JSON manifest** for auto-updates
7. **Generate PHPDoc** API documentation
8. **Deploy to GitHub Pages** (docs and manifest)

### Files Excluded from ZIP:

- `.git`, `.github`, `.claude` directories
- `.gitignore`, `CLAUDE.md`
- `phpdoc.xml`, `phpDocumentor.phar`
- `.phpdoc/`, `docs/` directories
- Any `.tgz` or `.DS_Store` files

### Manual Release Steps:

1. Update version numbers in all locations
2. Update `README.md` changelog
3. Commit changes: `git commit -m "Release vX.Y.Z"`
4. Create tag: `git tag vX.Y.Z`
5. Push: `git push origin main --tags`
6. Create GitHub release from tag
7. GitHub Actions automatically builds and deploys

## Common Patterns

### Accessing Current Language

```php
// In frontend template
$language = jpkcom_simplelang_get_current_language();
if ( $language ) {
    echo 'Page is in: ' . $language;
}
```

### Checking Post Language in Template

```php
// Get specific post's language
$post_language = jpkcom_simplelang_get_post_language( $post_id );

if ( $post_language === 'de_DE' ) {
    // Show German-specific content
}
```

### Adding Custom Language Logic

```php
// Use the locale filter
add_filter( 'locale', function( $locale ) {
    // Custom logic here
    return $locale;
}, 100 ); // High priority to run after plugin
```

### Overriding Plugin Files

Create a directory in your theme:
```
wp-content/themes/your-theme/jpkcom-simple-lang/admin-settings.php
```

The plugin will load your file instead of its own.

## Code Style

### PHP Requirements

- **Version:** PHP 8.3+
- **Strict Types:** Every file starts with `declare(strict_types=1);`
- **Type Hints:** All parameters and return types explicitly declared
- **Named Parameters:** Used for WordPress functions where applicable

### Naming Conventions

- **Plugin constants:** `JPKCOM_SIMPLELANG_*` (all caps with underscores)
- **Functions:** `jpkcom_simplelang_*` (lowercase with underscores)
- **File names:** `kebab-case.php` (lowercase with hyphens)
- **Meta keys:** `_jpkcom_simplelang_*` (leading underscore for private meta)
- **Option keys:** `jpkcom_simplelang_*` (no leading underscore)
- **CSS classes:** `jpkcom-simplelang-*` (lowercase with hyphens)

### File Structure

```php
<?php
/**
 * File Description
 *
 * Longer description of what this file does.
 *
 * @package   JPKCom_Simple_Lang
 * @since     1.0.0
 */

declare(strict_types=1);

if ( ! defined( constant_name: 'ABSPATH' ) ) {
    exit;
}

// Code here
```

### Function Documentation

```php
/**
 * Brief one-line description
 *
 * Longer description explaining what the function does,
 * including implementation details and important notes.
 *
 * @since 1.0.0
 *
 * @param string $param_name Brief description. Defaults to ''.
 * @param int    $another    Brief description.
 * @return string|null Description of return value.
 *
 * @global WP_Post $post Current post object (if applicable).
 */
function jpkcom_simplelang_example( string $param_name = '', int $another = 0 ): ?string {
    // Function body
}
```

### Hook Usage

```php
// Action hooks
add_action( 'hook_name', function(): void {
    // Code here
}, priority );

// Filter hooks
add_filter( 'filter_name', function( string $value ): string {
    return $value;
}, priority );

// With callback function
add_action( 'hook_name', 'jpkcom_simplelang_callback_function', priority );
```

### Security Patterns

**1. Input Validation:**
```php
$value = sanitize_text_field( $_POST['field'] );
$email = sanitize_email( $_POST['email'] );
$url = esc_url_raw( $_POST['url'] );
$post_types = array_map( 'sanitize_key', $_POST['post_types'] );
```

**2. Output Escaping:**
```php
echo esc_html( $text );                  // Plain text
echo esc_attr( $attribute );             // HTML attributes
echo esc_url( $url );                    // URLs in HTML
echo esc_url_raw( $url );                // URLs in functions
echo wp_kses_post( $html );              // HTML content
```

**3. Nonce Verification:**
```php
wp_nonce_field( 'action_name', 'nonce_name' );

if ( ! wp_verify_nonce( $_POST['nonce_name'], 'action_name' ) ) {
    return;
}
```

**4. Capability Checks:**
```php
if ( ! current_user_can( 'manage_options' ) ) {
    return;
}

if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
    return;
}
```

## Plugin Constants

Defined in `jpkcom-simple-lang.php`:

- `JPKCOM_SIMPLELANG_VERSION` - Plugin version (e.g., '1.0.0')
- `JPKCOM_SIMPLELANG_BASENAME` - Plugin basename from `plugin_basename()`
- `JPKCOM_SIMPLELANG_PLUGIN_PATH` - Absolute path to plugin directory
- `JPKCOM_SIMPLELANG_PLUGIN_URL` - URL to plugin directory

Usage:
```php
$file = JPKCOM_SIMPLELANG_PLUGIN_PATH . 'includes/file.php';
$url = JPKCOM_SIMPLELANG_PLUGIN_URL . 'assets/css/styles.css';
```

## Key Functions Reference

### Public API

- `jpkcom_simplelang_get_post_language( ?int $post_id = null ): ?string`
  - Get the language set for a post
  - Returns locale code or null if using default

- `jpkcom_simplelang_get_current_language(): ?string`
  - Get the currently active frontend language
  - Only works in frontend after locale switch

- `jpkcom_simplelang_get_language_code( string $locale ): string`
  - Convert locale (de_DE) to language code (de)

### Internal Functions

- `jpkcom_simplelang_locate_file( string $filename ): ?string`
  - Locate a file with override support
  - Returns full path or null

- `jpkcom_simplelang_sanitize_post_types( ?array $value ): array`
  - Sanitize post type settings
  - Validates against available post types

## Settings and Options

### Plugin Options

**`jpkcom_simplelang_enabled_post_types`**
- Type: Array
- Default: `['post', 'page']`
- Description: Post types with language selection enabled

### Post Meta

**`_jpkcom_simplelang_language`**
- Type: String
- Format: Locale code (e.g., 'de_DE', 'fr_FR')
- Description: Selected language for the post
- Empty/null = use site default

## Filters

### Available Filters

**`jpkcom_simplelang_file_paths`**
- Modify file search paths for template overrides
- Parameters: `$paths` (array), `$filename` (string)
- Usage:
  ```php
  add_filter( 'jpkcom_simplelang_file_paths', function( $paths, $filename ) {
      $paths[] = '/custom/path/' . $filename;
      return $paths;
  }, 10, 2 );
  ```

**`locale`** (WordPress core filter)
- The plugin uses this filter to override the locale
- Your code can hook in at priority 100+ to run after the plugin

**`language_attributes`** (WordPress core filter)
- The plugin uses this to modify HTML lang attribute
- Hook at priority 20+ to modify after the plugin

## Translation & Localization

### Text Domain

- Text domain: `jpkcom-simple-lang`
- Domain path: `/languages`

### Translation Files

Located in `languages/` directory:
- `.pot` - Template file for translations
- `.po` - Editable translation files (e.g., `de_DE.po`)
- `.mo` - Compiled translation files (e.g., `de_DE.mo`)
- `.l10n.php` - PHP array format (WordPress 6.5+)

### String Translation

All user-facing strings use WordPress translation functions:

```php
__( 'Text', 'jpkcom-simple-lang' )               // Return translated
_e( 'Text', 'jpkcom-simple-lang' )               // Echo translated
esc_html__( 'Text', 'jpkcom-simple-lang' )       // Return escaped HTML
esc_html_e( 'Text', 'jpkcom-simple-lang' )       // Echo escaped HTML
esc_attr__( 'Text', 'jpkcom-simple-lang' )       // Return escaped attribute
```

### Generating Translation Files

Using WP-CLI:
```bash
wp i18n make-pot . languages/jpkcom-simple-lang.pot
```

## Debugging

### Debug Mode

The plugin respects WordPress debug constants:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Debug messages go to `/wp-content/debug.log` when enabled.

### Plugin Updater Logging

The auto-updater (`includes/class-plugin-updater.php`) logs errors when `WP_DEBUG` is enabled:

```php
error_log( 'JPKCom Plugin Updater: ...' );
```

### Testing Language Switching

Check if locale switching is working:

```php
add_action( 'template_redirect', function() {
    if ( is_singular() ) {
        error_log( 'Current locale: ' . get_locale() );
        error_log( 'Post language: ' . get_post_meta( get_the_ID(), '_jpkcom_simplelang_language', true ) );
    }
}, 999 );
```

## Security Best Practices

1. **Never trust user input** - Always sanitize and validate
2. **Always escape output** - Use appropriate `esc_*()` functions
3. **Check capabilities** - Verify user permissions before actions
4. **Use nonces** - Verify form submissions with `wp_verify_nonce()`
5. **Validate locale format** - Use regex to validate locale strings
6. **Checksum verification** - Auto-updater verifies SHA256 checksums
7. **Prevent direct access** - All files check for `ABSPATH` constant

## Performance Considerations

- **Minimal overhead:** One meta query per post to check language
- **Native functions:** Uses WordPress `switch_to_locale()` (highly optimized)
- **No frontend assets:** No JavaScript or CSS loaded in frontend
- **Query optimization:** Only runs on singular pages
- **Early exit:** Checks post type activation before doing any work

## Troubleshooting

### Language not changing in frontend

1. Check if post type is enabled in Settings → Simple Lang
2. Verify language is selected in post meta box
3. Ensure language pack is installed (Settings → General)
4. Check for theme/plugin conflicts overriding locale
5. Enable WP_DEBUG and check debug.log

### Meta box not appearing

1. Verify post type is checked in Settings → Simple Lang
2. Check screen options (top right) - meta box might be hidden
3. Verify user has `edit_post` capability

### Oxygen conditions not working

1. Verify Oxygen Builder is installed and active
2. Check if conditions appear in Oxygen's condition dropdown
3. Ensure testing on singular post/page (conditions don't work on archives)

### Updates not appearing

1. Verify internet connection
2. Check GitHub Pages deployment status
3. Manually delete transient: `delete_transient( 'jpk_git_update_' . md5( 'jpkcom-simple-lang' ) )`

## API Documentation

Complete PHPDoc-generated API documentation is available at:
[https://jpkcom.github.io/jpkcom-simple-lang/docs/](https://jpkcom.github.io/jpkcom-simple-lang/docs/)

The documentation includes:
- All functions with parameters and return types
- Code examples
- Cross-references between related functions
- Class documentation for the updater

## Support & Contributing

- **Bug reports:** [GitHub Issues](https://github.com/JPKCom/jpkcom-simple-lang/issues)
- **Feature requests:** [GitHub Issues](https://github.com/JPKCom/jpkcom-simple-lang/issues)
- **Source code:** [GitHub Repository](https://github.com/JPKCom/jpkcom-simple-lang)

When reporting issues, please include:
- WordPress version
- PHP version
- Active theme and plugins
- Steps to reproduce
- Expected vs actual behavior
- Debug log output (if available)
