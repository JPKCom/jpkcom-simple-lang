# JPKCom Simple Lang

**Plugin Name:** JPKCom Simple Lang  
**Plugin URI:** https://github.com/JPKCom/jpkcom-simple-lang  
**Description:** Simple language selection for frontend pages.  
**Version:** 1.0.0  
**Author:** Jean Pierre Kolb <jpk@jpkc.com>  
**Author URI:** https://www.jpkc.com/  
**Contributors:** JPKCom  
**Tags:** Language, Lang, Locale, Multilingual, Translation, i18n, Oxygen Builder  
**Requires at least:** 6.8  
**Tested up to:** 6.9  
**Requires PHP:** 8.3  
**Network:** true  
**Stable tag:** 1.0.0  
**License:** GPL-2.0+  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.txt  
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
- **HTML Lang Attribute**: Updates the `<html lang="">` attribute to match the selected language
- **Open Graph Integration**: Sets the `og:locale` meta tag for proper social media sharing
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
3. **In the Frontend**: When visitors view that post, WordPress automatically switches to the selected language
4. **Locale Override**: The entire page renders with translations from the selected language, including:
   - WordPress core strings (dates, buttons, messages)
   - Theme translations
   - Plugin translations that respect the current locale
   - HTML `lang` attribute
   - Open Graph `og:locale` meta tag

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

#### Convert locale to language code:
```php
// Convert locale (de_DE) to language code (de) for HTML lang attribute
$lang_code = jpkcom_simplelang_get_language_code( 'de_DE' );
// Returns: 'de'
```

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

Simple Lang focuses on displaying content in different languages but doesn't generate hreflang tags or manage SEO relationships between language versions. For full SEO multilingual support, consider using dedicated SEO plugins like Yoast SEO or Rank Math in combination with your multilingual solution.

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

### How do I get support?

For bug reports and feature requests, please use the [GitHub issue tracker](https://github.com/JPKCom/jpkcom-simple-lang/issues).


## Installation

### Prerequisites

Before installing this plugin, ensure you have:

- **WordPress 6.8 or higher**
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

### 1.0.0 - 2025-12-16

**Initial Release**

- Language selection dropdown in post editor sidebar
- Support for all WordPress core languages
- Post type activation settings page
- Frontend locale override (including HTML lang and og:locale)
- Oxygen Builder conditional logic integration
- Helper functions for developers
- Template override system
- Automatic GitHub-based updates
- German translations included
- Full documentation and API docs
