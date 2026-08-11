<?php
/**
 * Guards for the Abilities API registration.
 *
 * Runs without WordPress: jpkcom_simplelang_get_ability_definitions() reads no
 * WordPress state and touches no registry. The few core functions it reaches are
 * stubbed below.
 *
 * Several checks are STRUCTURAL rather than per-case on purpose. A sibling
 * plugin shipped its unknown-key guard on two abilities of three and stayed
 * green, because every assertion written for that guard happened to target an
 * ability that had it.
 *
 * @package   JPKCom_Simple_Lang
 */

declare(strict_types=1);

define( 'ABSPATH', dirname( __DIR__ ) . '/' );

function __( string $text, string $domain = 'default' ): string {
	return $text;
}

function apply_filters( string $hook, mixed $value, mixed ...$args ): mixed {
	return $value;
}

function add_action( string $hook, mixed $callback, int $priority = 10, int $accepted = 1 ): bool {
	return true;
}

function add_filter( string $hook, mixed $callback, int $priority = 10, int $accepted = 1 ): bool {
	return true;
}

function esc_attr( string $text ): string {
	return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
}

function get_option( string $option, mixed $default_value = false ): mixed {
	return $default_value;
}

// The emitter's own conversion, so the schema descriptions are checked against
// the real thing rather than against a copy of it.
require_once dirname( __DIR__ ) . '/includes/frontend-language.php';

$root = dirname( __DIR__ );

require_once $root . '/includes/abilities.php';

$pass = 0;
$fail = 0;

function section( string $title ): void {
	echo "\n" . $title . "\n";
}

function chk( string $label, bool $ok, string $why = '' ): void {
	global $pass, $fail;

	if ( $ok ) {
		$pass++;
		echo "  PASS  {$label}\n";
		return;
	}

	$fail++;
	echo "  FAIL  {$label}\n";

	if ( $why !== '' ) {
		echo "        why:  {$why}\n";
	}
}

function body_of( string $source, string $needle ): ?string {
	$start = strpos( $source, $needle );

	if ( $start === false ) {
		return null;
	}

	$open  = strpos( $source, '{', $start );
	$depth = 0;

	for ( $i = $open, $len = strlen( $source ); $i < $len; $i++ ) {
		if ( $source[ $i ] === '{' ) {
			$depth++;
		} elseif ( $source[ $i ] === '}' ) {
			$depth--;
			if ( $depth === 0 ) {
				return substr( $source, $open, $i - $open );
			}
		}
	}

	return null;
}

$defs  = jpkcom_simplelang_get_ability_definitions();
$names = array_keys( $defs );
$src   = (string) file_get_contents( $root . '/includes/abilities.php' );

// --- Registration shape -----------------------------------------------------

section( 'Registration shape' );

chk( 'two abilities are defined', count( $defs ) === 2, 'Got ' . count( $defs ) . '. Every loop below iterates this list.' );

chk(
	'the names are the documented ones',
	$names === [ 'jpkcom-simple-lang/list-languages', 'jpkcom-simple-lang/check-translation-sets' ],
	'Ability names are a public contract; renaming one breaks every caller that stored it.'
);

foreach ( $defs as $name => $args ) {

	$ann = $args['meta']['annotations'] ?? [];

	chk(
		$name . ': all three annotations set explicitly',
		( $ann['readonly'] ?? null ) === true && ( $ann['destructive'] ?? null ) === false && ( $ann['idempotent'] ?? null ) === true,
		'They default to null, and the REST run controller derives the HTTP verb from them: without readonly the run route is POST-only.'
	);

	chk(
		$name . ': registered into the shared content category',
		( $args['category'] ?? null ) === 'jpkcom-content',
		'Categories are global and first-wins; the JPKCom content plugins share one.'
	);

	chk(
		$name . ': has label and description',
		is_string( $args['label'] ?? null ) && $args['label'] !== ''
			&& is_string( $args['description'] ?? null ) && $args['description'] !== '',
		'These are what an agent reads to decide whether to call the ability at all.'
	);

	$schema = $args['input_schema'] ?? [];

	chk(
		$name . ': carries a top-level default',
		array_key_exists( 'default', $schema ),
		'normalize_input() substitutes the TOP-LEVEL default when the input is exactly null, and nothing else does. Without it the most obvious call - no parameters at all - fails validation before the callback runs.'
	);

	chk(
		$name . ': the default encodes as {} and not []',
		json_encode( $schema['default'] ?? null ) === '{}',
		'The schema declares type: object. PHP serialises an empty array as a JSON array, and the MCP adapter publishes get_input_schema() verbatim.'
	);

	$declared = array_keys( (array) ( $schema['properties'] ?? [] ) );
	$guarded  = JPKCOM_SIMPLELANG_ABILITY_INPUT_KEYS[ $name ] ?? null;
	sort( $declared );
	if ( is_array( $guarded ) ) { sort( $guarded ); }

	chk(
		$name . ': guarded input keys match the schema properties',
		$guarded === $declared,
		'Two statements of one list. A key only in the schema is refused although it is declared to callers - a guard rejecting a correct request. A key only in the constant is waved through and read by nobody. Got '
			. var_export( $guarded, true ) . ', schema declares ' . var_export( $declared, true ) . '.'
	);

}

$ll = $defs['jpkcom-simple-lang/list-languages']['input_schema'] ?? [];

chk(
	'list-languages declares NO properties key at all',
	! array_key_exists( 'properties', $ll ),
	'An empty stdClass here is an anonymous fatal - core does an array offset on it - and an empty array is invalid JSON Schema.'
);

chk(
	'list-languages refuses unknown keys through the schema',
	( $ll['additionalProperties'] ?? null ) === false,
	'It declares no properties, so this is the only thing that can refuse a key there.'
);

chk(
	'check-translation-sets does NOT declare additionalProperties',
	! array_key_exists( 'additionalProperties', $defs['jpkcom-simple-lang/check-translation-sets']['input_schema'] ?? [] ),
	'validate_input() runs BEFORE the execute callback, so declaring it preempts the plugin guard and replaces a message naming the accepted keys with core\'s "not a valid property of the object".'
);

// --- Structural guards ------------------------------------------------------

section( 'Structural guards on the callbacks' );

foreach ( [ 'list_languages', 'check_sets' ] as $slug ) {

	chk(
		$slug . '_inner calls the unknown-key guard',
		str_contains( (string) body_of( $src, 'function jpkcom_simplelang_ability_' . $slug . '_inner(' ), 'jpkcom_simplelang_ability_validate_input_keys(' ),
		'A guard on a subset of the abilities is a trap: a caller that learned the refusal on one assumes it everywhere.'
	);

	chk(
		$slug . ' runs inside the Throwable boundary',
		str_contains( (string) body_of( $src, 'function jpkcom_simplelang_ability_' . $slug . '(' ), 'jpkcom_simplelang_ability_boundary(' ),
		'A Throwable escaping an ability callback is an error the client cannot act on.'
	);

	chk(
		$slug . ': permission callback resolves to a capability check',
		str_contains(
			(string) body_of( $src, 'function jpkcom_simplelang_ability_permission_' . ( $slug === 'check_sets' ? 'check_sets' : 'list_languages' ) . '(' ),
			'jpkcom_simplelang_ability_capability('
		),
		'The argument an ability permission callback receives is the validated input value, never a request object.'
	);

}

chk(
	'the default capability is edit_posts, not read',
	str_contains( (string) body_of( $src, 'function jpkcom_simplelang_ability_capability(' ), "'edit_posts'" ),
	'This reports an editorial and SEO condition and names unpublished and dangling posts while doing so. That is not visitor-facing content.'
);

// --- Agreement with the emitter ---------------------------------------------

section( 'The report is derived from the emitter, not from an opinion' );

chk(
	'the enabled post types come from the plugin option, with the plugin default',
	str_contains( $src, "get_option( 'jpkcom_simplelang_enabled_post_types', [ 'post', 'page' ] )" ),
	'Restating either the option name or its default would make this a second source of truth the moment the settings page changes.'
);

chk(
	'a member with no language of its own is read as the site default',
	str_contains( $src, 'jpkcom_simplelang_get_site_default_locale()' )
		&& str_contains( $src, '$uses_default = trim( string: $locale ) === \'\'' ),
	'The emitter substitutes the site default for an empty language, so such a version collides with an explicit one in that language rather than being ignored. Treating it as "no language" would miss exactly those collisions.'
);

chk(
	'tags are compared as BCP 47, not as raw locales',
	str_contains( (string) body_of( $src, 'function jpkcom_simplelang_ability_inspect_set(' ), 'jpkcom_simplelang_get_bcp47(' ),
	'de_DE and de_DE_formal are different locales and the same tag. Comparing raw locales would miss the collision this ability exists to surface.'
);

chk(
	'a collision reports no winner',
	! preg_match( '/\bwinner\b|\bwins\b|\bsurviv/i', (string) body_of( $src, 'function jpkcom_simplelang_ability_inspect_set(' ) ),
	'Which of two colliding versions keeps its entry depends on the order the posts come back in, which this ability does not control. Naming one would be a claim it cannot support.'
);

chk(
	'an unpublished member alone does not qualify a set',
	str_contains( (string) body_of( $src, 'function jpkcom_simplelang_ability_inspect_set(' ), "array_diff_key( \$issues, [ 'unpublished_member' => true ] )" ),
	'A draft translation is ordinary work in progress. An audit that flags every unfinished translation stops being read, which costs the same as a guard with false positives.'
);

chk(
	'the scan cap is reported rather than applied quietly',
	str_contains( $src, "'scan_truncated'" ) && str_contains( $src, "'scan_limit'" ),
	'An answer that stopped early while looking complete is the worse failure.'
);

// --- BCP 47 conversion, against the real function ---------------------------

section( 'The tag conversion this ability depends on' );

chk(
	'de_DE and de_DE_formal collapse to one tag',
	jpkcom_simplelang_get_bcp47( 'de_DE' ) === jpkcom_simplelang_get_bcp47( 'de_DE_formal' ),
	'This is the whole premise of the collision finding. If it stopped being true the finding would be reporting something that no longer happens.'
);

chk(
	'de_DE and de_AT stay distinct',
	jpkcom_simplelang_get_bcp47( 'de_DE' ) !== jpkcom_simplelang_get_bcp47( 'de_AT' ),
	'Regional variants are exactly what hreflang exists for; if these collapsed, every de site would report false collisions.'
);

printf( "\n  %d passed, %d failed\n", $pass, $fail );

exit( $fail > 0 ? 1 : 0 );
