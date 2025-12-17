<?php
/**
 * Hreflang Translation Links
 *
 * Handles bidirectional translation linking between posts and automatic
 * hreflang meta tag output for SEO.
 *
 * @package   JPKCom_Simple_Lang
 * @since     1.1.0
 */

declare(strict_types=1);

if ( ! defined( constant_name: 'ABSPATH' ) ) {
	exit;
}

/**
 * Get site default locale
 *
 * Returns the site's default locale, ignoring any temporary locale switches.
 * This is the locale set in Settings > General.
 *
 * @since 1.1.0
 *
 * @return string The site default locale (e.g., 'de_DE', 'en_US').
 */
function jpkcom_simplelang_get_site_default_locale(): string {
	$locale = get_option( 'WPLANG' );

	// If WPLANG is empty, WordPress defaults to en_US
	if ( empty( $locale ) ) {
		$locale = 'en_US';
	}

	return $locale;
}

/**
 * Get language name from locale
 *
 * Converts a locale code to a human-readable language name.
 *
 * @since 1.1.0
 *
 * @param string $locale The locale code (e.g., 'de_DE', 'fr_FR').
 * @return string The language name in native language.
 */
function jpkcom_simplelang_get_language_name( string $locale ): string {
	// Get available translations
	require_once ABSPATH . 'wp-admin/includes/translation-install.php';
	$translations = wp_get_available_translations();

	// Check if we have translation data
	if ( isset( $translations[ $locale ] ) ) {
		return $translations[ $locale ]['native_name'];
	}

	// Handle site default language
	if ( $locale === get_locale() ) {
		return __( 'Default (Site Language)', 'jpkcom-simple-lang' );
	}

	// Handle English
	if ( $locale === 'en_US' ) {
		return 'English (United States)';
	}

	// Fallback to locale code
	return $locale;
}

/**
 * Group posts by language
 *
 * Queries posts and groups them by their assigned language for display
 * in the translation links meta box.
 *
 * @since 1.1.0
 *
 * @param array<string, mixed> $query_args WP_Query arguments.
 * @return array<string, array<int, WP_Post>> Posts grouped by locale.
 */
function jpkcom_simplelang_group_posts_by_language( array $query_args ): array {
	$query = new WP_Query( $query_args );
	$grouped = [];

	// Get site default language
	$default_locale = jpkcom_simplelang_get_site_default_locale();

	foreach ( $query->posts as $post ) {
		// Get post language (or default)
		$locale = get_post_meta( $post->ID, '_jpkcom_simplelang_language', true );
		if ( empty( $locale ) ) {
			$locale = $default_locale;
		}

		if ( ! isset( $grouped[ $locale ] ) ) {
			$grouped[ $locale ] = [];
		}

		$grouped[ $locale ][] = $post;
	}

	// Sort by locale code for consistent ordering
	ksort( $grouped );

	return $grouped;
}

/**
 * Validate translation links
 *
 * Ensures that translation links are valid and prevents duplicate languages.
 *
 * @since 1.1.0
 *
 * @param int   $post_id         The current post ID.
 * @param int[] $translation_ids Array of translation post IDs.
 * @return int[] Validated array of translation post IDs.
 */
function jpkcom_simplelang_validate_translations( int $post_id, array $translation_ids ): array {
	// Get current post's language
	$current_locale = get_post_meta( $post_id, '_jpkcom_simplelang_language', true );
	if ( empty( $current_locale ) ) {
		$current_locale = jpkcom_simplelang_get_site_default_locale();
	}

	// Track seen languages
	$seen_languages = [ $current_locale => true ];
	$valid_translations = [];

	foreach ( $translation_ids as $translation_id ) {
		// Get translation's language
		$locale = get_post_meta( $translation_id, '_jpkcom_simplelang_language', true );
		if ( empty( $locale ) ) {
			$locale = jpkcom_simplelang_get_site_default_locale();
		}

		// Skip if language already seen
		if ( isset( $seen_languages[ $locale ] ) ) {
			continue;
		}

		$seen_languages[ $locale ] = true;
		$valid_translations[] = $translation_id;
	}

	return $valid_translations;
}

/**
 * Get translation posts efficiently
 *
 * Fetches multiple posts in a single query to avoid N+1 query problems.
 *
 * @since 1.1.0
 *
 * @param int[] $post_ids Array of post IDs to fetch.
 * @return WP_Post[] Array of post objects.
 */
function jpkcom_simplelang_get_translation_posts( array $post_ids ): array {
	if ( empty( $post_ids ) ) {
		return [];
	}

	$query = new WP_Query(
		[
			'post__in'       => $post_ids,
			'post_type'      => 'any',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'post__in',
		]
	);

	return $query->posts;
}

/**
 * Sync translation links bidirectionally
 *
 * Updates translation links for the current post and ensures all posts in the
 * translation set are linked to each other. Creates a complete translation set
 * where every post links to all others in the set.
 *
 * @since 1.1.0
 *
 * @param int   $post_id         The post ID to sync.
 * @param int[] $new_translations Array of translation post IDs.
 * @return void
 */
function jpkcom_simplelang_sync_translations( int $post_id, array $new_translations ): void {
	// Prevent infinite loops
	static $syncing = [];

	if ( isset( $syncing[ $post_id ] ) ) {
		return; // Already syncing this post
	}

	$syncing[ $post_id ] = true;

	try {
		// Get current translations (before update)
		$old_translations = array_map( 'intval', get_post_meta( $post_id, '_jpkcom_simplelang_translations', false ) );

		// Calculate removed translations
		$removed = array_diff( $old_translations, $new_translations );

		// Update current post's translations
		delete_post_meta( $post_id, '_jpkcom_simplelang_translations' );
		foreach ( $new_translations as $translation_id ) {
			add_post_meta( $post_id, '_jpkcom_simplelang_translations', $translation_id );
		}

		// Build the complete translation set (current post + new translations)
		$translation_set = array_merge( [ $post_id ], $new_translations );

		// Update all posts in the set to link to all others in the set
		foreach ( $translation_set as $set_post_id ) {
			// Skip the current post (already updated above)
			if ( $set_post_id === $post_id ) {
				continue;
			}

			// This post should link to all others in the set (except itself)
			$should_link_to = array_diff( $translation_set, [ $set_post_id ] );

			// Overwrite this post's translations with the complete set
			delete_post_meta( $set_post_id, '_jpkcom_simplelang_translations' );
			foreach ( $should_link_to as $link_id ) {
				add_post_meta( $set_post_id, '_jpkcom_simplelang_translations', $link_id );
			}
		}

		// Remove links from posts that were removed from the set
		foreach ( $removed as $removed_id ) {
			// Remove the current post from the removed post
			delete_post_meta( $removed_id, '_jpkcom_simplelang_translations', $post_id );

			// Also remove all other posts in the current set from the removed post
			foreach ( $new_translations as $translation_id ) {
				delete_post_meta( $removed_id, '_jpkcom_simplelang_translations', $translation_id );
			}
		}
	} finally {
		unset( $syncing[ $post_id ] );
	}
}

/**
 * Register translation links meta box
 *
 * Adds the translation links meta box to enabled post types.
 *
 * @since 1.1.0
 * @return void
 */
add_action( 'add_meta_boxes', function(): void {

	$enabled_post_types = get_option( 'jpkcom_simplelang_enabled_post_types', [ 'post', 'page' ] );

	if ( empty( $enabled_post_types ) || ! is_array( $enabled_post_types ) ) {
		return;
	}

	foreach ( $enabled_post_types as $post_type ) {
		add_meta_box(
			'jpkcom_simplelang_translation_links',
			__( 'Translation Links', 'jpkcom-simple-lang' ),
			'jpkcom_simplelang_render_translations_meta_box',
			$post_type,
			'side',
			'default'
		);
	}

}, 10 );

/**
 * Render translation links meta box
 *
 * Displays a multi-select dropdown with posts grouped by language.
 *
 * @since 1.1.0
 *
 * @param WP_Post $post Current post object.
 * @return void
 */
function jpkcom_simplelang_render_translations_meta_box( WP_Post $post ): void {

	// Add nonce for security
	wp_nonce_field( 'jpkcom_simplelang_save_translations', 'jpkcom_simplelang_translations_nonce' );

	// Get current translations (cast to int for proper comparison)
	$current_translations = array_map( 'intval', get_post_meta( $post->ID, '_jpkcom_simplelang_translations', false ) );

	// Get all posts of same post type (exclude current)
	$query_args = [
		'post_type'      => $post->post_type,
		'post_status'    => [ 'publish', 'draft', 'pending', 'future' ],
		'posts_per_page' => -1,
		'post__not_in'   => [ $post->ID ],
		'orderby'        => 'title',
		'order'          => 'ASC',
	];

	// Get posts grouped by language
	$posts_by_language = jpkcom_simplelang_group_posts_by_language( $query_args );

	// Check if we have any posts
	if ( empty( $posts_by_language ) ) {
		echo '<p>' . esc_html__( 'No other posts available for translation linking.', 'jpkcom-simple-lang' ) . '</p>';
		return;
	}

	?>
	<p>
		<label for="jpkcom_simplelang_translations">
			<?php esc_html_e( 'Select translations:', 'jpkcom-simple-lang' ); ?>
		</label>
	</p>
	<select
		name="jpkcom_simplelang_translations[]"
		id="jpkcom_simplelang_translations"
		multiple
		size="10"
		style="width: 100%; height: auto;"
	>
		<?php foreach ( $posts_by_language as $locale => $posts ) : ?>
			<?php $language_name = jpkcom_simplelang_get_language_name( $locale ); ?>
			<optgroup label="<?php echo esc_attr( $language_name ); ?>">
				<?php foreach ( $posts as $translation_post ) : ?>
					<?php $selected = in_array( $translation_post->ID, $current_translations, true ); ?>
					<option
						value="<?php echo esc_attr( (string) $translation_post->ID ); ?>"
						<?php selected( $selected, true ); ?>
					>
						<?php echo esc_html( $translation_post->post_title ); ?>
						<?php if ( $translation_post->post_status !== 'publish' ) : ?>
							(<?php echo esc_html( $translation_post->post_status ); ?>)
						<?php endif; ?>
					</option>
				<?php endforeach; ?>
			</optgroup>
		<?php endforeach; ?>
	</select>
	<p class="description">
		<?php
		esc_html_e(
			'Hold Ctrl (Cmd on Mac) to select multiple translations. Posts are grouped by language. Only published translations will appear in hreflang tags.',
			'jpkcom-simple-lang'
		);
		?>
	</p>
	<?php
	if ( ! empty( $current_translations ) ) {
		?>
		<p>
			<?php
			/* translators: %d: number of translations */
			echo esc_html(
				sprintf(
					_n(
						'%d translation linked',
						'%d translations linked',
						count( $current_translations ),
						'jpkcom-simple-lang'
					),
					count( $current_translations )
				)
			);
			?>
		</p>
		<?php
	}
}

/**
 * Save translation links
 *
 * Handles saving translation links when a post is saved, including
 * bidirectional synchronization.
 *
 * @since 1.1.0
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
	if ( ! isset( $_POST['jpkcom_simplelang_translations_nonce'] ) ||
	     ! wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST['jpkcom_simplelang_translations_nonce'] ) ),
			'jpkcom_simplelang_save_translations'
		) ) {
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

	// Get selected translation IDs
	$new_translations = isset( $_POST['jpkcom_simplelang_translations'] )
		? array_map( 'absint', (array) $_POST['jpkcom_simplelang_translations'] )
		: [];

	// Remove zero values and deduplicate
	$new_translations = array_filter( $new_translations );
	$new_translations = array_unique( $new_translations );

	// Validate translations (prevent duplicate languages)
	$new_translations = jpkcom_simplelang_validate_translations( $post_id, $new_translations );

	// Sync bidirectionally
	jpkcom_simplelang_sync_translations( $post_id, $new_translations );

}, 10, 2 );

/**
 * Output hreflang meta tags
 *
 * Adds hreflang link tags to the HTML head for all translation links.
 *
 * @since 1.1.0
 * @return void
 */
add_action( 'wp_head', function(): void {

	// Only on singular pages
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

	// Get all translations
	$translation_ids = get_post_meta( $post_id, '_jpkcom_simplelang_translations', false );

	// Need at least one translation to output hreflang
	if ( empty( $translation_ids ) ) {
		return;
	}

	// Output hreflang tags
	jpkcom_simplelang_output_hreflang_tags( $post_id, $translation_ids );

}, 1 );

/**
 * Generate and output hreflang tags
 *
 * Creates hreflang link tags for all translations including the current post.
 *
 * @since 1.1.0
 *
 * @param int   $post_id         The current post ID.
 * @param int[] $translation_ids Array of translation post IDs.
 * @return void
 */
function jpkcom_simplelang_output_hreflang_tags( int $post_id, array $translation_ids ): void {
	// Prepare array of all versions (including current)
	$all_versions = [ $post_id ];
	foreach ( $translation_ids as $translation_id ) {
		$all_versions[] = (int) $translation_id;
	}

	// Deduplicate and validate
	$all_versions = array_unique( $all_versions );

	// Get all posts in a single query
	$version_posts = jpkcom_simplelang_get_translation_posts( $all_versions );

	// Build tag data
	$hreflang_data = [];

	foreach ( $version_posts as $version_post ) {
		// Get language
		$locale = get_post_meta( $version_post->ID, '_jpkcom_simplelang_language', true );
		if ( empty( $locale ) ) {
			$locale = jpkcom_simplelang_get_site_default_locale(); // Site default
		}

		// Convert locale to language code (de_DE -> de)
		$lang_code = jpkcom_simplelang_get_language_code( $locale );

		// Get URL
		$url = get_permalink( $version_post->ID );

		$hreflang_data[] = [
			'lang' => $lang_code,
			'url'  => $url,
		];
	}

	// Sort by language code for consistency
	usort( $hreflang_data, fn( $a, $b ) => strcmp( $a['lang'], $b['lang'] ) );

	// Output tags
	echo "\n<!-- Hreflang tags by JPKCom Simple Lang -->\n";
	foreach ( $hreflang_data as $data ) {
		printf(
			'<link rel="alternate" hreflang="%s" href="%s" />' . "\n",
			esc_attr( $data['lang'] ),
			esc_url( $data['url'] )
		);
	}
	echo "<!-- End hreflang tags -->\n\n";
}
