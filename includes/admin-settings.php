<?php
/**
 * Admin Settings Page
 *
 * Registers the settings page under Settings menu and handles post type activation.
 *
 * @package   JPKCom_Simple_Lang
 * @since     1.0.0
 */

declare(strict_types=1);

if ( ! defined( constant_name: 'ABSPATH' ) ) {
	exit;
}

/**
 * Register admin menu page
 *
 * Adds a submenu page under Settings for configuring post type language selection.
 *
 * @since 1.0.0
 * @return void
 */
add_action( 'admin_menu', function(): void {

	add_options_page(
		__( 'Simple Lang Settings', 'jpkcom-simple-lang' ),
		__( 'Simple Lang', 'jpkcom-simple-lang' ),
		'manage_options',
		'jpkcom-simple-lang-settings',
		'jpkcom_simplelang_settings_page'
	);

}, 20 );

/**
 * Register settings
 *
 * Registers plugin settings for post type activation.
 *
 * @since 1.0.0
 * @return void
 */
add_action( 'admin_init', function(): void {

	// Register enabled post types setting
	register_setting(
		'jpkcom_simplelang_options',
		'jpkcom_simplelang_enabled_post_types',
		[
			'type'              => 'array',
			'default'           => [ 'post', 'page' ],
			'sanitize_callback' => 'jpkcom_simplelang_sanitize_post_types',
		]
	);

	// Add settings section
	add_settings_section(
		'jpkcom_simplelang_post_types_section',
		__( 'Post Type Settings', 'jpkcom-simple-lang' ),
		function() {
			echo '<p>' . esc_html__( 'Select which post types should have the frontend language selection dropdown in their edit screens.', 'jpkcom-simple-lang' ) . '</p>';
		},
		'jpkcom-simple-lang-settings'
	);

	// Add post types field
	add_settings_field(
		'jpkcom_simplelang_enabled_post_types',
		__( 'Enable Language Selection', 'jpkcom-simple-lang' ),
		'jpkcom_simplelang_enabled_post_types_field',
		'jpkcom-simple-lang-settings',
		'jpkcom_simplelang_post_types_section'
	);

} );

/**
 * Sanitize post types array
 *
 * Ensures only valid post types are saved in the settings.
 *
 * @since 1.0.0
 *
 * @param array<int, string>|null $value The post types array to sanitize.
 * @return array<int, string> Sanitized array of valid post types.
 */
function jpkcom_simplelang_sanitize_post_types( ?array $value ): array {
	if ( ! is_array( $value ) ) {
		return [ 'post', 'page' ];
	}

	$sanitized = [];
	$all_post_types = get_post_types( [ 'public' => true ], 'names' );

	foreach ( $value as $post_type ) {
		$post_type = sanitize_key( $post_type );
		if ( isset( $all_post_types[ $post_type ] ) ) {
			$sanitized[] = $post_type;
		}
	}

	return $sanitized;
}

/**
 * Render enabled post types field
 *
 * Displays checkboxes for all public post types.
 *
 * @since 1.0.0
 * @return void
 */
function jpkcom_simplelang_enabled_post_types_field(): void {
	$enabled = get_option( 'jpkcom_simplelang_enabled_post_types', [ 'post', 'page' ] );
	$post_types = get_post_types( [ 'public' => true ], 'objects' );

	if ( empty( $post_types ) ) {
		echo '<p>' . esc_html__( 'No public post types found.', 'jpkcom-simple-lang' ) . '</p>';
		return;
	}

	echo '<fieldset>';
	echo '<legend class="screen-reader-text"><span>' . esc_html__( 'Enable Language Selection', 'jpkcom-simple-lang' ) . '</span></legend>';

	foreach ( $post_types as $post_type ) {
		$checked = in_array( $post_type->name, $enabled, true );
		$id = 'jpkcom_simplelang_pt_' . esc_attr( $post_type->name );
		?>
		<label for="<?php echo esc_attr( $id ); ?>">
			<input
				type="checkbox"
				id="<?php echo esc_attr( $id ); ?>"
				name="jpkcom_simplelang_enabled_post_types[]"
				value="<?php echo esc_attr( $post_type->name ); ?>"
				<?php checked( $checked ); ?>
			>
			<?php echo esc_html( $post_type->label ); ?>
			<span class="description">(<?php echo esc_html( $post_type->name ); ?>)</span>
		</label>
		<br>
		<?php
	}

	echo '</fieldset>';
	echo '<p class="description">' . esc_html__( 'Posts and Pages are enabled by default. Unchecking a post type will hide the language selector on its edit screen and disable language override in the frontend.', 'jpkcom-simple-lang' ) . '</p>';
}

/**
 * Render settings page
 *
 * Displays the admin settings page for Simple Lang plugin.
 *
 * @since 1.0.0
 * @return void
 */
function jpkcom_simplelang_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Check if settings were saved
	if ( isset( $_GET['settings-updated'] ) ) {
		add_settings_error(
			'jpkcom_simplelang_messages',
			'jpkcom_simplelang_message',
			__( 'Settings saved successfully.', 'jpkcom-simple-lang' ),
			'success'
		);
	}

	settings_errors( 'jpkcom_simplelang_messages' );
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'jpkcom_simplelang_options' );
			do_settings_sections( 'jpkcom-simple-lang-settings' );
			submit_button( __( 'Save Settings', 'jpkcom-simple-lang' ) );
			?>
		</form>
	</div>
	<?php
}
