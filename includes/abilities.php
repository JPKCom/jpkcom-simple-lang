<?php
/**
 * WordPress Abilities API integration.
 *
 * Two read-only abilities: the language vocabulary this site actually uses, and
 * the translation sets whose hreflang output comes out incomplete.
 *
 * The second is the one worth having. The emitter resolves each version to a
 * BCP 47 tag and keeps the FIRST entry per tag, so two versions that map to the
 * same tag - two de_DE posts, or de_DE plus de_DE_formal - contribute one entry
 * and the other URL gets no annotation at all. That is deliberate (a
 * contradictory pair would be worse) but it is also silent: nothing in the admin
 * says a page was dropped. Same for a set with no version in the site's default
 * language, which is emitted without x-default on purpose.
 *
 * @package   JPKCom_Simple_Lang
 * @author    Jean Pierre Kolb <jpk@jpkc.com>
 * @license   GPL-2.0-or-later
 * @link      https://github.com/JPKCom/jpkcom-simple-lang
 */

declare(strict_types=1);

if ( ! defined( constant_name: 'ABSPATH' ) ) {

	exit;

}


if ( ! defined( constant_name: 'JPKCOM_SIMPLELANG_ABILITY_CATEGORY' ) ) {

	/**
	 * Ability category.
	 *
	 * Categories are global and registration is FIRST-WINS, so this goes through
	 * wp_has_ability_category() rather than assuming.
	 *
	 * @since 1.3.0
	 */
	define( 'JPKCOM_SIMPLELANG_ABILITY_CATEGORY', 'jpkcom-content' );

}

if ( ! defined( constant_name: 'JPKCOM_SIMPLELANG_ABILITY_PER_PAGE_DEFAULT' ) ) {

	/**
	 * Default page size for the set check.
	 *
	 * @since 1.3.0
	 */
	define( 'JPKCOM_SIMPLELANG_ABILITY_PER_PAGE_DEFAULT', 20 );

}

if ( ! defined( constant_name: 'JPKCOM_SIMPLELANG_ABILITY_PER_PAGE_MAX' ) ) {

	/**
	 * Largest page size honoured.
	 *
	 * @since 1.3.0
	 */
	define( 'JPKCOM_SIMPLELANG_ABILITY_PER_PAGE_MAX', 100 );

}

if ( ! defined( constant_name: 'JPKCOM_SIMPLELANG_ABILITY_SCAN_LIMIT' ) ) {

	/**
	 * Largest number of linked posts examined in one call.
	 *
	 * Building the sets means reading every post that carries a translation link,
	 * which is unbounded on a large site. The cap is reported back rather than
	 * applied silently: an answer that stopped early while looking complete is
	 * the failure this field exists to prevent.
	 *
	 * @since 1.3.0
	 */
	define( 'JPKCOM_SIMPLELANG_ABILITY_SCAN_LIMIT', 2000 );

}

if ( ! defined( constant_name: 'JPKCOM_SIMPLELANG_ABILITY_INPUT_KEYS' ) ) {

	/**
	 * Top-level input keys each ability declares.
	 *
	 * Cross-checked against the registered schemas by tests/test-abilities.php.
	 * additionalProperties is deliberately not declared on the schema that
	 * carries properties: validate_input() runs before the execute callback and
	 * would preempt the guard below, replacing a message naming the accepted keys
	 * with core's "not a valid property of the object".
	 *
	 * @since 1.3.0
	 */
	define(
		'JPKCOM_SIMPLELANG_ABILITY_INPUT_KEYS',
		[
			'jpkcom-simple-lang/list-languages'         => [],
			'jpkcom-simple-lang/check-translation-sets' => [ 'page', 'per_page' ],
		]
	);

}


if ( ! function_exists( function: 'jpkcom_simplelang_abilities_enabled' ) ) {

	/**
	 * Decide whether the abilities should be registered at all.
	 *
	 * @since 1.3.0
	 *
	 * @return bool True when registration should proceed.
	 */
	function jpkcom_simplelang_abilities_enabled(): bool {

		if ( defined( constant_name: 'JPKCOM_SIMPLELANG_ABILITIES' ) && ! JPKCOM_SIMPLELANG_ABILITIES ) {

			return false;

		}

		return function_exists( function: 'wp_register_ability' )
			&& function_exists( function: 'wp_register_ability_category' )
			&& function_exists( function: 'jpkcom_simplelang_get_bcp47' );

	}

}


if ( ! function_exists( function: 'jpkcom_simplelang_ability_log' ) ) {

	/**
	 * Write a debug line, and only with WP_DEBUG.
	 *
	 * @since 1.3.0
	 *
	 * @param string $message Message.
	 * @return void
	 */
	function jpkcom_simplelang_ability_log( string $message ): void {

		if ( defined( constant_name: 'WP_DEBUG' ) && WP_DEBUG ) {

			error_log( message: '[jpkcom-simple-lang] ' . $message );

		}

	}

}


if ( ! function_exists( function: 'jpkcom_simplelang_ability_error' ) ) {

	/**
	 * Build a WP_Error carrying an HTTP status.
	 *
	 * Without data['status'] rest_ensure_response() defaults to 500, and a 5xx
	 * tells an agent "transient fault, retry unchanged" - the opposite of what a
	 * caller mistake needs to hear.
	 *
	 * @since 1.3.0
	 *
	 * @param string $code    Error code.
	 * @param string $message Message.
	 * @param int    $status  HTTP status.
	 * @return WP_Error Error.
	 */
	function jpkcom_simplelang_ability_error( string $code, string $message, int $status = 400 ): WP_Error {

		return new WP_Error( $code, $message, [ 'status' => $status ] );

	}

}


if ( ! function_exists( function: 'jpkcom_simplelang_ability_boundary' ) ) {

	/**
	 * Turn a Throwable out of a callback into a WP_Error.
	 *
	 * @since 1.3.0
	 *
	 * @param callable $body    Callback.
	 * @param string   $ability Ability name.
	 * @return array<string, mixed>|WP_Error Result or error.
	 */
	function jpkcom_simplelang_ability_boundary( callable $body, string $ability ): array|WP_Error {

		try {

			return $body();

		} catch ( \Throwable $e ) {

			jpkcom_simplelang_ability_log( $ability . ' failed: ' . $e->getMessage() );

			return jpkcom_simplelang_ability_error(
				'jpkcom_simplelang_read_failed',
				__( 'The language data on this site could not be read. This is a condition on the site, not a problem with the request, so repeating the call unchanged will not help; the details are in the site error log.', 'jpkcom-simple-lang' ),
				500
			);

		}

	}

}


if ( ! function_exists( function: 'jpkcom_simplelang_ability_json_object' ) ) {

	/**
	 * Wrap an empty map so it encodes as {} rather than [].
	 *
	 * @since 1.3.0
	 *
	 * @param array<string, mixed> $map Map.
	 * @return array<string, mixed>|stdClass Map, or an empty object.
	 */
	function jpkcom_simplelang_ability_json_object( array $map ): array|stdClass {

		return $map === [] ? (object) [] : $map;

	}

}


if ( ! function_exists( function: 'jpkcom_simplelang_ability_capability' ) ) {

	/**
	 * Check the capability required to run an ability.
	 *
	 * `edit_posts`, not `read`. This reports an editorial and SEO condition of
	 * the site - which pages are annotated for which language, and where that
	 * annotation comes out incomplete - and it names unpublished and dangling
	 * posts while doing so. That is not visitor-facing content.
	 *
	 * @since 1.3.0
	 *
	 * @param string $ability Ability name.
	 * @return bool True when the current user may run it.
	 */
	function jpkcom_simplelang_ability_capability( string $ability ): bool {

		/**
		 * Filter the capability required to run a JPKCom Simple Lang ability.
		 *
		 * @since 1.3.0
		 *
		 * @param string $capability Capability name.
		 * @param string $ability    Ability name.
		 */
		$capability = apply_filters( 'jpkcom_simplelang_ability_capability', 'edit_posts', $ability );

		return current_user_can( is_string( value: $capability ) ? $capability : 'edit_posts' );

	}

}


if ( ! function_exists( function: 'jpkcom_simplelang_ability_meta' ) ) {

	/**
	 * Build the meta array for an ability.
	 *
	 * All three annotations explicit: they default to null and the REST run
	 * controller derives the HTTP verb from them, so without readonly the run
	 * route would be POST-only.
	 *
	 * @since 1.3.0
	 *
	 * @param string $ability Ability name.
	 * @return array<string, mixed> Meta array.
	 */
	function jpkcom_simplelang_ability_meta( string $ability ): array {

		$meta = [
			'show_in_rest' => true,
			'public'       => true,
			'mcp'          => [ 'public' => true ],
			'annotations'  => [
				'readonly'    => true,
				'destructive' => false,
				'idempotent'  => true,
			],
		];

		/**
		 * Filter the meta array of a JPKCom Simple Lang ability.
		 *
		 * @since 1.3.0
		 *
		 * @param array<string, mixed> $meta    Meta array.
		 * @param string               $ability Ability name.
		 */
		$filtered = apply_filters( 'jpkcom_simplelang_ability_meta', $meta, $ability );

		return is_array( value: $filtered ) ? $filtered : $meta;

	}

}


if ( ! function_exists( function: 'jpkcom_simplelang_ability_normalise_input' ) ) {

	/**
	 * Bring the value core hands the callback into array form.
	 *
	 * normalize_input() substitutes the schema's top-level default when the input
	 * is exactly null, and that default is a stdClass - so the callback receives
	 * an object and must read it.
	 *
	 * @since 1.3.0
	 *
	 * @param mixed $input Raw input.
	 * @return array<string, mixed>|null Array form, or null when unusable.
	 */
	function jpkcom_simplelang_ability_normalise_input( mixed $input ): ?array {

		if ( $input === null ) {

			return [];

		}

		if ( is_object( value: $input ) ) {

			return get_object_vars( object: $input );

		}

		return is_array( value: $input ) ? $input : null;

	}

}


if ( ! function_exists( function: 'jpkcom_simplelang_ability_validate_input_keys' ) ) {

	/**
	 * Refuse a top-level input key the ability does not declare.
	 *
	 * On EVERY ability, not a subset: a caller that learned the refusal on one
	 * assumes it everywhere, and the ability that silently accepts is the one it
	 * will trust.
	 *
	 * @since 1.3.0
	 *
	 * @param array<string, mixed> $input   Raw input.
	 * @param string[]             $allowed Declared keys.
	 * @return true|WP_Error True when every key is declared.
	 */
	function jpkcom_simplelang_ability_validate_input_keys( array $input, array $allowed ): true|WP_Error {

		$unknown = [];

		foreach ( array_keys( $input ) as $key ) {

			if ( ! in_array( needle: (string) $key, haystack: $allowed, strict: true ) ) {

				$unknown[] = (string) $key;

			}

		}

		if ( $unknown === [] ) {

			return true;

		}

		return jpkcom_simplelang_ability_error(
			'jpkcom_simplelang_unknown_input_key',
			sprintf(
				/* translators: 1: comma-separated rejected keys, 2: comma-separated accepted keys. */
				__( 'Unknown input key: %1$s. This ability accepts: %2$s. A key it does not declare is never read, so the request would be answered as though that key had not been sent.', 'jpkcom-simple-lang' ),
				implode( ', ', $unknown ),
				$allowed === [] ? __( 'no input at all', 'jpkcom-simple-lang' ) : implode( ', ', $allowed )
			),
			400
		);

	}

}


if ( ! function_exists( function: 'jpkcom_simplelang_ability_enabled_post_types' ) ) {

	/**
	 * The post types language selection is enabled for.
	 *
	 * Reads the same option and the same default the rest of the plugin reads,
	 * rather than restating either.
	 *
	 * @since 1.3.0
	 *
	 * @return string[] Post type names.
	 */
	function jpkcom_simplelang_ability_enabled_post_types(): array {

		$types = get_option( 'jpkcom_simplelang_enabled_post_types', [ 'post', 'page' ] );

		return is_array( value: $types ) ? array_values( array_filter( $types, 'is_string' ) ) : [];

	}

}


if ( ! function_exists( function: 'jpkcom_simplelang_ability_describe_locale' ) ) {

	/**
	 * Describe a locale the way the front end resolves it.
	 *
	 * `installed` matters more than it looks: a locale whose language pack was
	 * removed stays in the post meta, and switch_to_locale() then returns false,
	 * so the front end deliberately does NOT switch. The page keeps the site
	 * language while its meta still claims another one.
	 *
	 * @since 1.3.0
	 *
	 * @param string   $locale    Locale, or '' for "no language of its own".
	 * @param string[] $installed Installed locales.
	 * @return array<string, mixed> Description.
	 */
	function jpkcom_simplelang_ability_describe_locale( string $locale, array $installed ): array {

		return [
			'locale'    => $locale,
			'bcp47'     => $locale === '' ? '' : jpkcom_simplelang_get_bcp47( $locale ),
			'name'      => $locale === '' ? '' : jpkcom_simplelang_get_language_name( $locale ),
			'installed' => $locale === '' || $locale === 'en_US' || in_array( needle: $locale, haystack: $installed, strict: true ),
		];

	}

}


if ( ! function_exists( function: 'jpkcom_simplelang_ability_list_languages_inner' ) ) {

	/**
	 * Report which languages this site actually uses.
	 *
	 * @since 1.3.0
	 *
	 * @param mixed $input Ability input.
	 * @return array<string, mixed>|WP_Error Result.
	 */
	function jpkcom_simplelang_ability_list_languages_inner( mixed $input = null ): array|WP_Error {

		global $wpdb;

		$normalised = jpkcom_simplelang_ability_normalise_input( $input );

		if ( $normalised === null ) {

			return jpkcom_simplelang_ability_error(
				'jpkcom_simplelang_invalid_input',
				__( 'This ability takes no parameters. Call it with no input at all.', 'jpkcom-simple-lang' )
			);

		}

		$keys_valid = jpkcom_simplelang_ability_validate_input_keys(
			$normalised,
			JPKCOM_SIMPLELANG_ABILITY_INPUT_KEYS['jpkcom-simple-lang/list-languages']
		);

		if ( $keys_valid instanceof WP_Error ) {

			return $keys_valid;

		}

		$types     = jpkcom_simplelang_ability_enabled_post_types();
		$installed = get_available_languages();
		$installed = is_array( value: $installed ) ? $installed : [];
		$default   = jpkcom_simplelang_get_site_default_locale();

		if ( $types === [] ) {

			return [
				'enabled_post_types'     => [],
				'site_default'           => jpkcom_simplelang_ability_describe_locale( $default, $installed ),
				'installed_languages'    => $installed,
				'languages_in_use'       => [],
				'posts_total'            => 0,
				'posts_without_language' => 0,
				'language'               => determine_locale(),
			];

		}

		$placeholders = implode( ',', array_fill( 0, count( $types ), '%s' ) );

		// One statement rather than a query per language: the answer is a
		// GROUP BY over the meta, and walking the posts in PHP would make every
		// count a separate round trip.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT COALESCE( m.meta_value, '' ) AS locale, COUNT(*) AS posts
				FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->postmeta} m
					ON m.post_id = p.ID AND m.meta_key = '_jpkcom_simplelang_language'
				WHERE p.post_type IN ( {$placeholders} )
					AND p.post_status = 'publish'
				GROUP BY locale
				ORDER BY posts DESC",
				...$types
			)
		);

		$in_use    = [];
		$total     = 0;
		$no_locale = 0;

		if ( is_array( value: $rows ) ) {

			foreach ( $rows as $row ) {

				$locale = (string) $row->locale;
				$count  = (int) $row->posts;
				$total += $count;

				if ( trim( string: $locale ) === '' ) {

					$no_locale = $count;

					continue;

				}

				$in_use[] = jpkcom_simplelang_ability_describe_locale( $locale, $installed ) + [ 'posts' => $count ];

			}

		}

		return [
			'enabled_post_types'     => $types,
			'site_default'           => jpkcom_simplelang_ability_describe_locale( $default, $installed ),
			'installed_languages'    => $installed,
			'languages_in_use'       => $in_use,
			'posts_total'            => $total,
			'posts_without_language' => $no_locale,
			'language'               => determine_locale(),
		];

	}

}


if ( ! function_exists( function: 'jpkcom_simplelang_ability_build_sets' ) ) {

	/**
	 * Collect the translation sets on this site.
	 *
	 * A set is a post plus everything it links to. Because the links are
	 * bidirectional, the same set is reachable from each of its members, so sets
	 * are deduplicated by their sorted member list.
	 *
	 * @since 1.3.0
	 *
	 * @param string[] $types Enabled post types.
	 * @return array{sets: array<string, int[]>, scanned: int, truncated: bool} Sets.
	 */
	function jpkcom_simplelang_ability_build_sets( array $types ): array {

		global $wpdb;

		if ( $types === [] ) {

			return [
				'sets'      => [],
				'scanned'   => 0,
				'truncated' => false,
			];

		}

		$placeholders = implode( ',', array_fill( 0, count( $types ), '%s' ) );
		$limit        = JPKCOM_SIMPLELANG_ABILITY_SCAN_LIMIT;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT m.post_id, m.meta_value
				FROM {$wpdb->postmeta} m
				INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id
				WHERE m.meta_key = '_jpkcom_simplelang_translations'
					AND p.post_type IN ( {$placeholders} )
				ORDER BY m.post_id ASC
				LIMIT %d",
				...[ ...$types, $limit + 1 ]
			)
		);

		$rows      = is_array( value: $rows ) ? $rows : [];
		$truncated = count( $rows ) > $limit;
		$rows      = array_slice( $rows, 0, $limit );

		$links = [];

		foreach ( $rows as $row ) {

			$from = (int) $row->post_id;
			$to   = (int) $row->meta_value;

			if ( $from < 1 || $to < 1 ) {

				continue;

			}

			$links[ $from ][] = $to;

		}

		$sets = [];

		foreach ( $links as $post_id => $targets ) {

			$members = array_values( array_unique( array_merge( [ $post_id ], $targets ) ) );
			sort( $members );

			$sets[ implode( ',', $members ) ] = $members;

		}

		return [
			'sets'      => $sets,
			'scanned'   => count( $links ),
			'truncated' => $truncated,
		];

	}

}


if ( ! function_exists( function: 'jpkcom_simplelang_ability_inspect_set' ) ) {

	/**
	 * Report what the hreflang output for one set will and will not contain.
	 *
	 * Every finding below is derived from what the emitter does, not from an
	 * opinion about what it should do:
	 *
	 * - It keys entries by BCP 47 tag and keeps the FIRST per tag, so two members
	 *   resolving to one tag yield one entry and the other URL is annotated
	 *   nowhere. Which of the two survives depends on the order the posts come
	 *   back in, so this reports the collision and does NOT claim a winner.
	 * - A member with no language of its own is read as the site default, so it
	 *   collides with an explicit member in that language rather than being
	 *   ignored.
	 * - x-default is emitted only when some member carries the site default tag.
	 * - Only published posts are fetched, so an unpublished member never appears.
	 *
	 * @since 1.3.0
	 *
	 * @param int[]    $members     Member post IDs.
	 * @param string   $default_tag Site default BCP 47 tag.
	 * @param string[] $installed   Installed locales.
	 * @return array<string, mixed> Report for this set.
	 */
	function jpkcom_simplelang_ability_inspect_set( array $members, string $default_tag, array $installed ): array {

		$by_tag       = [];
		$entries      = [];
		$issues       = [];
		$missing_pack = [];

		foreach ( $members as $id ) {

			$post = get_post( $id );

			if ( ! $post instanceof WP_Post ) {

				$issues['dangling_member'][] = $id;

				continue;

			}

			$locale = (string) get_post_meta( post_id: $id, key: '_jpkcom_simplelang_language', single: true );
			$uses_default = trim( string: $locale ) === '';
			$effective    = $uses_default ? jpkcom_simplelang_get_site_default_locale() : $locale;
			$tag          = jpkcom_simplelang_get_bcp47( $effective );

			if ( ! $uses_default && $locale !== 'en_US' && ! in_array( needle: $locale, haystack: $installed, strict: true ) ) {

				$missing_pack[] = $id;

			}

			if ( $post->post_status !== 'publish' ) {

				$issues['unpublished_member'][] = $id;

				continue;

			}

			$entries[] = [
				'id'           => (int) $post->ID,
				'title'        => (string) get_the_title( $post ),
				'locale'       => $locale,
				'uses_default' => $uses_default,
				'bcp47'        => $tag,
			];

			$by_tag[ $tag ][] = (int) $post->ID;

		}

		foreach ( $by_tag as $tag => $ids ) {

			if ( count( $ids ) > 1 ) {

				$issues['tag_collision'][] = [
					'bcp47'   => (string) $tag,
					'posts'   => $ids,
					'emitted' => 1,
				];

			}

		}

		if ( $entries !== [] && ! array_key_exists( $default_tag, $by_tag ) ) {

			$issues['no_x_default'] = true;

		}

		if ( $missing_pack !== [] ) {

			$issues['language_pack_missing'] = $missing_pack;

		}

		// One-way links: the sync makes them mutual, but it only runs when a post
		// is saved through the meta box. An import, a direct write, or a member
		// whose links were rewritten elsewhere leaves the set asymmetric.
		$one_way = [];

		foreach ( $members as $id ) {

			$targets = array_map( 'intval', (array) get_post_meta( post_id: $id, key: '_jpkcom_simplelang_translations', single: false ) );
			$missing = array_values( array_diff( array_diff( $members, [ $id ] ), $targets ) );

			if ( $missing !== [] && get_post( $id ) instanceof WP_Post ) {

				$one_way[] = [
					'post'         => (int) $id,
					'not_linking_to' => $missing,
				];

			}

		}

		if ( $one_way !== [] ) {

			$issues['one_way_link'] = $one_way;

		}

		// An unpublished member does NOT by itself put a set in the report. A draft
		// translation is the ordinary state of work in progress, and an audit that
		// flags every unfinished translation stops being read - the same mechanic
		// as a guard with false positives, and the same cost. It stays in the
		// findings when the set qualifies on something else, because it is what
		// explains a tag count lower than the member count.
		$actionable = array_diff_key( $issues, [ 'unpublished_member' => true ] );

		return [
			'members'         => $members,
			'versions'        => $entries,
			'tags_emitted'    => count( $by_tag ),
			'issues'          => jpkcom_simplelang_ability_json_object( $issues ),
			'has_issues'      => $actionable !== [],
		];

	}

}


if ( ! function_exists( function: 'jpkcom_simplelang_ability_check_sets_inner' ) ) {

	/**
	 * Report the translation sets whose hreflang output comes out incomplete.
	 *
	 * @since 1.3.0
	 *
	 * @param mixed $input Ability input.
	 * @return array<string, mixed>|WP_Error Result.
	 */
	function jpkcom_simplelang_ability_check_sets_inner( mixed $input = null ): array|WP_Error {

		$normalised = jpkcom_simplelang_ability_normalise_input( $input );

		if ( $normalised === null ) {

			return jpkcom_simplelang_ability_error(
				'jpkcom_simplelang_invalid_input',
				__( 'The input has to be an object, for example {"per_page": 50}. Both parameters are optional; calling this ability with no input at all returns the first page.', 'jpkcom-simple-lang' )
			);

		}

		$keys_valid = jpkcom_simplelang_ability_validate_input_keys(
			$normalised,
			JPKCOM_SIMPLELANG_ABILITY_INPUT_KEYS['jpkcom-simple-lang/check-translation-sets']
		);

		if ( $keys_valid instanceof WP_Error ) {

			return $keys_valid;

		}

		$per_page = JPKCOM_SIMPLELANG_ABILITY_PER_PAGE_DEFAULT;

		if ( isset( $normalised['per_page'] ) && is_numeric( value: $normalised['per_page'] ) ) {

			$per_page = max( 1, min( JPKCOM_SIMPLELANG_ABILITY_PER_PAGE_MAX, (int) $normalised['per_page'] ) );

		}

		$page = 1;

		if ( isset( $normalised['page'] ) && is_numeric( value: $normalised['page'] ) ) {

			$page = max( 1, (int) $normalised['page'] );

		}

		$types     = jpkcom_simplelang_ability_enabled_post_types();
		$installed = get_available_languages();
		$installed = is_array( value: $installed ) ? $installed : [];
		$default   = jpkcom_simplelang_get_site_default_locale();
		$default_tag = jpkcom_simplelang_get_bcp47( $default );

		$collected = jpkcom_simplelang_ability_build_sets( $types );

		$with_issues = [];
		$sets_total  = 0;

		foreach ( $collected['sets'] as $members ) {

			++$sets_total;

			$report = jpkcom_simplelang_ability_inspect_set( $members, $default_tag, $installed );

			if ( $report['has_issues'] ) {

				unset( $report['has_issues'] );

				$with_issues[] = $report;

			}

		}

		$total  = count( $with_issues );
		$offset = ( $page - 1 ) * $per_page;

		return [
			'site_default'     => jpkcom_simplelang_ability_describe_locale( $default, $installed ),
			'sets_total'       => $sets_total,
			'total'            => $total,
			'page'             => $page,
			'per_page'         => $per_page,
			'total_pages'      => $per_page > 0 ? (int) ceil( $total / $per_page ) : 0,
			// Reported rather than applied quietly: an answer that stopped early
			// while looking complete is worse than one that says it stopped.
			'scan_truncated'   => $collected['truncated'],
			'scan_limit'       => JPKCOM_SIMPLELANG_ABILITY_SCAN_LIMIT,
			'language'         => determine_locale(),
			'sets'             => array_slice( $with_issues, $offset, $per_page ),
		];

	}

}


if ( ! function_exists( function: 'jpkcom_simplelang_ability_permission_list_languages' ) ) {

	/**
	 * Permission callback for list-languages.
	 *
	 * @since 1.3.0
	 *
	 * @param mixed $input Validated input, unused.
	 * @return bool True when the current user may run it.
	 */
	function jpkcom_simplelang_ability_permission_list_languages( mixed $input = null ): bool {

		return jpkcom_simplelang_ability_capability( 'jpkcom-simple-lang/list-languages' );

	}

}


if ( ! function_exists( function: 'jpkcom_simplelang_ability_permission_check_sets' ) ) {

	/**
	 * Permission callback for check-translation-sets.
	 *
	 * @since 1.3.0
	 *
	 * @param mixed $input Validated input, unused.
	 * @return bool True when the current user may run it.
	 */
	function jpkcom_simplelang_ability_permission_check_sets( mixed $input = null ): bool {

		return jpkcom_simplelang_ability_capability( 'jpkcom-simple-lang/check-translation-sets' );

	}

}


if ( ! function_exists( function: 'jpkcom_simplelang_ability_list_languages' ) ) {

	/**
	 * Execute callback for jpkcom-simple-lang/list-languages.
	 *
	 * @since 1.3.0
	 *
	 * @param mixed $input Ability input.
	 * @return array<string, mixed>|WP_Error Result.
	 */
	function jpkcom_simplelang_ability_list_languages( mixed $input = null ): array|WP_Error {

		return jpkcom_simplelang_ability_boundary(
			static fn(): array|WP_Error => jpkcom_simplelang_ability_list_languages_inner( $input ),
			'jpkcom-simple-lang/list-languages'
		);

	}

}


if ( ! function_exists( function: 'jpkcom_simplelang_ability_check_sets' ) ) {

	/**
	 * Execute callback for jpkcom-simple-lang/check-translation-sets.
	 *
	 * @since 1.3.0
	 *
	 * @param mixed $input Ability input.
	 * @return array<string, mixed>|WP_Error Result.
	 */
	function jpkcom_simplelang_ability_check_sets( mixed $input = null ): array|WP_Error {

		return jpkcom_simplelang_ability_boundary(
			static fn(): array|WP_Error => jpkcom_simplelang_ability_check_sets_inner( $input ),
			'jpkcom-simple-lang/check-translation-sets'
		);

	}

}


if ( ! function_exists( function: 'jpkcom_simplelang_get_ability_definitions' ) ) {

	/**
	 * Build the registration arguments for both abilities.
	 *
	 * Reads no WordPress state and touches no registry, which is what lets the CI
	 * harness assert the shape of these arrays without a WordPress installation.
	 *
	 * @since 1.3.0
	 *
	 * @return array<string, array<string, mixed>> Ability name => registration args.
	 */
	function jpkcom_simplelang_get_ability_definitions(): array {

		$locale_schema = [
			'type'       => 'object',
			'properties' => [
				'locale'    => [ 'type' => 'string', 'description' => __( 'WordPress locale, for example de_DE or de_DE_formal. Empty means the post carries no language of its own.', 'jpkcom-simple-lang' ) ],
				'bcp47'     => [ 'type' => 'string', 'description' => __( 'The BCP 47 tag this locale produces in markup. de_DE and de_DE_formal both produce de-DE, which is why two such versions collide in one hreflang set.', 'jpkcom-simple-lang' ) ],
				'name'      => [ 'type' => 'string', 'description' => __( 'Native language name, where WordPress knows one.', 'jpkcom-simple-lang' ) ],
				'installed' => [ 'type' => 'boolean', 'description' => __( 'Whether the language pack is installed. When it is not, the front end deliberately does not switch: the page keeps the site language while its meta still claims another one.', 'jpkcom-simple-lang' ) ],
			],
		];

		return [

			'jpkcom-simple-lang/list-languages' => [
				'label'       => __( 'List the languages this site uses', 'jpkcom-simple-lang' ),
				'description' => __( 'Returns which post types have language selection enabled, the site default language, the installed language packs, and which locales published posts actually carry, with counts. Call this before jpkcom-simple-lang/check-translation-sets so locales and their BCP 47 tags never have to be guessed.', 'jpkcom-simple-lang' ),
				'category'    => JPKCOM_SIMPLELANG_ABILITY_CATEGORY,

				'input_schema' => [
					'type'    => 'object',
					// Top level, deliberately: normalize_input() substitutes this value
					// when the input is exactly null and nothing else does. An object
					// rather than [], because the MCP adapter publishes the schema
					// verbatim and an array violates type: object.
					'default' => (object) array(),
					// NO `properties` key, and additionalProperties => false. An empty
					// stdClass here is an anonymous fatal - core does an array offset on
					// it - and an empty array is invalid JSON Schema. Omitting the key is
					// the only combination that yields a clean WP_Error.
					'additionalProperties' => false,
				],

				'output_schema' => [
					'type'       => 'object',
					'properties' => [
						'enabled_post_types'     => [ 'type' => 'array', 'items' => [ 'type' => 'string' ], 'description' => __( 'Post types with language selection enabled. Everything else this plugin does is scoped to these.', 'jpkcom-simple-lang' ) ],
						'site_default'           => $locale_schema + [ 'description' => __( 'The site default language, read from WPLANG rather than from get_locale(), so a temporary locale switch cannot distort it.', 'jpkcom-simple-lang' ) ],
						'installed_languages'    => [ 'type' => 'array', 'items' => [ 'type' => 'string' ], 'description' => __( 'Language packs installed on this site. en_US is always available and is not listed here.', 'jpkcom-simple-lang' ) ],
						'languages_in_use'       => [
							'type'        => 'array',
							'description' => __( 'Locales that published posts carry, most used first. A locale with installed=false is the case worth acting on: those posts claim a language the front end cannot actually serve.', 'jpkcom-simple-lang' ),
							'items'       => [
								'type'       => 'object',
								'properties' => $locale_schema['properties'] + [
									'posts' => [ 'type' => 'integer', 'description' => __( 'Published posts carrying this locale.', 'jpkcom-simple-lang' ) ],
								],
							],
						],
						'posts_total'            => [ 'type' => 'integer', 'description' => __( 'Published posts in the enabled post types altogether.', 'jpkcom-simple-lang' ) ],
						'posts_without_language' => [ 'type' => 'integer', 'description' => __( 'Published posts with no language of their own. This is not a gap: they are served in the site default language, and hreflang reads them as that language too.', 'jpkcom-simple-lang' ) ],
						'language'               => [ 'type' => 'string', 'description' => __( 'Locale this answer was read in.', 'jpkcom-simple-lang' ) ],
					],
				],

				'execute_callback'    => 'jpkcom_simplelang_ability_list_languages',
				'permission_callback' => 'jpkcom_simplelang_ability_permission_list_languages',
				'meta'                => jpkcom_simplelang_ability_meta( 'jpkcom-simple-lang/list-languages' ),
			],

			'jpkcom-simple-lang/check-translation-sets' => [
				'label'       => __( 'Check the translation sets for incomplete hreflang', 'jpkcom-simple-lang' ),
				'description' => __( 'Returns the translation sets whose hreflang annotation comes out incomplete, and why. The emitter keeps one entry per BCP 47 tag, so two versions resolving to the same tag - two de_DE pages, or de_DE together with de_DE_formal - produce a single entry and the other URL is annotated nowhere. That is deliberate, because a contradictory pair would be worse, but nothing in the admin says a page was dropped. This ability is what makes it visible. Also reports sets with no x-default, links to unpublished or deleted posts, one-way links, and members whose language pack is missing.', 'jpkcom-simple-lang' ),
				'category'    => JPKCOM_SIMPLELANG_ABILITY_CATEGORY,

				'input_schema' => [
					'type'    => 'object',
					// See the note on list-languages.
					'default' => (object) array(),
					'properties' => [
						'page'     => [
							'type'        => 'integer',
							'description' => __( 'Page number, starting at 1.', 'jpkcom-simple-lang' ),
							'minimum'     => 1,
							'default'     => 1,
						],
						'per_page' => [
							'type'        => 'integer',
							'description' => __( 'Sets per page.', 'jpkcom-simple-lang' ),
							'minimum'     => 1,
							'maximum'     => JPKCOM_SIMPLELANG_ABILITY_PER_PAGE_MAX,
							'default'     => JPKCOM_SIMPLELANG_ABILITY_PER_PAGE_DEFAULT,
						],
					],
				],

				'output_schema' => [
					'type'       => 'object',
					'properties' => [
						'site_default'   => $locale_schema + [ 'description' => __( 'The site default language. A set with no version in this language gets no x-default.', 'jpkcom-simple-lang' ) ],
						'sets_total'     => [ 'type' => 'integer', 'description' => __( 'How many translation sets exist altogether, so "total" can be read as a proportion.', 'jpkcom-simple-lang' ) ],
						'total'          => [ 'type' => 'integer', 'description' => __( 'How many of those sets have at least one finding.', 'jpkcom-simple-lang' ) ],
						'page'           => [ 'type' => 'integer', 'description' => __( 'The page that was returned.', 'jpkcom-simple-lang' ) ],
						'per_page'       => [ 'type' => 'integer', 'description' => __( 'The page size that was applied after clamping.', 'jpkcom-simple-lang' ) ],
						'total_pages'    => [ 'type' => 'integer', 'description' => __( 'Number of pages available.', 'jpkcom-simple-lang' ) ],
						'scan_truncated' => [ 'type' => 'boolean', 'description' => __( 'True when this site has more linked posts than one call examines, so the answer describes only the part that was read. Reported rather than applied quietly, because an answer that stopped early while looking complete is the worse failure.', 'jpkcom-simple-lang' ) ],
						'scan_limit'     => [ 'type' => 'integer', 'description' => __( 'How many linked posts one call examines.', 'jpkcom-simple-lang' ) ],
						'language'       => [ 'type' => 'string', 'description' => __( 'Locale this answer was read in.', 'jpkcom-simple-lang' ) ],
						'sets'           => [
							'type'        => 'array',
							'description' => __( 'The sets with findings, on the requested page.', 'jpkcom-simple-lang' ),
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'members'      => [ 'type' => 'array', 'items' => [ 'type' => 'integer' ], 'description' => __( 'Every post ID in this set, including ones that do not appear in the output.', 'jpkcom-simple-lang' ) ],
									'tags_emitted' => [ 'type' => 'integer', 'description' => __( 'How many hreflang entries this set actually produces. Compare with the number of published members: a smaller number means versions were dropped.', 'jpkcom-simple-lang' ) ],
									'versions'     => [
										'type'        => 'array',
										'description' => __( 'The published members and the tag each resolves to.', 'jpkcom-simple-lang' ),
										'items'       => [
											'type'       => 'object',
											'properties' => [
												'id'           => [ 'type' => 'integer', 'description' => __( 'Post ID.', 'jpkcom-simple-lang' ) ],
												'title'        => [ 'type' => 'string', 'description' => __( 'Post title.', 'jpkcom-simple-lang' ) ],
												'locale'       => [ 'type' => 'string', 'description' => __( 'Locale stored on the post, empty when it carries none.', 'jpkcom-simple-lang' ) ],
												'uses_default' => [ 'type' => 'boolean', 'description' => __( 'True when the post carries no language of its own and is therefore read as the site default - which is how a version with no language collides with an explicit one in that language.', 'jpkcom-simple-lang' ) ],
												'bcp47'        => [ 'type' => 'string', 'description' => __( 'The tag this version resolves to.', 'jpkcom-simple-lang' ) ],
											],
										],
									],
									'issues'       => [
										'type'        => 'object',
										'description' => __( 'What is wrong with this set. An "unpublished_member" entry alone does not put a set in this report - a draft translation is ordinary work in progress, and flagging every one of them would make the report unreadable; it is listed when the set qualifies on something else, because it explains a tag count below the member count. "tag_collision" lists the tag and the post IDs sharing it, with emitted=1 because only one of them is annotated; which one survives depends on the order the posts come back in, so no winner is named. "no_x_default" means no member carries the site default language. "unpublished_member" and "dangling_member" list linked posts that never appear because they are not published or no longer exist. "one_way_link" lists members that do not link back, which the editor sync would normally prevent. "language_pack_missing" lists members whose locale is no longer installed.', 'jpkcom-simple-lang' ),
									],
								],
							],
						],
					],
				],

				'execute_callback'    => 'jpkcom_simplelang_ability_check_sets',
				'permission_callback' => 'jpkcom_simplelang_ability_permission_check_sets',
				'meta'                => jpkcom_simplelang_ability_meta( 'jpkcom-simple-lang/check-translation-sets' ),
			],

		];

	}

}


if ( ! function_exists( function: 'jpkcom_simplelang_register_ability_category' ) ) {

	/**
	 * Register the shared category, unless a sibling plugin already did.
	 *
	 * @since 1.3.0
	 *
	 * @return void
	 */
	function jpkcom_simplelang_register_ability_category(): void {

		if ( ! jpkcom_simplelang_abilities_enabled() ) {

			return;

		}

		if ( function_exists( function: 'wp_has_ability_category' )
			&& wp_has_ability_category( JPKCOM_SIMPLELANG_ABILITY_CATEGORY ) ) {

			return;

		}

		$category = wp_register_ability_category(
			JPKCOM_SIMPLELANG_ABILITY_CATEGORY,
			[
				'label'       => __( 'JPKCom Content', 'jpkcom-simple-lang' ),
				'description' => __( 'Read-only access to content managed by the JPKCom content plugins.', 'jpkcom-simple-lang' ),
			]
		);

		if ( $category === null ) {

			jpkcom_simplelang_ability_log( 'ability category registration returned null' );

		}

	}

}


if ( ! function_exists( function: 'jpkcom_simplelang_register_abilities' ) ) {

	/**
	 * Register both abilities.
	 *
	 * wp_register_ability() returns null on EVERY failure path and reports only
	 * through _doing_it_wrong(), which is silent in production - and so is the
	 * debug log without WP_DEBUG.
	 *
	 * @since 1.3.0
	 *
	 * @return void
	 */
	function jpkcom_simplelang_register_abilities(): void {

		if ( ! jpkcom_simplelang_abilities_enabled() ) {

			return;

		}

		foreach ( jpkcom_simplelang_get_ability_definitions() as $name => $args ) {

			if ( wp_register_ability( $name, $args ) === null ) {

				jpkcom_simplelang_ability_log( 'registration returned null for ' . $name );

			}

		}

	}

}


add_action( 'wp_abilities_api_categories_init', 'jpkcom_simplelang_register_ability_category' );
add_action( 'wp_abilities_api_init', 'jpkcom_simplelang_register_abilities' );
