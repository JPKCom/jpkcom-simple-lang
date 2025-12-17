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

	// Get available language options
	$language_options = jpkcom_simplelang_get_oxygen_language_options();

	// Register condition: Post has specific language
	oxygen_vsb_register_condition(
		__( 'Post Language Is', 'jpkcom-simple-lang' ),
		[ 'options' => $language_options, 'custom' => false ],
		[ '==', '!=' ],
		'jpkcom_simplelang_oxygen_post_language_is',
		'Simple Lang'
	);

	// Register condition: Post has any custom language set
	oxygen_vsb_register_condition(
		__( 'Post Has Custom Language', 'jpkcom-simple-lang' ),
		[ 'options' => [ 'true' => __( 'Yes', 'jpkcom-simple-lang' ), 'false' => __( 'No', 'jpkcom-simple-lang' ) ], 'custom' => false ],
		[ '==' ],
		'jpkcom_simplelang_oxygen_has_custom_language',
		'Simple Lang'
	);

	// Register condition: Post uses site default language
	oxygen_vsb_register_condition(
		__( 'Post Uses Default Language', 'jpkcom-simple-lang' ),
		[ 'options' => [ 'true' => __( 'Yes', 'jpkcom-simple-lang' ), 'false' => __( 'No', 'jpkcom-simple-lang' ) ], 'custom' => false ],
		[ '==' ],
		'jpkcom_simplelang_oxygen_uses_default_language',
		'Simple Lang'
	);

}, 20 );

/**
 * Get language options for Oxygen Builder
 *
 * Returns an array of available languages formatted for Oxygen Builder conditions.
 *
 * @since 1.0.0
 *
 * @return array<string, string> Array of language codes and names.
 */
function jpkcom_simplelang_get_oxygen_language_options(): array {

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
 * Oxygen condition callback: Post language is specific value
 *
 * Checks if the post has a specific language set.
 *
 * @since 1.0.0
 *
 * @param string $value    The language code to check.
 * @param string $operator The comparison operator (== or !=).
 * @return bool True if the condition matches.
 */
function jpkcom_simplelang_oxygen_post_language_is( string $value, string $operator ): bool {

	if ( ! is_singular() ) {
		return false;
	}

	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return false;
	}

	$post_language = get_post_meta( $post_id, '_jpkcom_simplelang_language', true );

	// If no language is set, treat as empty string
	if ( empty( $post_language ) ) {
		$post_language = '';
	}

	// Evaluate based on operator
	global $OxygenConditions;
	if ( isset( $OxygenConditions ) && method_exists( $OxygenConditions, 'eval_string' ) ) {
		return $OxygenConditions->eval_string( $post_language, $value, $operator );
	}

	// Fallback to manual comparison
	if ( $operator === '==' ) {
		return $post_language === $value;
	} elseif ( $operator === '!=' ) {
		return $post_language !== $value;
	}

	return false;
}

/**
 * Oxygen condition callback: Post has custom language
 *
 * Checks if the post has any custom language set (not using site default).
 *
 * @since 1.0.0
 *
 * @param string $value    The value to check ('true' or 'false').
 * @param string $operator The comparison operator (always ==).
 * @return bool True if the condition matches.
 */
function jpkcom_simplelang_oxygen_has_custom_language( string $value, string $operator ): bool {

	if ( ! is_singular() ) {
		return false;
	}

	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return false;
	}

	$post_language = get_post_meta( $post_id, '_jpkcom_simplelang_language', true );
	$has_custom = ! empty( $post_language );

	// Convert boolean to string for comparison
	$result = $has_custom ? 'true' : 'false';

	return $result === $value;
}

/**
 * Oxygen condition callback: Post uses default language
 *
 * Checks if the post is using the site default language (no custom language set).
 *
 * @since 1.0.0
 *
 * @param string $value    The value to check ('true' or 'false').
 * @param string $operator The comparison operator (always ==).
 * @return bool True if the condition matches.
 */
function jpkcom_simplelang_oxygen_uses_default_language( string $value, string $operator ): bool {

	if ( ! is_singular() ) {
		return false;
	}

	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return false;
	}

	$post_language = get_post_meta( $post_id, '_jpkcom_simplelang_language', true );
	$uses_default = empty( $post_language );

	// Convert boolean to string for comparison
	$result = $uses_default ? 'true' : 'false';

	return $result === $value;
}
