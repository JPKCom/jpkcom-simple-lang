<?php
/**
 * Regression tests for locale handling in jpkcom-simple-lang.
 *
 * Runs standalone (no WordPress): the WordPress functions the modules touch at
 * load time are stubbed, the three relevant modules are required, and their
 * functions are then called directly. The hreflang output is captured from the
 * real emitter, so the assertions read the markup a visitor would get.
 *
 * Every case in the "locale validation", "BCP 47", "hreflang" and "settings
 * sanitiser" groups is red against 1.2.8.
 *
 * @package JPKCom_Simple_Lang
 * @since 1.2.9
 */

declare(strict_types=1);

if ( ! defined( constant_name: 'ABSPATH' ) ) {
    define( constant_name: 'ABSPATH', value: __DIR__ . '/' );
}

/** Installed language packs the stubs report. */
$GLOBALS['jpkcom_available'] = array( 'de_DE', 'de_DE_formal', 'de_AT' );

/** Post meta store: [ post_id ][ key ] = value. */
$GLOBALS['jpkcom_meta'] = array();

/** Registered hooks, so the suite can assert what is *not* registered any more. */
$GLOBALS['jpkcom_hooks'] = array();

/** Option store. */
$GLOBALS['jpkcom_options'] = array( 'WPLANG' => 'de_DE' );

if ( ! function_exists( function: 'add_action' ) ) {
    function add_action( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
        $GLOBALS['jpkcom_hooks']['action'][ $hook ][] = array( $callback, $priority );
    }
}

if ( ! function_exists( function: 'add_filter' ) ) {
    function add_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
        $GLOBALS['jpkcom_hooks']['filter'][ $hook ][] = array( $callback, $priority );
    }
}

if ( ! function_exists( function: 'get_available_languages' ) ) {
    function get_available_languages( string $dir = '' ): array {
        return $GLOBALS['jpkcom_available'];
    }
}

if ( ! function_exists( function: 'get_option' ) ) {
    function get_option( string $option, mixed $default_value = false ): mixed {
        return $GLOBALS['jpkcom_options'][ $option ] ?? $default_value;
    }
}

if ( ! function_exists( function: 'get_post_meta' ) ) {
    function get_post_meta( int $post_id, string $key = '', bool $single = false ): mixed {
        $value = $GLOBALS['jpkcom_meta'][ $post_id ][ $key ] ?? ( $single ? '' : array() );

        if ( $single && is_array( $value ) ) {
            return $value[0] ?? '';
        }

        return $value;
    }
}

if ( ! function_exists( function: 'get_permalink' ) ) {
    function get_permalink( int $post_id = 0 ): string {
        return 'https://example.test/post-' . $post_id . '/';
    }
}

if ( ! function_exists( function: 'get_post_types' ) ) {
    function get_post_types( array $args = array(), string $output = 'names' ): array {
        return array( 'post' => 'post', 'page' => 'page' );
    }
}

if ( ! function_exists( function: 'sanitize_key' ) ) {
    function sanitize_key( string $key ): string {
        return strtolower( (string) preg_replace( '/[^a-z0-9_\-]/i', '', $key ) );
    }
}

if ( ! function_exists( function: 'esc_attr' ) ) {
    function esc_attr( string $text ): string {
        return htmlspecialchars( string: $text, flags: ENT_QUOTES | ENT_SUBSTITUTE, encoding: 'UTF-8' );
    }
}

if ( ! function_exists( function: 'esc_url' ) ) {
    function esc_url( string $url ): string {
        return htmlspecialchars( string: $url, flags: ENT_QUOTES | ENT_SUBSTITUTE, encoding: 'UTF-8' );
    }
}

if ( ! function_exists( function: 'esc_html' ) ) {
    function esc_html( string $text ): string {
        return htmlspecialchars( string: $text, flags: ENT_QUOTES | ENT_SUBSTITUTE, encoding: 'UTF-8' );
    }
}

if ( ! function_exists( function: '__' ) ) {
    function __( string $text, string $domain = 'default' ): string {
        return $text;
    }
}

if ( ! function_exists( function: 'esc_html__' ) ) {
    function esc_html__( string $text, string $domain = 'default' ): string {
        return esc_html( $text );
    }
}

if ( ! function_exists( function: 'esc_html_e' ) ) {
    function esc_html_e( string $text, string $domain = 'default' ): void {
        echo esc_html( $text );
    }
}

if ( ! function_exists( function: 'wp_nonce_field' ) ) {
    function wp_nonce_field( string $action = '-1', string $name = '_wpnonce' ): void {}
}

if ( ! function_exists( function: 'selected' ) ) {
    function selected( mixed $selected, mixed $current = true, bool $display = true ): string {
        return (string) $selected === (string) $current ? ' selected' : '';
    }
}

if ( ! function_exists( function: 'current_user_can' ) ) {
    function current_user_can( string $capability, mixed ...$args ): bool {
        return true;
    }
}

if ( ! function_exists( function: 'admin_url' ) ) {
    function admin_url( string $path = '' ): string {
        return 'https://example.test/wp-admin/' . $path;
    }
}

if ( ! function_exists( function: 'get_post' ) ) {
    function get_post( mixed $post = null ): mixed {
        return null;
    }
}

if ( ! function_exists( function: 'get_current_screen' ) ) {
    function get_current_screen(): mixed {
        return null;
    }
}

if ( ! function_exists( function: 'wp_get_available_translations' ) ) {
    function wp_get_available_translations(): array {
        return array(
            'de_DE'        => array( 'native_name' => 'Deutsch' ),
            'de_DE_formal' => array( 'native_name' => 'Deutsch (Sie)' ),
            'de_AT'        => array( 'native_name' => 'Deutsch (Österreich)' ),
        );
    }
}

if ( ! function_exists( function: 'get_locale' ) ) {
    function get_locale(): string {
        return 'de_DE';
    }
}

if ( ! class_exists( class: 'WP_Post' ) ) {
    class WP_Post {
        public int $ID = 0;
        public string $post_type = 'post';
    }
}

if ( ! class_exists( class: 'WP_Query' ) ) {
    /** Returns the posts the test registered, in the requested order. */
    class WP_Query {
        /** @var array<int,WP_Post> */
        public array $posts = array();

        public function __construct( array $args = array() ) {
            foreach ( $args['post__in'] ?? array() as $id ) {
                $post            = new WP_Post();
                $post->ID        = (int) $id;
                $this->posts[]   = $post;
            }
        }
    }
}

require_once dirname( path: __DIR__ ) . '/includes/frontend-language.php';
require_once dirname( path: __DIR__ ) . '/includes/meta-box.php';
require_once dirname( path: __DIR__ ) . '/includes/hreflang-translations.php';
require_once dirname( path: __DIR__ ) . '/includes/admin-settings.php';

$failed = 0;
$passed = 0;

/**
 * Assert a condition and report it.
 *
 * @param string $name Case name.
 * @param bool   $ok   Whether the case passed.
 * @param string $note Extra detail printed on failure.
 * @return void
 */
function jpkcom_check( string $name, bool $ok, string $note = '' ): void {
    global $failed, $passed;

    if ( $ok ) {
        ++$passed;
        printf( "  ok   %s\n", $name );
        return;
    }

    ++$failed;
    printf( "  FAIL %s%s\n", $name, $note !== '' ? ' -- ' . $note : '' );
}

echo "jpkcom-simple-lang: locale handling regressions\n";

/* --- locale validation -------------------------------------------------- */

/*
 * 1.2.8 validated with /^[a-z]{2,3}(_[A-Z]{2})?$/, which rejects every
 * WordPress variant locale. de_DE_formal is the sharp case: the meta box offers
 * it, this plugin ships translations for it, and save_post dropped it silently.
 */
jpkcom_check(
    'a validation helper exists',
    function_exists( function: 'jpkcom_simplelang_is_valid_locale' ),
    'validation is not reachable outside save_post'
);

if ( function_exists( function: 'jpkcom_simplelang_is_valid_locale' ) ) {
    foreach ( array( 'de_DE', 'de_DE_formal', 'de_AT', 'en_US' ) as $locale ) {
        jpkcom_check(
            sprintf( '%s is accepted', $locale ),
            jpkcom_simplelang_is_valid_locale( $locale )
        );
    }

    // Not installed, and not invented: both must be refused.
    foreach ( array( 'fr_FR', 'zz_ZZ', 'nonsense', '', 'de_DE"><script>' ) as $locale ) {
        jpkcom_check(
            sprintf( '%s is refused', var_export( $locale, true ) ),
            ! jpkcom_simplelang_is_valid_locale( $locale )
        );
    }
}

/* --- BCP 47 -------------------------------------------------------------- */

jpkcom_check(
    'a BCP 47 helper exists',
    function_exists( function: 'jpkcom_simplelang_get_bcp47' )
);

if ( function_exists( function: 'jpkcom_simplelang_get_bcp47' ) ) {
    $expected = array(
        'de_DE'          => 'de-DE',
        'de_AT'          => 'de-AT',
        'de_DE_formal'   => 'de-DE',
        'de_CH_informal' => 'de-CH',
        'pt_PT_ao90'     => 'pt-PT',
        'pt_BR'          => 'pt-BR',
        'en_US'          => 'en-US',
        'ca'             => 'ca',
    );

    foreach ( $expected as $locale => $tag ) {
        jpkcom_check(
            sprintf( '%s becomes %s', $locale, $tag ),
            jpkcom_simplelang_get_bcp47( $locale ) === $tag,
            'got ' . jpkcom_simplelang_get_bcp47( $locale )
        );
    }
}

/* --- hreflang output ----------------------------------------------------- */

/**
 * Capture the emitted hreflang markup.
 *
 * @param int   $post_id      Current post.
 * @param int[] $translations Linked posts.
 * @return string Markup.
 */
function jpkcom_hreflang( int $post_id, array $translations ): string {
    ob_start();
    jpkcom_simplelang_output_hreflang_tags( $post_id, $translations );

    return (string) ob_get_clean();
}

/*
 * The case that made 1.2.8 emit the same hreflang value twice: two regional
 * variants of one language, which is what hreflang exists for.
 */
$GLOBALS['jpkcom_meta'] = array(
    1 => array( '_jpkcom_simplelang_language' => 'de_DE' ),
    2 => array( '_jpkcom_simplelang_language' => 'de_AT' ),
);

$markup = jpkcom_hreflang( 1, array( 2 ) );

jpkcom_check(
    'de_DE and de_AT get distinct hreflang values',
    str_contains( haystack: $markup, needle: 'hreflang="de-DE"' )
    && str_contains( haystack: $markup, needle: 'hreflang="de-AT"' ),
    'markup: ' . trim( preg_replace( '/\s+/', ' ', $markup ) ?? '' )
);

jpkcom_check(
    'no bare language subtag is emitted',
    ! str_contains( haystack: $markup, needle: 'hreflang="de"' )
);

jpkcom_check(
    'x-default is emitted',
    str_contains( haystack: $markup, needle: 'hreflang="x-default"' ),
    'markup: ' . trim( preg_replace( '/\s+/', ' ', $markup ) ?? '' )
);

jpkcom_check(
    'x-default points at the site default language version',
    str_contains( haystack: $markup, needle: '<link rel="alternate" hreflang="x-default" href="https://example.test/post-1/" />' ),
    'WPLANG is de_DE, so post 1 is the default version'
);

// A version with no language of its own counts as the site default.
$GLOBALS['jpkcom_meta'] = array(
    3 => array(),
    4 => array( '_jpkcom_simplelang_language' => 'de_AT' ),
);

$markup = jpkcom_hreflang( 3, array( 4 ) );

jpkcom_check(
    'an unset language falls back to the site default locale',
    str_contains( haystack: $markup, needle: 'hreflang="de-DE"' )
    && str_contains( haystack: $markup, needle: 'href="https://example.test/post-3/" />' )
);

/*
 * A variant of the default locale still counts as the default version. Found by
 * testing on a live site: comparing raw locales left a de_DE site whose German
 * page was set to de_DE_formal without any x-default.
 */
$GLOBALS['jpkcom_meta'] = array(
    9  => array( '_jpkcom_simplelang_language' => 'de_DE_formal' ),
    10 => array( '_jpkcom_simplelang_language' => 'de_AT' ),
);

$markup = jpkcom_hreflang( 9, array( 10 ) );

jpkcom_check(
    'a variant of the default locale still yields x-default',
    str_contains( haystack: $markup, needle: '<link rel="alternate" hreflang="x-default" href="https://example.test/post-9/" />' ),
    'markup: ' . trim( preg_replace( '/\s+/', ' ', $markup ) ?? '' )
);

// Two versions mapping to the same tag must not contradict each other.
$GLOBALS['jpkcom_meta'] = array(
    5 => array( '_jpkcom_simplelang_language' => 'de_DE' ),
    6 => array( '_jpkcom_simplelang_language' => 'de_DE_formal' ),
);

$markup = jpkcom_hreflang( 5, array( 6 ) );

jpkcom_check(
    'two versions with the same tag yield one entry',
    1 === substr_count( $markup, 'hreflang="de-DE"' ),
    sprintf( '%d occurrences', substr_count( $markup, 'hreflang="de-DE"' ) )
);

// No default-language version in the set: no x-default rather than a guess.
$GLOBALS['jpkcom_options']['WPLANG'] = 'en_US';
$GLOBALS['jpkcom_meta']              = array(
    7 => array( '_jpkcom_simplelang_language' => 'de_DE' ),
    8 => array( '_jpkcom_simplelang_language' => 'de_AT' ),
);

$markup = jpkcom_hreflang( 7, array( 8 ) );

jpkcom_check(
    'x-default is omitted when no version carries the default language',
    ! str_contains( haystack: $markup, needle: 'x-default' )
);

$GLOBALS['jpkcom_options']['WPLANG'] = 'de_DE';

/* --- the locale filter is gone ------------------------------------------ */

/*
 * switch_to_locale() installs WP_Locale_Switcher::filter_locale() itself. The
 * plugin's own `locale` filter was redundant when the switch succeeded, and when
 * it failed it was what made the request claim a language WordPress had not
 * loaded.
 */
jpkcom_check(
    'no own locale filter is registered any more',
    ( $GLOBALS['jpkcom_hooks']['filter']['locale'] ?? array() ) === array(),
    'still filtering locale'
);

jpkcom_check(
    'language_attributes is still filtered',
    ( $GLOBALS['jpkcom_hooks']['filter']['language_attributes'] ?? array() ) !== array()
);

$lang_attr = $GLOBALS['jpkcom_hooks']['filter']['language_attributes'][0][0];
$GLOBALS['jpkcom_simplelang_current_language'] = 'de_DE';

jpkcom_check(
    'the html lang attribute carries the region',
    $lang_attr( 'lang="en-US"' ) === 'lang="de-DE"',
    'got ' . $lang_attr( 'lang="en-US"' )
);

unset( $GLOBALS['jpkcom_simplelang_current_language'] );

jpkcom_check(
    'without a post language the attribute is untouched',
    $lang_attr( 'lang="en-US"' ) === 'lang="en-US"'
);

/* --- settings sanitiser ------------------------------------------------- */

/*
 * 1.2.8 typed this ?array, so a scalar raised a TypeError before the is_array()
 * guard inside could run - a hand-edited options form produced a fatal instead
 * of the documented fallback.
 */
$cases = array(
    'array of valid types' => array( array( 'post', 'page' ), array( 'post', 'page' ) ),
    'unknown type dropped' => array( array( 'post', 'nope' ), array( 'post' ) ),
    'scalar'               => array( 'post', array( 'post', 'page' ) ),
    'null'                 => array( null, array( 'post', 'page' ) ),
    'integer'              => array( 42, array( 'post', 'page' ) ),
);

foreach ( $cases as $label => $case ) {
    list( $input, $expected ) = $case;

    try {
        $result = jpkcom_simplelang_sanitize_post_types( $input );
        jpkcom_check(
            sprintf( 'sanitiser handles %s', $label ),
            $result === $expected,
            'got ' . json_encode( $result )
        );
    } catch ( Throwable $e ) {
        jpkcom_check(
            sprintf( 'sanitiser handles %s', $label ),
            false,
            get_class( $e ) . ': ' . substr( $e->getMessage(), 0, 70 )
        );
    }
}

printf( "\n  %d passed, %d failed\n", $passed, $failed );

exit( $failed > 0 ? 1 : 0 );
