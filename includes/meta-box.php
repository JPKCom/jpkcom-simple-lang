<?php
/**
 * Language Selection Meta Box
 *
 * Adds a meta box to post edit screens for selecting the frontend language.
 *
 * @package   JPKCom_Simple_Lang
 * @since     1.0.0
 */

declare(strict_types=1);

if ( ! defined( constant_name: 'ABSPATH' ) ) {
	exit;
}

/**
 * Add meta box to enabled post types
 *
 * Registers the language selection meta box for all enabled post types.
 *
 * @since 1.0.0
 * @return void
 */
add_action( 'add_meta_boxes', function(): void {

	$enabled_post_types = get_option( 'jpkcom_simplelang_enabled_post_types', [ 'post', 'page' ] );

	if ( empty( $enabled_post_types ) || ! is_array( $enabled_post_types ) ) {
		return;
	}

	foreach ( $enabled_post_types as $post_type ) {
		add_meta_box(
			'jpkcom_simplelang_language_select',
			__( 'Frontend Language Select', 'jpkcom-simple-lang' ),
			'jpkcom_simplelang_render_meta_box',
			$post_type,
			'side',
			'default'
		);
	}

}, 10 );

/**
 * Render language selection meta box
 *
 * Displays a dropdown with all available WordPress languages.
 *
 * @since 1.0.0
 *
 * @param WP_Post $post Current post object.
 * @return void
 */
function jpkcom_simplelang_render_meta_box( WP_Post $post ): void {

	// Add nonce for security
	wp_nonce_field( 'jpkcom_simplelang_save_language', 'jpkcom_simplelang_nonce' );

	// Get current language setting
	$current_language = get_post_meta( $post->ID, '_jpkcom_simplelang_language', true );

	// Get available languages
	$languages = get_available_languages();

	// Always include English as default
	$language_options = [ '' => __( 'Default (Site Language)', 'jpkcom-simple-lang' ) ];

	// Add English if not in available languages
	if ( ! in_array( 'en_US', $languages, true ) ) {
		$language_options['en_US'] = 'English (United States)';
	}

	// Get language names using WordPress translation API
	require_once ABSPATH . 'wp-admin/includes/translation-install.php';
	$translations = wp_get_available_translations();

	foreach ( $languages as $locale ) {
		if ( isset( $translations[ $locale ] ) ) {
			$language_options[ $locale ] = $translations[ $locale ]['native_name'];
		} else {
			// Fallback to locale code if translation data not available
			$language_options[ $locale ] = $locale;
		}
	}

	/*
	 * Keep a stored language in the list even when its pack is gone.
	 *
	 * Without this the select has no matching option, the browser falls back to
	 * the first one ("Default"), and simply pressing Update wipes the post's
	 * language without anyone touching that field. See the notice below, which
	 * explains the state to the editor.
	 */
	$missing_pack = '' !== $current_language && ! isset( $language_options[ $current_language ] );

	if ( $missing_pack ) {
		$language_options[ $current_language ] = sprintf(
			/* translators: %s: locale code, e.g. fr_FR. */
			__( '%s — language pack not installed', 'jpkcom-simple-lang' ),
			$current_language
		);
	}

	?>
	<p>
		<label for="jpkcom_simplelang_language">
			<?php esc_html_e( 'Select the language for this page:', 'jpkcom-simple-lang' ); ?>
		</label>
	</p>
	<select
		name="jpkcom_simplelang_language"
		id="jpkcom_simplelang_language"
		style="width: auto; max-width: 100%;"
	>
		<?php foreach ( $language_options as $locale => $name ) : ?>
			<option
				value="<?php echo esc_attr( $locale ); ?>"
				<?php selected( $current_language, $locale ); ?>
			>
				<?php echo esc_html( $name ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<p class="description">
		<?php esc_html_e( 'This will override the site language for this specific page in the frontend. Leave as "Default" to use the site-wide language setting.', 'jpkcom-simple-lang' ); ?>
	</p>
	<?php
}

/**
 * Save language meta box data
 *
 * Handles saving the selected language when a post is saved.
 *
 * @since 1.0.0
 *
 * @param int     $post_id The post ID.
 * @param WP_Post $post    The post object.
 * @return void
 */
add_action( 'save_post', function( int $post_id, WP_Post $post ): void {

	// Check if this is an autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Verify nonce
	if ( ! isset( $_POST['jpkcom_simplelang_nonce'] ) ||
	     ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['jpkcom_simplelang_nonce'] ) ), 'jpkcom_simplelang_save_language' ) ) {
		return;
	}

	// Check user capabilities
	$post_type = get_post_type_object( $post->post_type );
	if ( ! $post_type || ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
		return;
	}

	// Check if post type is enabled
	$enabled_post_types = get_option( 'jpkcom_simplelang_enabled_post_types', [ 'post', 'page' ] );
	if ( ! in_array( $post->post_type, $enabled_post_types, true ) ) {
		return;
	}

	// Save or delete the language meta
	if ( isset( $_POST['jpkcom_simplelang_language'] ) ) {
		$language = sanitize_text_field( wp_unslash( $_POST['jpkcom_simplelang_language'] ) );

		// Delete meta if empty (use default)
		if ( empty( $language ) ) {
			delete_post_meta( $post_id, '_jpkcom_simplelang_language' );
		} elseif ( jpkcom_simplelang_is_valid_locale( $language ) ) {
			update_post_meta( $post_id, '_jpkcom_simplelang_language', $language );
		}
	}

}, 10, 2 );

/**
 * Check whether a locale may be stored as a post language
 *
 * Validated against the locales WordPress actually has installed, plus `en_US`,
 * which needs no language pack. That is the same list the meta box builds its
 * options from, so anything offered can also be saved.
 *
 * Up to 1.2.8 this was a regex, `/^[a-z]{2,3}(_[A-Z]{2})?$/`, which silently
 * rejected every WordPress variant locale: `de_DE_formal`, `nl_NL_formal`,
 * `de_CH_informal`, `pt_PT_ao90` and `art_xemoji`. The meta box offered them,
 * `save_post` dropped them, and nothing said so — this plugin even ships
 * `de_DE_formal` translations of its own. A whitelist is both stricter (no
 * invented locales) and complete (every real one).
 *
 * @since 1.2.9
 *
 * @param string $locale The locale to check.
 * @return bool True when the locale may be stored.
 */
function jpkcom_simplelang_is_valid_locale( string $locale ): bool {
	if ( 'en_US' === $locale ) {
		return true;
	}

	return in_array( $locale, get_available_languages(), true );
}

/**
 * Warn on the edit screen when a post's language pack is missing
 *
 * A language selected while its pack was installed keeps sitting in the post
 * meta after the pack is removed — and from 1.2.9 the front end no longer
 * pretends to switch to it, so the page is simply delivered in the site
 * language. Nothing in the editor would otherwise say why.
 *
 * @since 1.2.9
 *
 * @return void
 */
add_action( 'admin_notices', function(): void {

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( ! $screen || 'post' !== $screen->base ) {
		return;
	}

	$post = get_post();

	if ( ! $post instanceof WP_Post ) {
		return;
	}

	$enabled_post_types = get_option( 'jpkcom_simplelang_enabled_post_types', [ 'post', 'page' ] );

	if ( ! is_array( $enabled_post_types ) || ! in_array( $post->post_type, $enabled_post_types, true ) ) {
		return;
	}

	$language = get_post_meta( $post->ID, '_jpkcom_simplelang_language', true );

	if ( ! is_string( $language ) || '' === $language || jpkcom_simplelang_is_valid_locale( $language ) ) {
		return;
	}

	?>
	<div class="notice notice-warning">
		<p>
			<strong>JPKCom Simple Lang:</strong>
			<?php
			printf(
				/* translators: %s: locale code wrapped in a <code> tag, e.g. fr_FR. */
				esc_html__( 'This content is set to the language %s, but that language pack is not installed. WordPress cannot switch to it, so the page is delivered in the site language and no hreflang is claimed for it.', 'jpkcom-simple-lang' ),
				'<code>' . esc_html( $language ) . '</code>'
			);

			if ( current_user_can( 'install_languages' ) ) {
				printf(
					' <a href="%1$s">%2$s</a>',
					esc_url( admin_url( 'options-general.php' ) ),
					esc_html__( 'Install the language under Settings → General.', 'jpkcom-simple-lang' )
				);
			}
			?>
		</p>
	</div>
	<?php

} );

/**
 * Get post language
 *
 * Retrieves the language set for a specific post.
 *
 * @since 1.0.0
 *
 * @param int|null $post_id Optional. Post ID. Defaults to current post.
 * @return string|null The language locale or null if not set.
 */
function jpkcom_simplelang_get_post_language( ?int $post_id = null ): ?string {
	if ( null === $post_id ) {
		$post_id = get_the_ID();
	}

	if ( ! $post_id ) {
		return null;
	}

	$language = get_post_meta( $post_id, '_jpkcom_simplelang_language', true );

	return ! empty( $language ) ? $language : null;
}
