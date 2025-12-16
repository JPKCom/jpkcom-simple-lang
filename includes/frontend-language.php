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

	// Switch to the specified locale
	switch_to_locale( $language );

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

	// Convert locale to language code (e.g., de_DE to de)
	$lang_code = jpkcom_simplelang_get_language_code( $language );

	// Replace the lang attribute
	$output = preg_replace(
		'/lang="[^"]*"/',
		'lang="' . esc_attr( $lang_code ) . '"',
		$output
	);

	return $output;

}, 10 );

/**
 * Filter locale for frontend
 *
 * Ensures the locale is properly set throughout the frontend rendering.
 *
 * @since 1.0.0
 *
 * @param string $locale The current locale.
 * @return string The filtered locale.
 */
add_filter( 'locale', function( string $locale ): string {

	// Check if we have a custom language set
	if ( isset( $GLOBALS['jpkcom_simplelang_current_language'] ) ) {
		return $GLOBALS['jpkcom_simplelang_current_language'];
	}

	return $locale;

}, 10 );

/**
 * Convert locale to language code
 *
 * Converts a locale string (e.g., de_DE) to a language code (e.g., de).
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
