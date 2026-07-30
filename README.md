# JPKCom Simple Lang

**Plugin Name:** JPKCom Simple Lang  
**Plugin URI:** https://github.com/JPKCom/jpkcom-simple-lang  
**Description:** Simple language selection for frontend pages.  
**Version:** 1.2.9  
**Author:** Jean Pierre Kolb <jpk@jpkc.com>  
**Author URI:** https://www.jpkc.com/  
**Contributors:** JPKCom  
**Tags:** Language, Lang, Locale, Multilingual, Translation, i18n, Hreflang, SEO, Oxygen Builder  
**Requires at least:** 6.9  
**Tested up to:** 7.1  
**Requires PHP:** 8.3  
**Network:** true  
**Stable tag:** 1.2.9  
**License:** GPL-2.0-or-later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html  
**Text Domain:** jpkcom-simple-lang  
**Domain Path:** /languages

A plugin to provide per-post language selection that overrides the site-wide language setting in the frontend.


## Description

**JPKCom Simple Lang** is a lightweight solution for displaying individual pages or posts in different languages than your site's default language. Unlike full-fledged multilingual plugins, Simple Lang focuses on a single task: allowing you to override the WordPress locale on a per-post basis for frontend display.

This is particularly useful for sites that are primarily in one language but occasionally need to display specific pages in another language, without the complexity and overhead of managing multiple translations for every piece of content.

### Key Features

- **Per-Post Language Selection**: Choose a different language for individual posts, pages, or custom post types
- **WordPress Core Languages**: Uses WordPress's built-in language system - no additional translation files needed
- **Post Type Control**: Enable or disable language selection per post type via settings page
- **Frontend Locale Override**: Automatically switches locale in frontend for proper translation support
- **HTML Lang Attribute**: Updates the `<html lang="">` attribute to match the selected language, region included (`lang="de-DE"`)
- **Translation Links & Hreflang**: Link related posts in different languages and automatically generate SEO-friendly hreflang meta tags as proper BCP 47, including an `x-default`
- **SEO Plugin Compatible**: SEO plugins (Yoast, Rank Math, etc.) automatically detect the locale change and output correct `og:locale` meta tags
- **Plugin Compatibility**: Other plugins respect the locale change for their frontend output
- **Oxygen Builder Support**: Provides conditional logic for Oxygen Builder based on selected language
- **Clean Admin UI**: Simple dropdown in the post editor sidebar
- **Default Fallback**: Posts without a custom language use the site's default language
- **Multisite Compatible**: Works seamlessly with WordPress multisite installations
- **Developer-Friendly**: Template override system and helper functions for custom development
- **Automatic Updates**: Secure GitHub-based plugin updates with SHA256 checksum verification
- **Modern PHP**: Built with PHP 8.3+ strict typing for performance and reliability

### Use Cases

- **Bilingual Content**: Display specific pages in a secondary language while keeping your site primarily in one language
- **International Landing Pages**: Create marketing pages in different languages without managing full site translations
- **Documentation**: Provide documentation pages in multiple languages on demand
- **Legal Pages**: Display terms of service or privacy policies in required languages
- **Client Presentations**: Create project presentations in your client's language
- **Oxygen Builder**: Show/hide specific Oxygen elements based on page language

### What's Included

- **Admin Settings** (`includes/admin-settings.php`) - Settings page under Settings → Simple Lang for post type activation
- **Meta Box** (`includes/meta-box.php`) - Language selection dropdown in post editor sidebar
- **Frontend Language** (`includes/frontend-language.php`) - Locale switching and HTML attribute override logic
- **Hreflang Translations** (`includes/hreflang-translations.php`) - Translation links meta box and automatic hreflang tag generation
- **Oxygen Conditions** (`includes/oxygen-conditions.php`) - Conditional logic integration for Oxygen Builder
- **Translation Files** (`languages/`) - Plugin interface translations (German included)
- **Automatic Updates** (`includes/class-plugin-updater.php`) - GitHub-based update system with SHA256 checksum verification

### Documentation

**API Documentation:** Complete PHPDoc-generated API documentation is available at:
[https://jpkcom.github.io/jpkcom-simple-lang/docs/](https://jpkcom.github.io/jpkcom-simple-lang/docs/)

The documentation includes detailed information about all functions, classes, hooks, and filters available in the plugin.

### How It Works

1. **In the Admin**: When editing a post, you'll see a "Frontend Language Select" dropdown in the sidebar
2. **Select a Language**: Choose from any WordPress language installed on your system, or use the default
3. **Link Translations (Optional)**: Use the "Translation Links" meta box to link related posts in different languages
4. **In the Frontend**: When visitors view that post, WordPress automatically switches to the selected language
5. **Locale Override**: The entire page renders with translations from the selected language, including:
   - WordPress core strings (dates, buttons, messages)
   - Theme translations
   - Plugin translations that respect the current locale
   - HTML `lang` attribute
   - SEO plugin meta tags (og:locale via `get_locale()`)
   - Hreflang link tags (if translation links are configured)

### Translation Links & Hreflang Tags

Simple Lang includes a powerful translation linking system that helps search engines understand the relationship between your content in different languages.

#### How Translation Links Work

1. **Link Related Content**: In the post editor sidebar, you'll find a "Translation Links" meta box
2. **Multi-Select Interface**: Posts are grouped by language for easy selection
3. **Bidirectional Linking**: When you link Post A to Post B, both posts automatically link to each other
4. **Complete Translation Sets**: If you link Post 1 (DE) to Post 2 (EN) and Post 3 (FR), all three posts are automatically linked together
5. **Automatic Hreflang Generation**: Linked posts automatically get `<link rel="alternate" hreflang="XX">` tags in the HTML `<head>`

#### Example Scenario

You have three versions of the same page:
- **Post 1**: German version (de_DE)
- **Post 2**: English version (en_US)
- **Post 3**: French version (fr_FR)

**Steps:**
1. Edit Post 1 (German)
2. In "Translation Links" select Post 2 and Post 3
3. Save Post 1

**Result:**
- Post 1 now links to Posts 2 & 3
- Post 2 automatically links to Posts 1 & 3
- Post 3 automatically links to Posts 1 & 2
- All three posts display proper hreflang tags

**Generated HTML on each page** (site default language `de_DE`):
```html
<!-- Hreflang tags by JPKCom Simple Lang -->
<link rel="alternate" hreflang="de-DE" href="https://example.com/german-page/" />
<link rel="alternate" hreflang="en-US" href="https://example.com/english-page/" />
<link rel="alternate" hreflang="fr-FR" href="https://example.com/french-page/" />
<link rel="alternate" hreflang="x-default" href="https://example.com/german-page/" />
<!-- End hreflang tags -->
```

#### Tag format

Tags are proper BCP 47 and carry the region: `de-DE`, `de-AT`, `pt-BR`. WordPress
variant suffixes have no BCP 47 meaning and are dropped, so `de_DE_formal` and
`de_CH_informal` become `de-DE` and `de-CH`, and `pt_PT_ao90` becomes `pt-PT` — a
formal and an informal German page are the same language and region as far as a
search engine is concerned.

Because the set is keyed by tag, two versions that resolve to the same tag —
two posts both set to `de_DE`, or `de_DE` plus `de_DE_formal` — contribute one
entry rather than a contradictory pair. The first one wins. Tags are sorted
alphabetically for a stable output.

#### x-default

`x-default` points at the version in the site's default language: the page to
serve when none of the declared languages matches the visitor. It is emitted in
addition to that version's own tag, which is how the annotation is meant to be
used, and matched on the BCP 47 tag — so on a `de_DE` site a page set to
`de_DE_formal` still counts as the German version. When none of the linked
versions carries the default language, no `x-default` is emitted.

#### SEO Benefits

- **Language Targeting**: Search engines understand which language each page targets
- **Regional Targeting**: `de_DE` and `de_AT` are told apart instead of both collapsing to `de`
- **Duplicate Content Prevention**: Signals that pages are translations, not duplicates
- **Regional Search Results**: Users see the correct language version in their regional search results
- **Self-Referencing Tags**: Each page includes its own language in hreflang tags (SEO best practice)
- **Automatic Updates**: Adding or removing translation links automatically updates all related pages

### Helper Functions

#### Get the language set for a post:
```php
// Get language for a specific post
$language = jpkcom_simplelang_get_post_language( $post_id );
// Returns: 'de_DE', 'fr_FR', etc. or null if using default

// Get language for current post
$language = jpkcom_simplelang_get_post_language();
```

#### Get the currently active frontend language:
```php
// Get the active language in frontend (after locale switch)
$current_lang = jpkcom_simplelang_get_current_language();
// Returns: 'de_DE', 'fr_FR', etc. or null if using site default
```

#### Convert a locale to a BCP 47 tag:
```php
// Convert locale (de_DE) to a BCP 47 tag (de-DE) for lang / hreflang
$tag = jpkcom_simplelang_get_bcp47( 'de_DE' );
// Returns: 'de-DE'

// WordPress variant suffixes are dropped
$tag = jpkcom_simplelang_get_bcp47( 'de_DE_formal' );
// Returns: 'de-DE'
```

This is what the plugin itself uses for the `lang` attribute and the hreflang
tags since 1.2.9. Prefer it for any language markup.

#### Convert locale to a bare language code:
```php
// Convert locale (de_DE) to the language subtag (de)
$lang_code = jpkcom_simplelang_get_language_code( 'de_DE' );
// Returns: 'de'
```

Still available and unchanged, but the poorer choice for markup: a bare subtag
cannot express the region. Use `jpkcom_simplelang_get_bcp47()` instead.

### Oxygen Builder Conditions

If [Oxygen Builder](https://oxygenbuilder.com/) is installed, Simple Lang provides three custom conditions:

#### 1. Post Language Is
Check if the current post is set to a specific language.

**Usage:** Show German-specific content only when post language is German.

**Parameters:** Select from dropdown of all available languages.

**Example:** Display a German contact form only on German pages.

#### 2. Post Has Custom Language
Check if the current post has any custom language set (not using site default).

**Usage:** Show a language indicator badge on posts with custom languages.

**Example:** Display "This page is available in [Language]" notification.

#### 3. Post Uses Default Language
Check if the current post uses the site default language (no custom language set).

**Usage:** Show default language content only when no custom language is selected.

**Example:** Display site-wide navigation only on default language pages.

### Filters

#### Modify file search paths
```php
/**
 * Filter the file search paths for template overrides
 *
 * @param array  $paths    Default search paths
 * @param string $filename The filename being located
 */
add_filter( 'jpkcom_simplelang_file_paths', function( $paths, $filename ) {
    // Add custom search path
    $paths[] = '/custom/path/to/overrides/' . $filename;
    return $paths;
}, 10, 2 );
```


## FAQ

### What's the difference between this and a full multilingual plugin like WPML or Polylang?

Simple Lang is intentionally simple. It doesn't create separate translations of your content, manage language switchers, or handle complex translation workflows. It simply allows you to tell WordPress "display this specific post in German" without creating a duplicate post or managing translation relationships.

**Use Simple Lang when:**
- You need occasional pages in different languages
- Your site is primarily one language
- You want minimal overhead and complexity

**Use WPML/Polylang when:**
- You need full site translations
- You want translation management workflows
- You need language switchers and translation relationships

### Which languages are available?

Simple Lang uses WordPress's built-in language system. Any language pack installed on your WordPress site is automatically available in the dropdown. You can install language packs via **Settings → General → Site Language**.

A locale is accepted when `get_available_languages()` reports it, plus `en_US`. That includes WordPress variant locales such as `de_DE_formal`, `nl_NL_formal`, `de_CH_informal` and `pt_PT_ao90`. Up to 1.2.8 a regex rejected exactly those: the meta box offered them, `save_post` dropped them, and nothing said so.

If a post's language pack is missing — uninstalled after the fact, for instance — the editor says so and the stored language stays selectable, so pressing Update does not silently clear it. In the frontend such a post falls back to the site language rather than advertising a language WordPress has not loaded.

WordPress supports 200+ languages. See the [full list of available languages](https://translate.wordpress.org/).

### Does this translate my content?

No. Simple Lang only changes the **interface language** (WordPress core strings, theme strings, plugin strings). Your post content remains exactly as you write it. You're responsible for writing the content in the target language.

**What gets translated:**
- WordPress admin bar links (if visible)
- Post date formats
- Comment form labels
- Theme navigation elements
- Plugin interface elements

**What doesn't get translated:**
- Your post title
- Your post content
- Your custom fields
- Media captions

### Can I use this with page builders like Elementor or Oxygen?

Yes! Simple Lang works with any page builder. The locale change happens at the WordPress level, so builder elements that use WordPress translation functions will automatically display in the selected language.

For **Oxygen Builder**, there's built-in support with custom conditions that let you show/hide elements based on the page language.

### Does this work with Gutenberg blocks?

Yes. Block editor blocks that use WordPress's translation system will display in the selected language. Core blocks and properly internationalized third-party blocks work seamlessly.

### What about SEO and hreflang tags?

Simple Lang automatically generates hreflang tags when you link posts together using the "Translation Links" meta box. The plugin outputs proper `<link rel="alternate" hreflang="XX">` tags in the HTML `<head>` section for all linked translations.

**What's included:**
- Automatic hreflang tag generation
- Proper BCP 47 tags with the region (`de-DE`, `de-AT`, `pt-BR`), since 1.2.9
- An `x-default` pointing at the version in the site's default language, since 1.2.9
- Self-referencing hreflang tags (SEO best practice)
- Bidirectional translation linking
- Only published posts appear in hreflang tags
- Tags sorted for consistency

**SEO Plugin Compatibility:**
- Works alongside Yoast SEO, Rank Math, and other SEO plugins
- SEO plugins automatically output correct `og:locale` meta tags
- Hreflang tags appear early in `<head>` (before most SEO plugins)

**What's NOT included:**
- Automatic translation suggestions
- Language switcher widgets

### Can I restrict which post types have language selection?

Yes! Go to **Settings → Simple Lang** and check/uncheck the post types you want. By default, Posts and Pages have language selection enabled. Custom post types can be enabled individually.

### How do I install additional WordPress languages?

**Important:** The language dropdown only shows installed languages. Here's how to add more:

**Quick Method:**
1. Go to **Settings → General** in WordPress admin
2. Find the **Site Language** dropdown
3. Select the language you want to install (e.g., "Deutsch", "Français")
4. Click **Save Changes**
5. WordPress automatically downloads the language pack (5-10 seconds)
6. Optional: Change back to your default language if needed
7. The new language now appears in Simple Lang's dropdown!

**Why only installed languages?**
- Language packs contain WordPress translations (buttons, menus, date formats, etc.)
- Without the pack, switching language would only change the HTML `lang` attribute
- Content would remain untranslated, creating a poor user experience
- This approach ensures every selectable language actually works

**Need help?**
- Go to **Settings → Simple Lang** for a detailed step-by-step guide
- Click the "Go to General Settings" button for quick access
- View all 200+ available languages at [translate.wordpress.org](https://translate.wordpress.org/)

**Pro Tip:** You can install multiple languages without changing your site's default language. Just install each language and switch back to your preferred default afterward. All installed languages remain available!

### Does this work on WordPress Multisite?

Yes! Simple Lang is fully compatible with WordPress Multisite installations. Each site in the network can have language selection enabled independently.

### Will this slow down my site?

No. Simple Lang adds minimal overhead:
- One meta query per post to check for custom language
- Locale switching happens once per request
- No additional database tables
- No frontend JavaScript or CSS

The plugin uses WordPress's native locale switching functions which are highly optimized.

### Can I customize the plugin files?

Yes! Simple Lang uses a template override system. You can override any plugin file by placing it in:

1. **Child theme**: `{child-theme}/jpkcom-simple-lang/{filename}`
2. **Parent theme**: `{parent-theme}/jpkcom-simple-lang/{filename}`
3. **MU plugins**: `{WPMU_PLUGIN_DIR}/jpkcom-simple-lang-overrides/{filename}`

This lets you customize functionality without modifying plugin files directly.

### Does the language selection appear in the REST API?

Yes. The language meta is stored as post meta with the key `_jpkcom_simplelang_language` and is accessible via the REST API if your setup includes post meta in responses.

### What happens if I deactivate the plugin?

Nothing breaks. Posts will simply display in your site's default language. The language selection meta data remains in the database, so if you reactivate the plugin later, your language selections are restored.

### Can I bulk-assign languages to multiple posts?

Not currently. Language selection is done individually per post in the editor. For bulk operations, you'd need to write custom code using `update_post_meta()` with the meta key `_jpkcom_simplelang_language`.

### Does this work with WooCommerce?

Yes, if you enable language selection for the `product` post type in settings. However, Simple Lang only changes the interface language, not product data. You'll need to manually enter product titles and descriptions in the target language.


## Installation

### Prerequisites

Before installing this plugin, ensure you have:

- **WordPress 6.9 or higher**
- **PHP 8.3 or higher**
- At least one additional language pack installed (optional but recommended)

### Method 1: WordPress Admin Upload

1. Download the latest release ZIP file from [GitHub Releases](https://github.com/JPKCom/jpkcom-simple-lang/releases)
2. Log in to your WordPress admin panel
3. Navigate to **Plugins → Add New**
4. Click **Upload Plugin** at the top
5. Choose the downloaded ZIP file
6. Click **Install Now**
7. Click **Activate Plugin**

### Method 2: FTP Installation

1. Download and extract the plugin ZIP file
2. Upload the `jpkcom-simple-lang` folder to `/wp-content/plugins/`
3. Log in to your WordPress admin panel
4. Navigate to **Plugins**
5. Find "JPKCom Simple Lang" and click **Activate**

### Method 3: GitHub Installation (Developers)

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/JPKCom/jpkcom-simple-lang.git
```

Then activate via WordPress admin.

### Post-Installation Steps

1. **Configure Post Types** (Optional):
   - Go to **Settings → Simple Lang**
   - Check/uncheck which post types should have language selection
   - Click **Save Settings**

2. **Install Additional Languages** (Recommended):

   **Why is this needed?** The language dropdown in the post editor only shows languages that are already installed on your WordPress site. By default, WordPress only includes English. To display pages in other languages, you need to install the corresponding language packs first.

   **Step-by-Step Guide:**

   a. **Navigate to Language Settings:**
      - In WordPress admin, go to **Settings → General**
      - Scroll down to find the **Site Language** dropdown

   b. **Install a Language Pack:**
      - Click the **Site Language** dropdown
      - Select the language you want to add (e.g., "Deutsch", "Français", "Español")
      - Click **Save Changes** at the bottom of the page
      - WordPress will automatically download and install the language pack (takes 5-10 seconds)

   c. **Restore Your Default Language (Optional):**
      - If you don't want to change your site's default language, immediately go back to **Settings → General**
      - Change the **Site Language** back to your preferred default (e.g., "English (United States)" or "Deutsch")
      - Click **Save Changes** again

   d. **Verify Installation:**
      - Go to **Settings → Simple Lang**
      - You'll see a helpful guide with a **"Go to General Settings"** button for quick access
      - The newly installed language is now available in the dropdown!

   e. **Add More Languages:**
      - Repeat steps b-c for each additional language you need
      - You can install as many languages as you want (WordPress supports 200+ languages)
      - Each language pack is typically 1-2 MB in size

   **Quick Tips:**
   - You don't need to keep your site in a different language to use it for individual pages
   - Language packs remain installed even after you switch back to your default language
   - Already installed languages will appear immediately in the post editor dropdown
   - View all available languages at [translate.wordpress.org](https://translate.wordpress.org/)

3. **Test Language Selection**:
   - Edit any post or page
   - Look for "Frontend Language Select" in the sidebar
   - Select a language from the dropdown
   - Save/update the post
   - View the post in frontend and verify the language changed

### Updating

The plugin includes automatic update support via GitHub. When a new version is released:

1. You'll see an update notification in **Plugins**
2. Click **Update Now**
3. WordPress automatically downloads and installs the update

Updates include SHA256 checksum verification for security.

### Uninstallation

To remove the plugin:

1. Deactivate the plugin via **Plugins**
2. Click **Delete**
3. Confirm deletion

**Note:** Language selection meta data will remain in the database. If you want to remove this data, run:

```sql
DELETE FROM wp_postmeta WHERE meta_key = '_jpkcom_simplelang_language';
```


## Changelog

### 1.2.9
* Fixed: WordPress variant locales could not be saved. The check was a regex, `/^[a-z]{2,3}(_[A-Z]{2})?$/`, which silently rejected `de_DE_formal`, `nl_NL_formal`, `de_CH_informal`, `pt_PT_ao90` and `art_xemoji` — the meta box offered them, `save_post` dropped them, and nothing said so. This plugin even ships its own `de_DE_formal` translation. Validation now runs against `get_available_languages()` plus `en_US`, which is both stricter (no invented locales) and complete
* Fixed: hreflang lost the region. `de_DE` and `de_AT` both came out as `hreflang="de"`, so a two-language set advertised the same value for two different URLs and the annotation became ambiguous — regional variants are exactly what hreflang is for. Tags are now proper BCP 47 (`de-DE`, `de-AT`, `pt-BR`), WordPress variant suffixes are dropped (`de_DE_formal` → `de-DE`), and two versions resolving to the same tag contribute one entry instead of a contradictory pair
* Added: an `x-default` link pointing at the version in the site's default language, matched on the BCP 47 tag so a `de_DE_formal` page still counts as the German version on a `de_DE` site. Omitted when no version carries the default language
* Fixed: the `lang` attribute on `<html>` now carries the region too (`lang="de-DE"` instead of `lang="de"`)
* Fixed: a failed locale switch is no longer treated as a success. `switch_to_locale()` returns false when the language pack is missing; that return value was ignored, so a page set to an uninstalled language advertised `lang="fr"` and `hreflang="fr"` while serving the site language, and `wp_footer` restored a switch that had never happened
* Added: the editor now warns when a post's language pack is not installed, and the stored language stays selectable in the meta box. Previously it vanished from the dropdown, so simply pressing Update silently cleared the post's language without anyone touching the field
* Removed: the plugin's own `locale` filter. `switch_to_locale()` installs WordPress' own filter, so it was redundant when the switch succeeded — and when it failed it was the thing that made the request claim a language WordPress had not loaded. It also ran ahead of the core locale switcher, so a later `switch_to_locale()` by other code could be overridden
* Fixed: the settings sanitiser raised a `TypeError` on a non-array value. It was typed `?array`, which rejected the value before the `is_array()` guard inside could run, so a hand-edited options form produced a fatal instead of the intended fallback
* Added: `tests/test-language.php` — 36 cases covering locale validation, BCP 47 conversion, the hreflang and x-default output and the settings sanitiser; 13 of them fail against 1.2.8. CI runs it on every pull request and push to `main`
* i18n: new strings translated for `de_DE` and `de_DE_formal`, `.pot`, `.mo` and `.l10n.php` regenerated. A `translators:` comment that sat too far from its `_n()` call to reach the POT was moved next to it
* i18n: the shipped translation files were carrying about 110 foreign strings — entries extracted from WordPress core's `theme.json`, WooCommerce's email editor and the Twenty Twenty-Five theme, from a POT that had once been generated in the wrong working directory. Regenerating from the plugin directory alone brings the catalogue down to the 47 strings this plugin actually has. The `.po` headers, including `Plural-Forms`, are unchanged

### 1.2.8
* Changed: the update manifest generator now defaults a missing `Network:` header to false instead of true, matching WordPress' own default. No change for this plugin, which declares `Network: true` explicitly
* CI: the lint and guard workflow now also runs on pushes to `main`. It only covered pull requests, so a direct push with bypass rights skipped every check
* Changed: comments, workflow step names and CI output across the repository are now English throughout, and the developer notes in `CLAUDE.md` were translated and trimmed. No effect on the shipped plugin

### 1.2.7
* Changed: `Tested up to` raised to WordPress 7.1
* Changed: the bundled updater's runtime floor now matches the plugin's own minimum. It bailed out below WordPress 6.8 while the plugin header has required 6.9 for several releases, so the check could never fire on a supported installation
* Docs: the remaining "WordPress 6.8" requirement statements now say 6.9, matching the plugin header
* CI: the release manifest's fallback values for `requires` and `tested` now say 6.9 and 7.1. They only apply when the README metadata cannot be read, but a stale fallback would have published a minimum the plugin no longer supports

### 1.2.6
* Changed: the plugin banners (`assets/banner-1544x500.avif`, `assets/banner-772x250.avif`) are now a plain `#3c4955` surface with no lettering

### 1.2.5
* CI: the release step no longer copies the staging directory into itself, so the ZIP has no empty `jpkcom-simple-lang/jpkcom-simple-lang/` folder
* CI: bumped the pinned GitHub Actions (checkout v7.0.1, setup-python v7.0.0, action-gh-release v3.0.2, fetch-metadata v3.1.0), still pinned to full commit SHAs
* CI: the release ZIP now excludes the development-only `tests/` and `tools/` directories
* CI: security and regression tests now run on every pull request, where a plugin has them

### 1.2.4
* Security: update packages are now verified *before* installation — the verified file is handed to WordPress instead of being downloaded a second time, so the bytes that were checked are the bytes that get installed
* Security: a missing or unfetchable SHA-256 checksum now aborts the update instead of installing unverified code (previously it silently skipped verification)
* Security: pinned every GitHub Action to a full commit SHA and added Dependabot with a 7-day cooldown, so a moved tag can no longer change the release build
* Security: tightened which download the updater claims, so sibling plugins cannot match each other's package
* Fixed: `sprintf()` calls in the updater bound named arguments to a variadic parameter, which raises `ArgumentCountError` on PHP 8.3
* Fixed: the "View Details" modal could fail with a `TypeError` when the manifest omitted `requires_plugins`
* Performance: a failed manifest fetch is now cached for an hour instead of being retried on every admin request
* Added: CI workflow on every pull request (PHP lint, named-argument check, YAML validation, action-pinning guard)
* Housekeeping: removed stray editor backups from the release package

### 1.2.3 - 2026-06-16
* Raised the minimum WordPress version to 6.9 and "Tested up to" to WordPress 7.0
* Switched license metadata to the SPDX identifier `GPL-2.0-or-later` with the HTTPS license URI

### 1.2.2 - 2026-06-16
* Security: updater prefers an exact match against the manifest `download_url` over the slug heuristic, so a tampered manifest can no longer bypass the checksum gate
* Security: timing-safe checksum comparison (`hash_equals()`) with an `is_string()` guard against `hash_file()` failures
* Security: manifest fetch via `wp_safe_remote_get()` (SSRF defense-in-depth)
* Fixed PHP warning and missing contributor names in the plugin detail popup (`display_name` now provided)
* Fixed PHP warning/deprecation on `wp plugin list` by completing the `no_update` transient entry (`new_version`, `package`, `tested`, `requires_php`)

### 1.2.1 - 2026-03-13

**Bug Fixes**

- Fixed manual ZIP upload failing with "No valid URL" error caused by checksum verification running on local file paths instead of remote URLs
- Fixed release ZIP missing top-level plugin directory, preventing WordPress from recognizing it as an update to the existing installation

### 1.2.0 - 2026-03-13

**Bug Fixes**

- Fixed language selection not saving for locales without country code (e.g. `ar` for Arabic)
- Updated locale validation regex to accept both short (`xx`) and full (`xx_XX`) locale formats

### 1.1.1 - 2025-12-17

**Bug Fixes**

- Fixed duplicate "Settings saved" message on admin settings page
- Fixed Oxygen Builder conditions not displaying dropdown options
- Fixed Oxygen Builder condition callback signatures to match API requirements

**Improvements**

- Updated Oxygen Builder conditions to use correct API structure with 'options' array
- Added proper operator support (==, !=) for "Post Language Is" condition
- Added Yes/No dropdown options for boolean Oxygen conditions
- Added German translations for Oxygen condition options (Ja/Nein)
- All Oxygen conditions now appear under "Simple Lang" category

**Technical Changes**

- Renamed callback functions to match Oxygen API conventions:
  - `jpkcom_simplelang_oxygen_post_language_is()` with ($value, $operator) parameters
  - `jpkcom_simplelang_oxygen_has_custom_language()` with ($value, $operator) parameters
  - `jpkcom_simplelang_oxygen_uses_default_language()` with ($value, $operator) parameters
- Removed redundant `settings_errors()` call in admin settings page

### 1.1.0 - 2025-12-17

**New Features**

- **Translation Links**: New meta box for linking posts in different languages
- **Hreflang Tags**: Automatic generation of SEO-friendly `<link rel="alternate" hreflang="">` tags
- **Bidirectional Linking**: Posts automatically link to each other when translation links are created
- **Complete Translation Sets**: All posts in a translation group are automatically linked together
- **Smart Validation**: Prevents duplicate languages in translation sets

**Bug Fixes**

- Fixed locale detection for default site language in translation grouping
- Fixed type casting issue in translation link display
- Improved meta data consistency across all translation sync operations

**Improvements**

- New helper function: `jpkcom_simplelang_get_site_default_locale()`
- Enhanced hreflang output with deterministic sorting
- Performance optimization: Single query for all translation posts (prevents N+1 queries)
- Updated German translations (de_DE and de_DE_formal)

**Developer Notes**

- New module: `includes/hreflang-translations.php`
- New meta key: `_jpkcom_simplelang_translations` (multiple entries per post)
- All translation sync operations use loop prevention for data integrity

### 1.0.0 - 2025-12-16

**Initial Release**

- Language selection dropdown in post editor sidebar
- Support for all WordPress core languages
- Post type activation settings page
- Frontend locale override (HTML lang attribute and SEO plugin compatibility)
- Oxygen Builder conditional logic integration
- Helper functions for developers
- Template override system
- Automatic GitHub-based updates
- German translations included
- Full documentation and API docs
