<?php
/**
 * Frontend Language Override
 *
 * Handles switching the locale in the frontend based on post language settings.
 *
 * @package   JPKCom_Simple_Lang
 * @since     1.0.0
 */

declare(strict_types=1);

if ( ! defined( constant_name: 'ABSPATH' ) ) {
	exit;
}

/**
 * Switch locale on template redirect
 *
 * Changes the locale for the current request if a post has a specific language set.
 *
 * @since 1.0.0
 * @return void
 */
add_action( 'template_redirect', function(): void {

	// Only run on singular posts
	if ( ! is_singular() ) {
		return;
	}

	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return;
	}

	// Check if post type is enabled
	$enabled_post_types = get_option( 'jpkcom_simplelang_enabled_post_types', [ 'post', 'page' ] );
	$post_type = get_post_type( $post_id );

	if ( ! $post_type || ! in_array( $post_type, $enabled_post_types, true ) ) {
		return;
	}

	// Get the post language
	$language = get_post_meta( $post_id, '_jpkcom_simplelang_language', true );

	if ( empty( $language ) ) {
		return;
	}

	/*
	 * Only claim the language if WordPress could actually switch to it.
	 * switch_to_locale() returns false when the language pack is missing, and
	 * up to 1.2.8 that return value was ignored: the page then advertised
	 * lang="fr" and hreflang="fr" while serving the site's original
	 * translations, and wp_footer called restore_previous_locale() for a switch
	 * that never happened.
	 */
	if ( ! switch_to_locale( $language ) ) {
		return;
	}

	// Store the language for later use
	$GLOBALS['jpkcom_simplelang_current_language'] = $language;

}, 5 );

/**
 * Override language attributes in HTML tag
 *
 * Filters the language attributes output in the HTML tag.
 *
 * @since 1.0.0
 *
 * @param string $output Language attributes output.
 * @return string Modified language attributes.
 */
add_filter( 'language_attributes', function( string $output ): string {

	// Check if we have a custom language set
	if ( ! isset( $GLOBALS['jpkcom_simplelang_current_language'] ) ) {
		return $output;
	}

	$language = $GLOBALS['jpkcom_simplelang_current_language'];

	/*
	 * The full BCP 47 tag, not just the language subtag. Up to 1.2.8 this wrote
	 * lang="de" for de_DE, dropping the region that WordPress itself emits.
	 */
	$tag = jpkcom_simplelang_get_bcp47( $language );

	// Replace the lang attribute. A callback keeps the replacement literal.
	$output = (string) preg_replace_callback(
		'/lang="[^"]*"/',
		static function () use ( $tag ): string {
			return 'lang="' . esc_attr( $tag ) . '"';
		},
		$output,
		1
	);

	return $output;

}, 10 );

/*
 * There is deliberately no `locale` filter here any more.
 *
 * switch_to_locale() installs WP_Locale_Switcher::filter_locale() itself, so
 * get_locale() already reports the switched locale. The extra filter was
 * redundant when the switch succeeded, and when it failed it was the thing that
 * made the request claim a language WordPress had not loaded. It also ran ahead
 * of the core switcher (both at priority 10, registered earlier), which meant a
 * later switch_to_locale() by other code - sending a mail in the visitor's
 * language, for instance - could be overridden.
 *
 * @since 1.2.9 Removed.
 */

/**
 * Convert locale to language code
 *
 * Converts a locale string (e.g., de_DE) to a language code (e.g., de).
 *
 * Kept for backwards compatibility. For markup that needs a language tag use
 * {@see jpkcom_simplelang_get_bcp47()} instead — a bare subtag cannot express
 * de-DE versus de-AT, and hreflang needs that distinction.
 *
 * @since 1.0.0
 *
 * @param string $locale The locale string.
 * @return string The language code.
 */
function jpkcom_simplelang_get_language_code( string $locale ): string {
	// Extract the language part before the underscore
	$parts = explode( '_', $locale );
	return ! empty( $parts[0] ) ? $parts[0] : $locale;
}

/**
 * Convert a WordPress locale to a BCP 47 language tag
 *
 * `de_DE` becomes `de-DE`, `en_US` becomes `en-US`, `ca` stays `ca`. WordPress
 * variant suffixes carry no BCP 47 meaning and are dropped, so `de_DE_formal`
 * and `de_CH_informal` become `de-DE` and `de-CH`, and `pt_PT_ao90` becomes
 * `pt-PT` — a formal and an informal German page are the same language and
 * region as far as a search engine is concerned.
 *
 * @since 1.2.9
 *
 * @param string $locale The WordPress locale (e.g. 'de_DE', 'de_DE_formal').
 * @return string The BCP 47 tag (e.g. 'de-DE').
 */
function jpkcom_simplelang_get_bcp47( string $locale ): string {
	$locale = (string) preg_replace( '/_(formal|informal|ao90)$/', '', $locale );

	return str_replace( '_', '-', $locale );
}

/**
 * Get current frontend language
 *
 * Returns the currently active language for the frontend.
 *
 * @since 1.0.0
 *
 * @return string|null The current language locale or null if using site default.
 */
function jpkcom_simplelang_get_current_language(): ?string {
	if ( isset( $GLOBALS['jpkcom_simplelang_current_language'] ) ) {
		return $GLOBALS['jpkcom_simplelang_current_language'];
	}

	return null;
}

/**
 * Restore locale after frontend rendering
 *
 * Ensures the locale is restored to the site default after page rendering.
 *
 * @since 1.0.0
 * @return void
 */
add_action( 'wp_footer', function(): void {

	// Restore original locale if it was changed
	if ( isset( $GLOBALS['jpkcom_simplelang_current_language'] ) ) {
		restore_previous_locale();
		unset( $GLOBALS['jpkcom_simplelang_current_language'] );
	}

}, 999 ); // Late priority to ensure all content has been processed
