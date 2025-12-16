<?php
/**
 * Oxygen Builder Conditions
 *
 * Provides conditional logic for Oxygen Builder based on post language settings.
 *
 * @package   JPKCom_Simple_Lang
 * @since     1.0.0
 */

declare(strict_types=1);

if ( ! defined( constant_name: 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Oxygen Builder conditions
 *
 * Registers custom conditions for language selection if Oxygen Builder is active.
 *
 * @since 1.0.0
 * @return void
 */
add_action( 'init', function(): void {

	// Check if Oxygen Builder is installed
	if ( ! function_exists( 'oxygen_vsb_register_condition' ) ) {
		return;
	}

	// Register condition: Post has specific language
	oxygen_vsb_register_condition(
		__( 'Post Language Is', 'jpkcom-simple-lang' ),
		[ 'jpkcom_simplelang_post_language_is' ],
		[ 'jpkcom_simplelang_post_language_is_values' ],
		__( 'Simple Lang: Check if post has a specific language', 'jpkcom-simple-lang' ),
		'jpkcom_simplelang_post_language_is'
	);

	// Register condition: Post has any custom language set
	oxygen_vsb_register_condition(
		__( 'Post Has Custom Language', 'jpkcom-simple-lang' ),
		[ 'jpkcom_simplelang_has_custom_language' ],
		[],
		__( 'Simple Lang: Check if post has any custom language set', 'jpkcom-simple-lang' ),
		'jpkcom_simplelang_has_custom_language'
	);

	// Register condition: Post uses site default language
	oxygen_vsb_register_condition(
		__( 'Post Uses Default Language', 'jpkcom-simple-lang' ),
		[ 'jpkcom_simplelang_uses_default_language' ],
		[],
		__( 'Simple Lang: Check if post uses the site default language', 'jpkcom-simple-lang' ),
		'jpkcom_simplelang_uses_default_language'
	);

}, 20 );

/**
 * Oxygen condition: Post language is specific value
 *
 * Checks if the post has a specific language set.
 *
 * @since 1.0.0
 *
 * @param string $language_code The language code to check.
 * @return bool True if the post has the specified language.
 */
function jpkcom_simplelang_post_language_is( string $language_code ): bool {

	if ( ! is_singular() ) {
		return false;
	}

	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return false;
	}

	$post_language = get_post_meta( $post_id, '_jpkcom_simplelang_language', true );

	return $post_language === $language_code;
}

/**
 * Oxygen condition values: Available languages
 *
 * Returns all available languages for the condition dropdown.
 *
 * @since 1.0.0
 *
 * @return array<string, string> Array of language codes and names.
 */
function jpkcom_simplelang_post_language_is_values(): array {

	$languages = get_available_languages();
	$language_options = [];

	// Add English if not in available languages
	if ( ! in_array( 'en_US', $languages, true ) ) {
		$language_options['en_US'] = 'English (United States)';
	}

	// Get language names using WordPress translation API
	if ( ! function_exists( 'wp_get_available_translations' ) ) {
		require_once ABSPATH . 'wp-admin/includes/translation-install.php';
	}

	$translations = wp_get_available_translations();

	foreach ( $languages as $locale ) {
		if ( isset( $translations[ $locale ] ) ) {
			$language_options[ $locale ] = $translations[ $locale ]['native_name'];
		} else {
			$language_options[ $locale ] = $locale;
		}
	}

	return $language_options;
}

/**
 * Oxygen condition: Post has custom language
 *
 * Checks if the post has any custom language set (not using site default).
 *
 * @since 1.0.0
 *
 * @return bool True if the post has a custom language.
 */
function jpkcom_simplelang_has_custom_language(): bool {

	if ( ! is_singular() ) {
		return false;
	}

	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return false;
	}

	$post_language = get_post_meta( $post_id, '_jpkcom_simplelang_language', true );

	return ! empty( $post_language );
}

/**
 * Oxygen condition: Post uses default language
 *
 * Checks if the post is using the site default language (no custom language set).
 *
 * @since 1.0.0
 *
 * @return bool True if the post uses the default language.
 */
function jpkcom_simplelang_uses_default_language(): bool {

	if ( ! is_singular() ) {
		return false;
	}

	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return false;
	}

	$post_language = get_post_meta( $post_id, '_jpkcom_simplelang_language', true );

	return empty( $post_language );
}
