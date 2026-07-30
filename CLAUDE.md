# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

This is a WordPress plugin called **JPKCom Simple Lang** - a lightweight solution for per-post language selection that overrides the site-wide language setting in the frontend. It allows content editors to display individual pages or posts in different languages without the complexity of full multilingual plugins.

**Requirements:**
- WordPress 6.9+
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

Five core modules loaded in `jpkcom-simple-lang.php`:

1. **admin-settings.php** - Settings page for post type activation
2. **meta-box.php** - Language selection meta box in post editor
3. **frontend-language.php** - Locale switching and HTML attribute override
4. **hreflang-translations.php** - Translation links meta box and hreflang tag generation
5. **oxygen-conditions.php** - Oxygen Builder conditional logic (optional)
6. **class-plugin-updater.php** - GitHub-based auto-updater with mandatory SHA256 verification (fail closed: a missing or unfetchable `checksum_sha256` aborts the update); the verified temp file is returned from `upgrader_pre_download`, so WordPress installs exactly the bytes that were hashed

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

### Hreflang Translations (`includes/hreflang-translations.php`)

Handles bidirectional translation linking between posts and automatic hreflang meta tag generation for SEO.

**Key Functions:**
- `jpkcom_simplelang_get_site_default_locale()` - Returns site default locale from WPLANG option
- `jpkcom_simplelang_get_language_name()` - Converts locale to native language name
- `jpkcom_simplelang_group_posts_by_language()` - Groups posts by their language for meta box display
- `jpkcom_simplelang_validate_translations()` - Prevents duplicate languages in translation sets
- `jpkcom_simplelang_sync_translations()` - Creates complete translation sets with bidirectional links
- `jpkcom_simplelang_output_hreflang_tags()` - Generates `<link rel="alternate" hreflang="">` tags

**Meta Storage:**
- Meta key: `_jpkcom_simplelang_translations`
- Meta type: Multiple integer values (post IDs)
- Storage method: WordPress native multiple meta entries with same key
- Example: Post 1 links to [2, 3] = two separate meta rows with values 2 and 3

**Security:**
- Nonce verification: `jpkcom_simplelang_translations_nonce`
- Capability check: Uses post type's `edit_post` capability
- Post ID sanitization: `absint()` on all post IDs
- Autosave check: Skips on `DOING_AUTOSAVE`

**Hooks Used:**

1. **`add_meta_boxes` (priority 10)** - Registers "Translation Links" meta box
   - Renders multi-select dropdown grouped by language using `<optgroup>`
   - Shows all posts of same post type (except current)
   - Displays post status (draft, pending, etc.) for unpublished posts
   - Post selection is grouped alphabetically by language

2. **`save_post` (priority 10, 2 params)** - Handles translation link saving
   - Validates selected translation IDs (no duplicates, no same-language posts)
   - Calls `jpkcom_simplelang_sync_translations()` to create complete translation set
   - Uses static flag to prevent infinite loops during bidirectional sync

3. **`wp_head` (priority 1)** - Outputs hreflang tags in HTML `<head>`
   - Only on singular pages with enabled post types
   - Requires at least one translation link to output tags
   - Calls `jpkcom_simplelang_output_hreflang_tags()`

**Translation Set Sync Logic:**

The sync function creates a **complete translation set** where every post links to all others:

```
Example: Post 1 (DE) linked to [2 (EN), 3 (FR)]

Result after sync:
- Post 1: [2, 3]
- Post 2: [1, 3]  (automatically updated)
- Post 3: [1, 2]  (automatically updated)
```

**Algorithm:**
1. Get current post's old translations
2. Calculate removed translations
3. Update current post with new translations
4. Build complete translation set (current + new translations)
5. Update ALL posts in set to link to ALL others (except themselves)
6. Remove links from posts that were removed from set
7. Static flag prevents infinite loops during updates

**Hreflang Output:**

Tags are generated for all posts in the translation set:

```html
<link rel="alternate" hreflang="de" href="https://example.com/german-page/" />
<link rel="alternate" hreflang="en" href="https://example.com/english-page/" />
<link rel="alternate" hreflang="fr" href="https://example.com/french-page/" />
```

**Hreflang Features:**
- Self-referencing tags included (SEO best practice)
- Only published posts appear in tags
- Deduplicated post IDs
- Sorted by language code for deterministic output
- Uses `jpkcom_simplelang_get_language_code()` from frontend-language.php
- Single query for all posts (prevents N+1 problem)
- HTML comments for debugging

**Edge Cases Handled:**

1. **Orphaned links:** WordPress auto-deletes meta on post deletion
2. **Draft posts:** Only published posts in hreflang output
3. **Duplicate languages:** Validation prevents multiple posts in same language
4. **Circular references:** Not a problem - all posts show all versions
5. **Default language detection:** Uses `jpkcom_simplelang_get_site_default_locale()` instead of `get_locale()` to avoid issues with `switch_to_locale()`
6. **Type casting:** All post IDs cast to integers for proper array comparisons

**Performance:**
- Meta box: Single WP_Query for all posts
- Save: 2-3 queries (delete old meta, add new meta, update related posts)
- Frontend: 2 queries (get meta, get posts in single query)
- WordPress object cache handles meta caching automatically

**UI/UX:**
- Multi-select dropdown with `<optgroup>` for language grouping
- Shows post title and status
- Help text explains Ctrl/Cmd multi-select
- Counter shows number of linked translations
- Empty state message when no posts available

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

Version numbers appear in five places and must be kept in sync:

- `jpkcom-simple-lang.php` — header `Version:`
- `jpkcom-simple-lang.php` — header `Stable tag:`
- `jpkcom-simple-lang.php` — `JPKCOM_SIMPLELANG_VERSION`
- `phpdoc.xml` — `<version number="…">`
- `README.md` — `**Version:**`, `**Stable tag:**`, plus a new `### x.y.z` changelog block

## Release Process

**Actions are pinned to commit SHAs.** Every `uses:` line in `.github/workflows/` references a 40-character commit SHA instead of a tag (`@v4`), with the version as a trailing comment. A tag is a movable pointer and can be repointed; a SHA cannot. Since the release workflow builds the plugin ZIP **and** the SHA256 checksum the auto-updater trusts, a compromised action would ship a tampered ZIP together with a matching checksum — the checksum secures the transport, the pinning secures the build. `.github/dependabot.yml` keeps the pins current weekly in one combined PR; when updating, always change the SHA *and* the version comment together.

**CI** (`.github/workflows/ci.yml`) runs on every pull request *and* on every push to `main` — a required status check only covers pull requests, so a direct push with bypass rights would otherwise skip the checks entirely. It runs `php -l` over all PHP files; flags invalid named arguments to internal PHP functions (catches `sprintf(format:, values:)` → `ArgumentCountError`, which `php -l` does not see); validates the YAML of every `.github` file; asserts every action is pinned to a 40-character commit SHA; and executes `tests/test-*.php` where present.

**Dependabot auto-merge** (`.github/workflows/dependabot-auto-merge.yml`) merges only `semver-patch` and `semver-minor`, and only PRs from `dependabot[bot]` in this repo — never from forks. Major updates get a comment and stay manual. Two repo settings are prerequisites, otherwise this is useless or outright dangerous: "Allow auto-merge" must be enabled, and branch protection must list `CI / Lint & Guards` as a **required status check** — without it `gh pr merge --auto` merges *immediately*, since there is nothing left to wait for. Together with `cooldown: default-days: 7` no action release is adopted during its first week.

**Releasing.** Bump the five version locations, add the changelog block, commit, then push a `v*` tag — that tag push is the only trigger. `.github/workflows/release.yml` creates the GitHub release itself; do **not** create it by hand first. Pipeline: README metadata via Pandoc → slug-named ZIP (excludes `.git`, `.github`, `.claude`, `CLAUDE.md`, `tests`, `tools`, `phpdoc.xml`, `docs`, build artefacts) → SHA256 → upload ZIP + `.sha256` → `plugin_jpkcom-simple-lang.json` manifest → PHPDoc → deploy to `gh-pages`.

The manifest's `checksum_sha256` is what the updater verifies on every update, so ZIP and manifest must come from the same run — which is why the manifest is only rebuilt on a tag push.

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

### Working with Translation Links (since 1.1.0)

```php
// Get all translation links for a post
$translation_ids = get_post_meta( $post_id, '_jpkcom_simplelang_translations', false );

// Check if post has any translations
if ( ! empty( $translation_ids ) ) {
    echo 'This post has ' . count( $translation_ids ) . ' translations';
}

// Get translation posts (only published)
$translations = jpkcom_simplelang_get_translation_posts( $translation_ids );

foreach ( $translations as $translation ) {
    $lang = jpkcom_simplelang_get_post_language( $translation->ID );
    $lang_code = jpkcom_simplelang_get_language_code( $lang );
    echo '<a href="' . get_permalink( $translation->ID ) . '" hreflang="' . $lang_code . '">';
    echo get_the_title( $translation->ID );
    echo '</a>';
}
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

### File and Function Conventions

Every file opens with a PHPDoc file block (`@package JPKCom_Simple_Lang`, `@since`), then `declare(strict_types=1);`, then an `ABSPATH` guard. Every function carries a docblock with `@since`, typed `@param`s and `@return`. Standard WordPress sanitising/escaping and nonce plus capability checks apply — see the existing files for the house style rather than a template here.

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

- `jpkcom_simplelang_get_site_default_locale(): string`
  - Get the site's default locale from WPLANG option
  - Ignores any temporary locale switches from `switch_to_locale()`
  - Returns 'en_US' if WPLANG is empty

### Translation Functions (since 1.1.0)

- `jpkcom_simplelang_get_language_name( string $locale ): string`
  - Convert locale code to native language name
  - Uses `wp_get_available_translations()` for translation data

- `jpkcom_simplelang_group_posts_by_language( array $query_args ): array`
  - Group posts by their language for meta box display
  - Returns associative array: `$locale => $posts[]`

- `jpkcom_simplelang_validate_translations( int $post_id, array $translation_ids ): array`
  - Validate translation links to prevent duplicate languages
  - Returns filtered array of valid translation post IDs

- `jpkcom_simplelang_sync_translations( int $post_id, array $new_translations ): void`
  - Sync translation links bidirectionally across all posts in set
  - Creates complete translation set where all posts link to all others
  - Uses static flag to prevent infinite loops

- `jpkcom_simplelang_get_translation_posts( array $post_ids ): array`
  - Fetch multiple posts in single query (prevents N+1)
  - Only returns published posts

- `jpkcom_simplelang_output_hreflang_tags( int $post_id, array $translation_ids ): void`
  - Generate and output hreflang link tags in HTML head
  - Includes self-referencing tags and all linked translations

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

**`_jpkcom_simplelang_translations`** (since 1.1.0)
- Type: Multiple integer values (post IDs)
- Storage: WordPress native multiple meta entries with same key
- Description: Post IDs of linked translation posts
- Example: Post 1 links to [2, 3] creates two meta rows with values 2 and 3
- Bidirectional: All posts in a translation set link to all others

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

## Security Notes

Beyond the usual sanitise/escape/nonce/capability discipline, two things are specific to this plugin: locale strings are validated against a regex before they reach `switch_to_locale()`, and the auto-updater verifies the SHA256 checksum from the manifest before installing.

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

PHPDoc output is regenerated on every release and published to [jpkcom.github.io/jpkcom-simple-lang/docs/](https://jpkcom.github.io/jpkcom-simple-lang/docs/).
