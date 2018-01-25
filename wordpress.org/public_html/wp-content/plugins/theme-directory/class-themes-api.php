<?php

/**
 * The WordPress.org Themes API.
 *
 * Class Themes_API
 */
class Themes_API {

	/**
	 * Array of request parameters.
	 *
	 * @var array
	 */
	public $request = array();

	/**
	 * Array of parameters for WP_Query.
	 *
	 * @var array
	 */
	public $query = array();

	/**
	 * Holds the result of a WP_Query query.
	 *
	 * @var array
	 */
	public $result = array();

	/**
	 * API response.
	 *
	 * @var null|array|object
	 */
	public $response = null;

	/**
	 * Field defaults, overridden by individual sections.
	 *
	 * @var array
	 */
	public $fields = array(
		'description'        => false,
		'downloaded'         => false,
		'downloadlink'       => false,
		'last_updated'       => false,
		'parent'             => false,
		'rating'             => false,
		'ratings'            => false,
		'screenshot_count'   => false,
		'screenshot_url'     => true,
		'screenshots'        => false,
		'sections'           => false,
		'tags'               => false,
		'template'           => false,
		'versions'           => false,
		'theme_url'          => false,
		'extended_author'    => false,
		'photon_screenshots' => false,
		'active_installs'    => false,
	);

	/**
	 * Name of the cache group.
	 *
	 * @var string
	 */
	private $cache_group = 'theme-info';

	/**
	 * The amount of time to keep information cached.
	 *
	 * @var int
	 */
	private $cache_life = 600; // 10 minutes.


	/**
	 * Constructor.
	 *
	 * @param string $action
	 * @param array $request
	 */
	public function __construct( $action = '', $request = array() ) {
		$this->request = (object) $request;

		// The locale we should use is specified by the request
		add_filter( 'locale', array( $this, 'filter_locale' ) );

		/*
		 * Supported actions:
		 * query_themes, theme_information, hot_tags, feature_list.
		 */
		$valid_actions = array( 'query_themes', 'theme_information', 'hot_tags', 'feature_list', 'get_commercial_shops' );
		if ( in_array( $action, $valid_actions, true ) && method_exists( $this, $action ) ) {
			$this->$action();
		} else {
			// Assume a friendly wp hacker :)
			if ( 'POST' != strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
				wp_die( 'Action not implemented. <a href="https://codex.wordpress.org/WordPress.org_API">API Docs</a>' );
			} else {
				$this->response = (object) array( 'error' => 'Action not implemented' );
			}
		}
	}

	/**
	 * Filter get_locale() to use the locale which is specified in the request.
	 */
	function filter_locale( $locale ) {
		return isset( $this->request->locale ) ? $this->request->locale : $locale;
	}

	/**
	 * Prepares result.
	 *
	 * @return string|void
	 */
	public function get_result() {
		if ( defined( 'JSON_RESPONSE' ) && JSON_RESPONSE ) {
			return wp_json_encode( $this->response );
		} else {
			return serialize( $this->response );
		}
	}

	/* Action functions */

	/**
	 * Gets theme tags, ordered by how popular they are.
	 */
	public function hot_tags() {
		$cache_key = sanitize_key( __METHOD__ );
		if ( false === ( $this->response = wp_cache_get( $cache_key, $this->cache_group ) ) ) {
			$tags = get_tags( array(
				'orderby'    => 'count',
				'order'      => 'DESC',
				'hide_empty' => false,
			) );

			// Format in the API representation.
			foreach ( $tags as $tag ) {
				$this->response[ $tag->slug ] = array(
					'name'  => $tag->name,
					'slug'  => $tag->slug,
					'count' => $tag->count,
				);
			}

			wp_cache_add( $cache_key, $this->response, $this->cache_group, $this->cache_life );
		}

		if ( ! empty( $this->request->number ) ) {
			$this->response = array_slice( $this->response, 0, $this->request->number );
		}
	}

	/**
	 * Gets a list of valid "features" aka theme tags.
	 */
	public function feature_list() {
		$tags = array(
			__( 'Colors' )   => array(
				'black'  => __( 'Black' ),
				'blue'   => __( 'Blue' ),
				'brown'  => __( 'Brown' ),
				'gray'   => __( 'Gray' ),
				'green'  => __( 'Green' ),
				'orange' => __( 'Orange' ),
				'pink'   => __( 'Pink' ),
				'purple' => __( 'Purple' ),
				'red'    => __( 'Red' ),
				'silver' => __( 'Silver' ),
				'tan'    => __( 'Tan' ),
				'white'  => __( 'White' ),
				'yellow' => __( 'Yellow' ),
				'dark'   => __( 'Dark' ),
				'light'  => __( 'Light' ),
			),
			__( 'Columns' )  => array(
				'one-column'    => __( 'One Column' ),
				'two-columns'   => __( 'Two Columns' ),
				'three-columns' => __( 'Three Columns' ),
				'four-columns'  => __( 'Four Columns' ),
				'left-sidebar'  => __( 'Left Sidebar' ),
				'right-sidebar' => __( 'Right Sidebar' ),
			),
			__( 'Layout' )   => array(
				'fixed-layout'      => __( 'Fixed Layout' ),
				'fluid-layout'      => __( 'Fluid Layout' ),
				'responsive-layout' => __( 'Responsive Layout' ),
			),
			__( 'Features' ) => array(
				'accessibility-ready'   => __( 'Accessibility Ready' ),
				'blavatar'              => __( 'Blavatar' ),
				'buddypress'            => __( 'BuddyPress' ),
				'custom-background'     => __( 'Custom Background' ),
				'custom-colors'         => __( 'Custom Colors' ),
				'custom-header'         => __( 'Custom Header' ),
				'custom-menu'           => __( 'Custom Menu' ),
				'editor-style'          => __( 'Editor Style' ),
				'featured-image-header' => __( 'Featured Image Header' ),
				'featured-images'       => __( 'Featured Images' ),
				'flexible-header'       => __( 'Flexible Header' ),
				'front-page-post-form'  => __( 'Front Page Posting' ),
				'full-width-template'   => __( 'Full Width Template' ),
				'microformats'          => __( 'Microformats' ),
				'post-formats'          => __( 'Post Formats' ),
				'rtl-language-support'  => __( 'RTL Language Support' ),
				'sticky-post'           => __( 'Sticky Post' ),
				'theme-options'         => __( 'Theme Options' ),
				'threaded-comments'     => __( 'Threaded Comments' ),
				'translation-ready'     => __( 'Translation Ready' ),
			),
			__( 'Subject' )  => array(
				'holiday'       => __( 'Holiday' ),
				'photoblogging' => __( 'Photoblogging' ),
				'seasonal'      => __( 'Seasonal' ),
			)
		);

		// Get version from user agent since it's not explicitly sent to feature_list requests.
		if ( preg_match( '|WordPress/([^;]+)|', $_SERVER['HTTP_USER_AGENT'], $matches ) ) {
			$wp_version = $matches[1];
		}

		// Pre 3.8 installs get width tags instead of layout tags.
		if ( isset( $wp_version ) && version_compare( $wp_version, '3.7.999', '<' ) ) {
			unset( $tags[ __( 'Layout' ) ] );
			$tags[ __( 'Width' ) ] = array(
				'fixed-width'    => __( 'Fixed Width' ),
				'flexible-width' => __( 'Flexible Width' ),
			);

			if ( array_key_exists( 'accessibility-ready', $tags[ __( 'Features' ) ] ) ) {
				unset( $tags[ __( 'Features' ) ]['accessibility-ready'] );
			}
		}

		if ( ! isset( $wp_version ) || version_compare( $wp_version, '3.9-beta', '>' ) ) {
			$tags[ __( 'Layout' ) ] = array_merge( $tags[ __( 'Layout' ) ], $tags[ __( 'Columns' ) ] );
			unset( $tags[ __( 'Columns' ) ] );
		}

		// See https://core.trac.wordpress.org/ticket/33407.
		if ( ! isset( $wp_version ) || version_compare( $wp_version, '4.6-alpha', '>' ) ) {
			unset( $tags[ __( 'Colors' ) ] );
			$tags[ __( 'Layout' ) ] = array(
				'grid-layout'   => __( 'Grid Layout' ),
				'one-column'    => __( 'One Column' ),
				'two-columns'   => __( 'Two Columns' ),
				'three-columns' => __( 'Three Columns' ),
				'four-columns'  => __( 'Four Columns' ),
				'left-sidebar'  => __( 'Left Sidebar' ),
				'right-sidebar' => __( 'Right Sidebar' ),
			);

			unset( $tags[ __( 'Features' ) ]['blavatar'] );
			$tags[ __( 'Features' ) ]['footer-widgets'] = __( 'Footer Widgets' );
			asort( $tags[ __( 'Features' ) ] ); // To move footer-widgets to the right place.

			$tags[ __( 'Subject' ) ] = array(
				'blog'           => __( 'Blog' ),
				'e-commerce'     => __( 'E-Commerce' ),
				'education'      => __( 'Education' ),
				'entertainment'  => __( 'Entertainment' ),
				'food-and-drink' => __( 'Food & Drink' ),
				'holiday'        => __( 'Holiday' ),
				'news'           => __( 'News' ),
				'photography'    => __( 'Photography' ),
				'portfolio'      => __( 'Portfolio' ),
			);
		}

		// Only return tag slugs, to stay compatible with bbpress-version of Themes API.
		foreach ( $tags as $title => $group ) {
			$tags[ $title ] = array_keys( $group );
		}

		$this->response = $tags;
	}

	/**
	 * Retrieve specific information about a theme.
	 */
	public function theme_information() {
		global $post;
		// Theme slug to identify theme.
		if ( empty( $this->request->slug ) ) {
			$this->response = (object) array( 'error' => 'Slug not provided' );
			return;
		}

		// If there is a cached result, return that.
		$cache_key = sanitize_key( __METHOD__ . ':' . get_locale() . ':' . md5( serialize( $this->request ) ) );
		if ( false !== ( $this->response = wp_cache_get( $cache_key, $this->cache_group ) ) && empty( $this->request->cache_buster ) ) {
			return;
		}

		// Set which fields wanted by default:
		$defaults = array(
			'sections'     => true,
			'rating'       => true,
			'downloaded'   => true,
			'downloadlink' => true,
			'last_updated' => true,
			'homepage'     => true,
			'tags'         => true,
			'template'     => true,
		);

		if ( empty( $this->request->fields ) ) {
			$this->request->fields = array();
		}
		$this->fields = array_merge( $this->fields, $defaults, (array) $this->request->fields );

		if ( !empty( $post ) && 'repopackage' == $post->post_type && $this->request->slug === $post->post_name ) {
			$this->response = $this->fill_theme( $post );
		} else {
			// get_post_by_slug()
			$themes = get_posts( array(
				'name'      => $this->request->slug,
				'post_type' => 'repopackage',
			) );

			if ( ! empty( $themes ) ) {
				$this->response = $this->fill_theme( $themes[0] );
			}
		}

		wp_cache_set( $cache_key, $this->response, $this->cache_group, $this->cache_life );
	}

	/**
	 * Get a list of themes.
	 *
	 *  Object:
	 *      info (array)
	 *          page (int)
	 *          pages (int)
	 *          results (int)
	 *      themes (array)
	 *          name
	 *          slug
	 *          version
	 *          author
	 *          rating
	 *          num_ratings
	 *          homepage
	 *          description
	 *          preview_url
	 *          download_url
	 */
	public function query_themes() {
		global $wp_query;
		// If there is a cached result, return that.
		$cache_key = sanitize_key( __METHOD__ . ':' . get_locale() . ':' . md5( serialize( $this->request ) ) );
		if ( false !== ( $this->response = wp_cache_get( $cache_key, $this->cache_group ) ) && empty( $this->request->cache_buster ) ) {
			return;
		}

		if ( isset( $wp_query ) && $wp_query->query_vars ) {
			$this->result = $wp_query;
		} else {
			$this->result = $this->perform_wp_query();
		}

		if ( empty( $this->request->fields ) ) {
			$this->request->fields = array();
		}

		// Basic information about the request.
		$this->response = (object) array(
			'info'   => array(),
			'themes' => array(),
		);

		// Basic information about the request.
		$this->response->info = array(
			'page'    => max( 1, $this->result->query_vars['paged'] ),
			'pages'   => max( 1, $this->result->max_num_pages ),
			'results' => (int) $this->result->found_posts,
		);

		// Set which fields wanted by default:
		$defaults = array(
			'description' => true,
			'rating'      => true,
			'homepage'    => true,
			'template'    => true,
		);


		$this->fields = array_merge( $this->fields, $defaults, (array) $this->request->fields );

		// Fill up the themes lists.
		foreach ( (array) $this->result->posts as $theme ) {
			$this->response->themes[] = $this->fill_theme( $theme );
		}

		wp_cache_set( $cache_key, $this->response, $this->cache_group, $this->cache_life );
	}

	public function perform_wp_query() {
		$this->query = array(
			'post_type'   => 'repopackage',
			'post_status' => 'publish',
		);
		if ( isset( $this->request->page ) ) {
			$this->query['paged'] = (int)$this->request->page;
		}
		if ( isset( $this->request->per_page ) ) {
			$this->query['posts_per_page'] = $this->request->per_page;
		}

		// Views
		if ( ! empty( $this->request->browse ) ) {
			$this->query['browse'] = $this->request->browse;

			if ( 'featured' == $this->request->browse ) {
				$this->cache_life = HOUR_IN_SECONDS;
			} elseif ( 'favorites' == $this->request->browse ) {
				$this->query['favorites_user'] = $this->request->user;
			}

		}

		// Tags
		if ( ! empty( $this->request->tag ) ) {
			if ( ! is_array( $this->request->tag ) ) {
				$this->request->tag = explode( ',', $this->request->tag );
			}
			$this->request->tag = array_unique( $this->request->tag );

			// Replace updated tags.
			$updated_tags = array(
				'fixed-width'    => 'fixed-layout',
				'flexible-width' => 'fluid-layout',
			);
			foreach ( $updated_tags as $old => $new ) {
				if ( $key = array_search( $old, $this->request->tag ) ) {
					$this->request->tag[ $key ] = $new;
				}
			}

			$this->query['tax_query'] = array(
				array(
					'taxonomy' => 'post_tag',
					'field'    => 'slug',
					'terms'    => $this->request->tag,
					'operator' => 'AND',
				),
			);
		}

		// Search
		if ( ! empty( $this->request->search ) ) {
			$this->query['s'] = $this->request->search;
		}

		// Direct theme
		if ( ! empty( $this->request->theme ) ) {
			$this->query['name'] = $this->request->theme;

			add_filter( 'parse_query', array( $this, 'direct_theme_query' ) );
		}

		// Author
		if ( ! empty( $this->request->author ) ) {
			$this->query['author_name'] = $this->request->author;
		}

		// Query
		return new WP_Query( $this->query );
	}

	/**
	 * Get a list of commercial theme shops.
	 *
	 *  Object:
	 *      shops (array)
	 *          (object)
	 *              shop (string)
	 *              slug (string)
	 *              haiku (string)
	 *              image (string)
	 *              url (string)
	 */
	function get_commercial_shops() {
		if ( false !== ( $this->response = wp_cache_get( 'commercial_theme_shops', $this->cache_group ) ) && empty( $this->request->cache_buster ) ) {
			return;
		}

		$this->response = (object) array(
			'shops' => array()
		);

		$theme_shops = new WP_Query( array(
			'post_type'      => 'theme_shop',
			'posts_per_page' => -1,
			'orderby'        => 'rand(' . gmdate('YmdH') . ')',
		) );

		while ( $theme_shops->have_posts() ) {
			$theme_shops->the_post();

			$this->response->shops[] = (object) array(
				'shop'  => get_the_title(),
				'slug'  => sanitize_title( get_the_title() ),
				'haiku' => get_the_content(),
				'image' => post_custom( 'image_url' ) ?: sprintf( '//s0.wp.com/mshots/v1/%s?w=572', urlencode( post_custom( 'url' ) ) ),
				'url'   => post_custom( 'url' ),
			);
		}

		wp_cache_set( 'commercial_theme_shops', $this->response, $this->cache_group, 15 * 60 );
	}

	/**
	 * Fill it up with information.
	 *
	 * @param  WP_Theme $theme
	 *
	 * @return object
	 */
	public function fill_theme( $theme ) {
		// If there is a cached theme for the current locale, return that.
		$cache_key = sanitize_key( implode( '-', array( $theme->post_name, md5( serialize( $this->fields ) ), get_locale() ) ) );
		if ( false !== ( $phil = wp_cache_get( $cache_key, $this->cache_group ) ) && empty( $this->request->cache_buster ) ) {
			return $phil;
		}

		global $wpdb;

		$phil = (object) array(
			'name' => $theme->post_title,
			'slug' => $theme->post_name,
		);

		$repo_package  = new WPORG_Themes_Repo_Package( $theme->ID );
		$phil->version = $repo_package->latest_version();

		$phil->preview_url = "https://wp-themes.com/{$theme->post_name}";

		$author = get_user_by( 'id', $theme->post_author );

		if ( $this->fields['extended_author'] ) {
			$phil->author = (object) array(
				'user_nicename' => $author->user_nicename,
				'display_name'  => $author->display_name,
			);
		} else {
			$phil->author = $author->user_nicename;
		}

		if ( $this->fields['screenshot_url'] || $this->fields['screenshot_count'] || $this->fields['screenshots'] ) {

			// TODO this whole thing will need refactoring for multiple screenshots, if and when.
			$screenshot_base = "https://wp-themes.com/wp-content/themes/{$theme->post_name}/screenshot";
			if ( $this->fields['screenshot_url'] ) {
				$screenshots = get_post_meta( $theme->ID, '_screenshot', true );

				if ( $this->fields['photon_screenshots'] ) {
					$phil->screenshot_url = sprintf( 'https://i0.wp.com/themes.svn.wordpress.org/%1$s/%2$s/%3$s', $phil->slug, $phil->version, $screenshots[ $phil->version ] );
				} else {
					$phil->screenshot_url = sprintf( '//ts.w.org/wp-content/themes/%1$s/%2$s?ver=%3$s', $phil->slug, $screenshots[ $phil->version ], $phil->version );
				}
			}

			if ( $this->fields['screenshot_count'] || $this->fields['screenshots'] ) {
				$screenshot_count = 1; // TODO
				if ( $screenshot_count < 1 ) {
					$screenshot_count = 1;
				}

				if ( $this->fields['screenshot_count'] ) {
					$phil->screenshot_count = $screenshot_count;
				}

				if ( $this->fields['screenshots'] ) {
					$phil->screenshots = array( $screenshot_base . '.png' );
					for ( $i = 2; $i <= $screenshot_count; $i ++ ) {
						$phil->screenshots[] = $screenshot_count . '-' . $i . '.png';
					}
				}
			}
		}

		if ( $this->fields['theme_url'] ) {
			$phil->theme_url = wporg_themes_get_version_meta( $theme->ID, '_theme_url', $phil->version );
		}

		if ( $this->fields['ratings'] ) {
			// Amount of reviews for each rating level.
			$phil->ratings = \WPORG_Ratings::get_rating_counts( 'theme', $theme->post_name );
		}

		if ( $this->fields['rating'] ) {
			// Return a % rating; Rating range: 0~5.
			$phil->rating = \WPORG_Ratings::get_avg_rating( 'theme', $theme->post_name ) * 20;
			$phil->num_ratings = \WPORG_Ratings::get_rating_count( 'theme', $theme->post_name );
		}

		if ( $this->fields['downloaded'] ) {
			$key = "theme-down:$theme->post_name";

			if ( false === ( $phil->downloaded = wp_cache_get( $key, $this->cache_group ) ) ) {
				$phil->downloaded = (int) $wpdb->get_var( $wpdb->prepare( "SELECT SUM( downloads ) FROM bb_themes_stats WHERE slug = %s", $theme->post_name ) );
				wp_cache_set( $key, $phil->downloaded, $this->cache_group, $this->cache_life );
			}
		}

		if ( $this->fields['active_installs'] ) {
			$phil->active_installs = (int) get_post_meta( $theme->ID, '_active_installs', true );

			// 0, 1m+, rounded to nearest significant digit
			if ( $phil->active_installs < 10 ) {
				$phil->active_installs = 0;
			} elseif ( $phil->active_installs >= 3000000 ) {
				$phil->active_installs = 3000000;
			} else {
				$phil->active_installs = strval( $phil->active_installs )[0] * pow( 10, floor( log10( $phil->active_installs ) ) );
			}
		}

		if ( $this->fields['last_updated'] ) {
			$phil->last_updated = get_post_modified_time( 'Y-m-d', null, $theme->ID, true );
		}

		if ( $this->fields['homepage'] ) {
			$phil->homepage = "https://wordpress.org/themes/{$theme->post_name}/";
		}

		if ( $this->fields['description'] || $this->fields['sections'] ) {
			if ( $this->fields['sections'] ) {
				// Client wants Sections.
				$phil->sections = array();
				if ( preg_match_all( '|--theme-data-(.+?)-->(.*?)<!|ims', $theme->post_content, $pieces ) ) {
					for ( $i = 0; $i < count( $pieces[1] ); $i ++ ) {
						$phil->sections[ $pieces[1][ $i ] ] = trim( $pieces[2][ $i ] );
					}
				} else {
					// Doesn't have any sections:
					$phil->sections['description'] = $this->fix_mangled_description( trim( $theme->post_content ) );
				}
			} else {
				// No sections, Ok, Just return the Description (First field?)
				if ( strpos( $theme->post_content, '<!--' ) ) {
					$phil->description = trim( substr( $theme->post_content, 0, strpos( $theme->post_content, '<!--' ) ) );
				} else {
					$phil->description = trim( $theme->post_content );
				}
				$phil->description = $this->fix_mangled_description( $phil->description );
			}
		}

		if ( $this->fields['downloadlink'] ) {
			$phil->download_link = $this->create_download_link( $theme, $phil->version );
		}

		if ( $this->fields['tags'] ) {
			$phil->tags = array();
			foreach ( wp_get_post_tags( $theme->ID ) as $tag ) {
				$phil->tags[ $tag->slug ] = $tag->name;
			}
		}

		if ( $this->fields['template'] || $this->fields['parent'] ) {
			/*
			 * On requests for single themes the post global can be set, returning that theme as the parent
			 * if the current theme is not a child theme.
			 */
			unset( $GLOBALS['post'] );

			$parent = get_post( $theme->post_parent );

			if ( is_a( $parent, 'WP_Post' ) ) {
				if ( $this->fields['template'] ) {
					$phil->template = $parent->post_name;
				}

				if ( $this->fields['parent'] ) {
					$phil->parent = array(
						'slug'     => $parent->post_name,
						'name'     => $parent->post_title,
						'homepage' => "https://wordpress.org/themes/{$parent->post_name}/",
					);
				}
			}
		}

		if ( $this->fields['versions'] ) {
			$phil->versions = array();

			foreach ( array_keys( get_post_meta( $theme->ID, '_status', true ) ) as $version ) {
				$phil->versions[ $version ] = $this->create_download_link( $theme, $version );
			}
		}

		if ( class_exists( 'GlotPress_Translate_Bridge' ) ) {
			$glotpress_project = "wp-themes/{$phil->slug}";

			$phil->name = GlotPress_Translate_Bridge::translate( $phil->name, $glotpress_project );

			if ( isset( $phil->description ) ) {
				$phil->description = GlotPress_Translate_Bridge::translate( $phil->description, $glotpress_project );
			}

			if ( isset( $phil->sections['description'] ) ) {
				$phil->sections['description'] = GlotPress_Translate_Bridge::translate( $phil->sections['description'], $glotpress_project );
			}

		}

		wp_cache_set( $cache_key, $phil, $this->cache_group, $this->cache_life );

		return $phil;
	}

	/* Filter */

	/**
	 * Marks queries for single themes as archive queries.
	 *
	 * When themes are queried directly, namely the `name` parameter is set, WordPress assumes this is a singular view.
	 * If a theme is not published and the user doing the request is not logged in, the query returns empty. In case
	 * the requested theme has a version that is awaiting approval, that would not be a desired outcome.
	 *
	 * @param WP_Query $query
	 *
	 * @return WP_Query
	 */
	public function direct_theme_query( $query ) {
		$query->is_single   = false;
		$query->is_singular = false;

		$query->is_post_type_archive = true;
		$query->is_archive           = true;

		return $query;
	}

	/* Helper functions */

	/**
	 * Creates download link.
	 *
	 * @param  WP_Post $theme
	 * @param  string $version
	 *
	 * @return string
	 */
	private function create_download_link( $theme, $version ) {
		$url  = 'http://downloads.wordpress.org/theme/';
		$file = $theme->post_name . '.' . $version . '.zip';

		$file = preg_replace( '/[^a-z0-9_.-]/i', '', $file );
		$file = preg_replace( '/[.]+/', '.', $file );

		return set_url_scheme( $url . $file );
	}

	/**
	 * Fixes mangled descriptions.
	 *
	 * @param string $description
	 *
	 * @return string
	 */
	private function fix_mangled_description( $description ) {
		$description = str_replace( '&quot;"', '"', $description );
		$description = str_replace( 'href="//', 'href="http://', $description );
		$description = strip_tags( $description );

		return $description;
	}
}