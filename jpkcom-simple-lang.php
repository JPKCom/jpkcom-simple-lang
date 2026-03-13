<?php
/*
Plugin Name: JPKCom Simple Lang
Plugin URI: https://github.com/JPKCom/jpkcom-simple-lang
Description: Simple language selection for frontend pages.
Version: 1.2.0
Author: Jean Pierre Kolb <jpk@jpkc.com>
Author URI: https://www.jpkc.com/
Contributors: JPKCom
Tags: Language, Lang
Requires at least: 6.8
Tested up to: 6.9
Requires PHP: 8.3
Network: true
Stable tag: 1.2.0
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain: jpkcom-simple-lang
Domain Path: /languages
*/

declare(strict_types=1);

if ( ! defined( constant_name: 'WPINC' ) ) {
    die;
}

/**
 * Plugin Constants
 *
 * @since 1.0.0
 */
if ( ! defined( 'JPKCOM_SIMPLELANG_VERSION' ) ) {
	define( 'JPKCOM_SIMPLELANG_VERSION', '1.2.0' );
}

if ( ! defined( 'JPKCOM_SIMPLELANG_BASENAME' ) ) {
	define( 'JPKCOM_SIMPLELANG_BASENAME', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'JPKCOM_SIMPLELANG_PLUGIN_PATH' ) ) {
	define( 'JPKCOM_SIMPLELANG_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'JPKCOM_SIMPLELANG_PLUGIN_URL' ) ) {
	define( 'JPKCOM_SIMPLELANG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}


/**
 * Initialize Plugin Updater
 *
 * Loads and initializes the GitHub-based plugin updater with SHA256 checksum verification.
 *
 * @since 1.0.0
 * @return void
 */
add_action( 'init', static function (): void {
	$updater_file = JPKCOM_SIMPLELANG_PLUGIN_PATH . 'includes/class-plugin-updater.php';

	if ( file_exists( $updater_file ) ) {
		require_once $updater_file;

		if ( class_exists( 'JPKComSimpleLangGitUpdate\\JPKComGitPluginUpdater' ) ) {
			new \JPKComSimpleLangGitUpdate\JPKComGitPluginUpdater(
				plugin_file: __FILE__,
				current_version: JPKCOM_SIMPLELANG_VERSION,
				manifest_url: 'https://jpkcom.github.io/jpkcom-simple-lang/plugin_jpkcom-simple-lang.json'
			);
		}
	}
}, 5 );


/**
 * Load plugin text domain for translations
 *
 * Loads translation files from the /languages directory.
 *
 * @since 1.0.0
 * @return void
 */
function jpkcom_simplelang_textdomain(): void {
	load_plugin_textdomain(
		'jpkcom-simple-lang',
		false,
		dirname( path: JPKCOM_SIMPLELANG_BASENAME ) . '/languages'
	);
}

add_action( 'plugins_loaded', 'jpkcom_simplelang_textdomain' );


/**
 * Load plugin modules
 *
 * Loads all functional modules using the locate file function with override support.
 *
 * @since 1.0.0
 * @return void
 */
add_action( 'plugins_loaded', function(): void {

	// Core modules to load
	$modules = [
		'admin-settings.php',
		'meta-box.php',
		'frontend-language.php',
		'oxygen-conditions.php',
		'hreflang-translations.php',
	];

	foreach ( $modules as $module ) {
		$file = jpkcom_simplelang_locate_file( $module );

		if ( $file && file_exists( $file ) ) {
			require_once $file;
		}
	}

}, 5 );


/**
 * Locate file with override support
 *
 * Searches for a file in multiple locations with priority:
 * 1. Child theme
 * 2. Parent theme
 * 3. MU plugin overrides
 * 4. Plugin includes directory
 *
 * @since 1.0.0
 *
 * @param string $filename The filename to locate (without path).
 * @return string|null Full path to the file if found, null otherwise.
 */
function jpkcom_simplelang_locate_file( string $filename ): ?string {

    $paths = [
        get_stylesheet_directory() . '/jpkcom-simple-lang/' . $filename,
        get_template_directory() . '/jpkcom-simple-lang/' . $filename,
        WPMU_PLUGIN_DIR . '/jpkcom-simple-lang-overrides/' . $filename,
        JPKCOM_SIMPLELANG_PLUGIN_PATH . 'includes/' . $filename,
    ];

    /**
     * Filter the file search paths
     *
     * @since 1.0.0
     *
     * @param string[] $paths    Array of paths to search.
     * @param string   $filename The filename being located.
     */
    $paths = apply_filters( 'jpkcom_simplelang_file_paths', $paths, $filename );

    foreach ( $paths as $path ) {

        if ( file_exists( filename: $path ) ) {

            return $path;

        }

    }

    return null;

}
