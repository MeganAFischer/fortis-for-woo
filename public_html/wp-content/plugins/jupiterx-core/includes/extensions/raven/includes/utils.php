<?php
/**
 * Adds utils.
 *
 * @package JupiterX_Core\Raven
 * @since 1.0.0
 */

namespace JupiterX_Core\Raven;

defined( 'ABSPATH' ) || die();

use Elementor\Controls_Stack;
use Elementor\Plugin as Elementor;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;

/**
 * Raven utils class.
 *
 * Raven utils handler class is responsible for different utility methods
 * used by Raven.
 *
 * @since 1.0.0
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Utils {

	/**
	 * A list of safe tage for `validate_html_tag` method.
	 */
	const ALLOWED_HTML_WRAPPER_TAGS = [
		'a',
		'article',
		'aside',
		'button',
		'div',
		'footer',
		'h1',
		'h2',
		'h3',
		'h4',
		'h5',
		'h6',
		'header',
		'main',
		'nav',
		'p',
		'section',
		'span',
	];

	/**
	 * Fresh install.
	 *
	 * @since 4.0.0
	 * @var Boolean
	 */
	private static $fresh_install = false;

	/**
	 * List of elementor global colors.
	 *
	 * @since 4.0.0
	 */
	const ELEMENTOR_GLOBAL_COLORS = [
		'primary' => Global_Colors::COLOR_PRIMARY,
		'secondary' => Global_Colors::COLOR_SECONDARY,
		'text' => Global_Colors::COLOR_TEXT,
		'accent' => Global_Colors::COLOR_ACCENT,
	];

	/**
	 * Get the svg directory path.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @param  string $file_name SVG file name.
	 * @return string Directory path.
	 */
	public static function get_svg( $file_name = '' ) {
		if ( empty( $file_name ) ) {
			return $file_name;
		}

		$file_name = basename( $file_name );

		return Plugin::$plugin_path . 'assets/img/' . $file_name . '.svg';
	}

	/**
	 * Generate data-parallax based on params.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @param int $pxs_x          X position.
	 * @param int $pxs_y          Y position.
	 * @param int $pxs_z          Z position.
	 * @param int $pxs_smoothness Smoothness level.
	 *
	 * @return string Data attribute.
	 */
	public static function parallax_scroll( $pxs_x, $pxs_y, $pxs_z, $pxs_smoothness ) {
		$parallax_scroll = [];

		if ( ! empty( $pxs_x ) ) {
			$parallax_scroll[] = '"x":' . $pxs_x;
		}

		if ( ! empty( $pxs_y ) ) {
			$parallax_scroll[] = '"y":' . $pxs_y;
		}

		if ( ! empty( $pxs_z ) ) {
			$parallax_scroll[] = '"z":' . $pxs_z;
		}

		if ( ! empty( $pxs_smoothness ) ) {
			$parallax_scroll[] = '"smoothness":' . $pxs_smoothness;
		}

		if ( empty( $parallax_scroll ) ) {
			return;
		}

		return '{' . implode( ',', $parallax_scroll ) . '}';
	}

	/**
	 * Get site domain.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @return string Site domain.
	 */
	public static function get_site_domain() {
		// @codingStandardsIgnoreStart
		// Get the site domain and get rid of www.
		$sitename = strtolower( sanitize_text_field( $_SERVER['SERVER_NAME'] ) );

		if ( substr( $sitename, 0, 4 ) == 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}

		return $sitename;
		// @codingStandardsIgnoreEnd
	}

	/**
	 * Get WP_Query arguments.
	 *
	 * Retrieving arguments from element settings.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @param array $settings Widget settings.
	 *
	 * @return args Prepared WP_Query arguments.
	 *
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	public static function get_query_args( $settings ) {
		$settings = array_merge(
			[
				'query_post_type' => 'post',
				'query_posts_per_page' => 3,
				'query_orderby' => 'date',
				'query_order' => 'DESC',
				'category' => -1,
			],
			$settings
		);

		$args = [
			'post_type' => $settings['query_post_type'],
			'posts_per_page' => $settings['query_posts_per_page'],
			'orderby' => $settings['query_orderby'],
			'order' => $settings['query_order'],
			'post_status' => 'publish',
			'ignore_sticky_posts' => empty( $settings['query_ignore_sticky_posts'] ) ? 0 : 1,
			'paged' => max( 1, get_query_var( 'paged' ), get_query_var( 'page' ) ),
		];

		// Only use offset on all category state.
		if ( -1 === $settings['category'] && ! empty( $settings['query_offset'] ) ) {
			$args['offset_proper'] = $settings['query_offset'];
		}

		if ( ! empty( $settings['paged'] ) ) {
			$args['paged'] = $settings['paged'];
		}

		if ( ! empty( $settings['query_excludes'] ) ) {
			$current_post_key = array_search( 'current_post', $settings['query_excludes'], true );

			// If current_post is existing in the array values replace it with the current post viewing ID.
			if ( false !== $current_post_key ) {
				$settings['query_excludes'][ $current_post_key ] = get_the_ID();
			}

			$args['post__not_in'] = $settings['query_excludes'];

			if ( ! empty( $settings['query_excludes_ids'] ) && is_array( $settings['query_excludes_ids'] ) ) {
				$args['post__not_in'] = array_merge( $args['post__not_in'], $settings['query_excludes_ids'] );
			}
		}

		if ( ! empty( $settings[ 'query_' . $args['post_type'] . '_includes' ] ) ) {
			$args['post__in'] = $settings[ 'query_' . $args['post_type'] . '_includes' ];
		}

		if ( ! empty( $settings['query_authors'] ) ) {
			$args['author__in'] = $settings['query_authors'];
		}

		$taxonomies = get_object_taxonomies( $args['post_type'], 'names' );

		if ( ! empty( $settings['category'] ) && $settings['category'] > 0 && ! empty( $taxonomies ) ) {
			$args['tax_query'] = [];

			$taxonomies_length = count( $taxonomies );

			for ( $i = 0; $i < $taxonomies_length; $i++ ) {
				$validate = false !== strpos( $taxonomies[ $i ], 'cat' );

				$validate_taxonomy = apply_filters( 'jupitex_raven_valid_sortable_taxonomy', $validate, $taxonomies[ $i ], $settings );

				if ( ! $validate_taxonomy ) {
					continue;
				}

				$args['tax_query'][] = [
					'taxonomy' => $taxonomies[ $i ],
					'field' => 'term_id',
					'terms' => $settings['category'],
				];

				break;
			}
		} elseif ( empty( $settings[ 'query_' . $args['post_type'] . '_includes' ] ) && ! empty( $taxonomies ) ) {
			$args['tax_query'] = [];

			foreach ( $taxonomies as $taxonomy ) {
				$taxonomy_control_id = 'query_' . $taxonomy . '_ids';

				if ( ! empty( $settings[ $taxonomy_control_id ] ) ) {
					$args['tax_query'][] = [
						'taxonomy' => $taxonomy,
						'field' => 'term_id',
						'terms' => $settings[ $taxonomy_control_id ],
					];
				}
			}
		}

		return $args;
	}

	/**
	 * Get responsive class base on settings key.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @param  string $prefix Before class string.
	 * @param  string  $key Settings key.
	 * @param  string $settings Settings stored.
	 *
	 * @return string|void Responsive class.
	 */
	public static function get_responsive_class( $prefix = '', $key = '', $settings = '' ) {
		if ( empty( $prefix ) || empty( $key ) || empty( $settings ) ) {
			return;
		}

		$devices = [
			Controls_Stack::RESPONSIVE_DESKTOP,
			Controls_Stack::RESPONSIVE_TABLET,
			Controls_Stack::RESPONSIVE_MOBILE,
		];

		if ( Elementor::$instance->experiments->is_feature_active( 'additional_custom_breakpoints' ) ) {
			$devices = [ 'desktop' ];

			foreach ( Elementor::$instance->breakpoints->get_active_breakpoints() as $breakpoint ) {
				$devices[] = $breakpoint->get_name();
			}
		}

		$classes = [];

		foreach ( $devices as $device_name ) {
			$temp_key = \Elementor\Controls_Stack::RESPONSIVE_DESKTOP === $device_name ? $key : $key . '_' . $device_name;

			if ( ! isset( $settings[ $temp_key ] ) ) {
				return;
			}

			$device = \Elementor\Controls_Stack::RESPONSIVE_DESKTOP === $device_name ? '' : '-' . $device_name;

			$classes[] = sprintf( $prefix . $settings[ $temp_key ], $device );
		}

		return implode( ' ', $classes );
	}


	/**
	 * Get element settings recursively.
	 *
	 * Retrieve specific element settings by model ID.
	 *
	 * @param  array  $elements Page elements.
	 * @param  string $model_id Element model id.
	 *
	 * @return array|false Return array if element found.
	 */
	public static function find_element_recursive( $elements, $model_id ) {
		foreach ( $elements as $element ) {
			if ( $model_id === $element['id'] ) {
				return $element;
			}

			if ( ! empty( $element['elements'] ) ) {
				$element = self::find_element_recursive( $element['elements'], $model_id );

				if ( $element ) {
					return $element;
				}
			}
		}

		return false;
	}

	/**
	 * Wrapper around the core WP get_plugins function, making sure it's actually available.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @param string $plugin_folder Optional. Relative path to single plugin folder.
	 *
	 * @return array Array of installed plugins with plugin information.
	 */
	public static function get_plugins( $plugin_folder = '' ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return get_plugins( $plugin_folder );
	}

	/**
	 * Checks if a plugin is installed. Does not take must-use plugins into account.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @param string $slug Required. Plugin slug.
	 *
	 * @return bool True if installed, false otherwise.
	 */
	public static function is_plugin_installed( $slug ) {
		return ! empty( self::get_plugins( '/' . $slug ) );
	}

	/**
	 * Get automatic direction based on RTL/LTR.
	 *
	 * @since 1.0.0
	 *
	 * @param string $direction The direction.
	 *
	 * @return string The direction.
	 */
	public static function get_direction( $direction ) {
		if ( ! is_rtl() ) {
			return $direction;
		}

		if ( false !== stripos( $direction, 'left' ) ) {
			return str_replace( 'left', 'right', $direction );
		}

		if ( false !== stripos( $direction, 'right' ) ) {
			return str_replace( 'right', 'left', $direction );
		}

		return $direction;
	}

	/**
	 * Get post ID based on document.
	 *
	 * @since 1.0.0
	 */
	public static function get_current_post_id() {
		if ( isset( Elementor::$instance->documents ) && ! empty( Elementor::$instance->documents->get_current() ) ) {
			return Elementor::$instance->documents->get_current()->get_main_id();
		}

		return get_the_ID();
	}

	/**
	 * Get Client IP Address.
	 *
	 * @since 1.2.0
	 * @access private
	 * @static
	 *
	 * @return string
	 */
	public static function get_client_ip() {
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput
		if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip_address = sanitize_text_field( $_SERVER['HTTP_CLIENT_IP'] );
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip_address = sanitize_text_field( $_SERVER['HTTP_X_FORWARDED_FOR'] );
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED'] ) ) {
			$ip_address = sanitize_text_field( $_SERVER['HTTP_X_FORWARDED'] );
		} elseif ( isset( $_SERVER['HTTP_FORWARDED_FOR'] ) ) {
			$ip_address = sanitize_text_field( $_SERVER['HTTP_FORWARDED_FOR'] );
		} elseif ( isset( $_SERVER['HTTP_FORWARDED'] ) ) {
			$ip_address = sanitize_text_field( $_SERVER['HTTP_FORWARDED'] );
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip_address = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
		}
		// phpcs:enable
		return $ip_address;
	}

	/**
	 * Download File.
	 *
	 * @since 1.2.0
	 * @access public
	 * @static
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	public static function handle_file_download() {
		$file  = filter_input( INPUT_GET, 'file' );
		$nonce = filter_input( INPUT_GET, '_wpnonce' );

		// Validate nonce.
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce ) ) {
			wp_die( '<script>window.close();</script>' );
		}

		$upload_dir = wp_get_upload_dir();
		$root_path  = wp_normalize_path( $upload_dir['basedir'] );

		$file = base64_decode($file); // phpcs:ignore
		$file = wp_normalize_path( $file );

		if ( strpos( wp_normalize_path( $file ), $root_path ) !== 0 ) {
			wp_die( '<script>window.close();</script>' );
		}

		// Make sure file exists.
		if ( empty( $file ) || ! file_exists( $file ) ) {
			wp_die( '<script>window.close();</script>' );
		}

		// Ensure the file is within the root path
		$real_file_path = realpath( $file );
		if (
			false === $real_file_path ||
			strpos( wp_normalize_path( $real_file_path ), $root_path ) !== 0
		) {
			wp_die( '<script>window.close();</script>' );
		}

		$file_name = pathinfo( $file, PATHINFO_BASENAME );
		$file_name = sanitize_file_name( $file_name );
		$file_info = wp_check_filetype_and_ext( $file, $file_name );

		// Validate file extension and MIME type.
		if ( empty( $file_info['ext'] ) || empty( $file_info['type'] ) ) {
			wp_die( '<script>window.close();</script>' );
		}

		// Validate file path.
		if (
			strpos( $file, wp_normalize_path( WP_CONTENT_DIR . '/uploads/' ) ) === false ||
			strpos( $file, wp_normalize_path( WP_CONTENT_DIR . '/uploads/' ) ) !== 0
		) {
			wp_die( '<script>window.close();</script>' );
		}

		// Restrict the download to WP upload directory.
		if (
			strpos( $file, $root_path ) === false ||
			strpos( $file, $root_path ) !== 0
		) {
			wp_die( '<script>window.close();</script>' );
		}

		$file_ext = pathinfo( $file, PATHINFO_EXTENSION );

		// Strip hash.
		$file_name  = str_replace( $file_ext, '', $file_name );
		$file_parts = explode( '__', $file_name );
		$file_name  = array_shift( $file_parts );
		$file_name .= '.' . $file_ext;

		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . $file_name . '"' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . filesize( $file ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions
		readfile( $file );
	}

	/**
	 * Refresh Global product variable.
	 *
	 * @since 2.5.0
	 * @access public
	 * @static
	 */
	public static function get_product() {
		global $product;

		if ( ! empty( wc_get_product() ) ) {
			$product = wc_get_product();

			return $product;
		}

		$args = [
			'post_type' => 'product',
			'stock' => 1,
			'posts_per_page' => 1,
			'orderby' => 'date',
			'order' => 'DESC',
			'post_status' => 'publish',
		];

		$last_product = get_posts( $args );

		if ( empty( $last_product ) ) {
			return;
		}

		$last    = $last_product[0];
		$product = wc_get_product( $last->ID );

		return wc_get_product( $last->ID );
	}

	/**
	 * Get page title based on a current query.
	 *
	 * @param bool $include_context whether to prefix result with the context.
	 * @return string the page title.
	 * @since 2.5.0
	 * @access public
	 * @static
	 *
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
	public static function get_page_title( $include_context = true ) {
		$title = '';

		if ( is_singular() ) {
			$singular_name = get_post_type_object( get_post_type() )->labels->singular_name;
			$title         = $include_context ? $singular_name . ': ' . get_the_title() : get_the_title();

			return $title;
		}

		if ( is_search() ) {
			$title  = esc_html__( 'Search Results for: ', 'jupiterx-core' ) . get_search_query();
			$title .= get_query_var( 'paged' ) ? esc_html__( '&nbsp;&ndash; Page ', 'jupiterx-core' ) . get_query_var( 'paged' ) : '';

			return $title;
		}

		if ( is_category() ) {
			$title  = $include_context ? esc_html__( 'Category: ', 'jupiterx-core' ) : '';
			$title .= single_cat_title( '', false );

			return $title;
		}

		if ( is_tag() ) {
			$title  = $include_context ? esc_html__( 'Tag: ', 'jupiterx-core' ) : '';
			$title .= single_tag_title( '', false );

			return $title;
		}

		if ( is_author() ) {
			$title  = $include_context ? esc_html__( 'Author: ', 'jupiterx-core' ) : '';
			$title .= '<span class="vcard">' . get_the_author() . '</span>';

			return $title;
		}

		if ( is_year() ) {
			$title  = $include_context ? esc_html__( 'Year: ', 'jupiterx-core' ) : '';
			$title .= get_the_date( _x( 'Y', 'yearly archives date format', 'jupiterx-core' ) );

			return $title;
		}

		if ( is_month() ) {
			$title  = $include_context ? esc_html__( 'Month: ', 'jupiterx-core' ) : '';
			$title .= get_the_date( _x( 'F Y', 'monthly archives date format', 'jupiterx-core' ) );

			return $title;
		}

		if ( is_day() ) {
			$title  = $include_context ? esc_html__( 'Day: ', 'jupiterx-core' ) : '';
			$title .= get_the_date( _x( 'F j, Y', 'daily archives date format', 'jupiterx-core' ) );

			return $title;
		}

		if ( is_tax( 'post_format' ) ) {
			if ( is_tax( 'post_format', 'post-format-aside' ) ) {
				return _x( 'Asides', 'post format archive title', 'jupiterx-core' );
			}

			if ( is_tax( 'post_format', 'post-format-gallery' ) ) {
				return _x( 'Galleries', 'post format archive title', 'jupiterx-core' );
			}

			if ( is_tax( 'post_format', 'post-format-image' ) ) {
				return _x( 'Images', 'post format archive title', 'jupiterx-core' );
			}

			if ( is_tax( 'post_format', 'post-format-video' ) ) {
				return _x( 'Videos', 'post format archive title', 'jupiterx-core' );
			}

			if ( is_tax( 'post_format', 'post-format-quote' ) ) {
				return _x( 'Quotes', 'post format archive title', 'jupiterx-core' );
			}

			if ( is_tax( 'post_format', 'post-format-link' ) ) {
				return _x( 'Links', 'post format archive title', 'jupiterx-core' );
			}

			if ( is_tax( 'post_format', 'post-format-status' ) ) {
				return _x( 'Statuses', 'post format archive title', 'jupiterx-core' );
			}

			if ( is_tax( 'post_format', 'post-format-audio' ) ) {
				return _x( 'Audio', 'post format archive title', 'jupiterx-core' );
			}

			if ( is_tax( 'post_format', 'post-format-chat' ) ) {
				return _x( 'Chats', 'post format archive title', 'jupiterx-core' );
			}
		}

		if ( is_post_type_archive() ) {
			$title  = $include_context ? esc_html__( 'Archives: ', 'jupiterx-core' ) : '';
			$title .= post_type_archive_title( '', false );

			return $title;
		}

		if ( is_tax() ) {
			$tax_singular_name = get_taxonomy( get_queried_object()->taxonomy )->labels->singular_name;

			$title  = $include_context ? $tax_singular_name . ': ' : '';
			$title .= single_term_title( '', false );

			return $title;
		}

		if ( is_archive() ) {
			return esc_html__( 'Archives', 'jupiterx-core' );
		}

		if ( is_404() ) {
			return esc_html__( 'Page Not Found', 'jupiterx-core' );
		}

		return $title;
	}

	/**
	 * Get list of the post types.
	 *
	 * @param array $arg post types arguments.
	 * @since 2.5.3
	 * @access public
	 * @static
	 * @return array
	 */
	public static function get_public_post_types( $args = [] ) {
		$post_type_args = [
			'show_in_nav_menus' => true,
		];

		if ( ! empty( $args['post_type'] ) ) {
			$post_type_args['name'] = $args['post_type'];
			unset( $args['post_type'] );
		}

		$post_type_args = wp_parse_args( $post_type_args, $args );

		$_post_types = get_post_types( $post_type_args, 'objects' );

		$post_types = [];

		foreach ( $_post_types as $post_type => $object ) {
			$post_types[ $post_type ] = $object->label;
		}

		return $post_types;
	}

	/**
	 * Get taxonomies based on post type.
	 *
	 * @param array  $args
	 * @param string $output
	 * @param string $operator
	 * @return array
	 */
	public static function get_taxonomies( $args = [], $output = 'names', $operator = 'and' ) {
		global $wp_taxonomies;

		$field = ( 'names' === $output ) ? 'name' : false;

		if ( isset( $args['object_type'] ) ) {
			$object_type = (array) $args['object_type'];
			unset( $args['object_type'] );
		}

		$taxonomies = wp_filter_object_list( $wp_taxonomies, $args, $operator );

		if ( isset( $object_type ) ) {
			foreach ( $taxonomies as $tax => $tax_data ) {
				if ( ! array_intersect( $object_type, $tax_data->object_type ) ) {
					unset( $taxonomies[ $tax ] );
				}
			}
		}

		if ( $field ) {
			$taxonomies = wp_list_pluck( $taxonomies, $field );
		}

		return $taxonomies;
	}

	/**
	 * Get animated gradient attributes.
	 *
	 * @param  string  $direction Gradient direction.
	 * @param  array $gradient_color_list Gradient color list.
	 * @return array
	 */
	public static function get_animated_gradient_attributes( $direction, $gradient_color_list ) {
		$background_size_color_count = ( count( $gradient_color_list ) + 1 ) * 100;
		$data_background_size        = $background_size_color_count . '% 100%';
		$data_animation_name         = 'AnimatedGradientBgLeft';
		$angle                       = '90deg';

		if ( 'right' === $direction ) {
			$data_background_size = $background_size_color_count . '% 100%';
			$data_animation_name  = 'AnimatedGradientBgRight';
			$angle                = '90deg';

			return compact( 'data_background_size', 'data_animation_name', 'angle' );
		}

		if ( 'up' === $direction ) {
			$data_background_size = '100% ' . $background_size_color_count . '%';
			$data_animation_name  = 'AnimatedGradientBgUp';
			$angle                = '0deg';

			return compact( 'data_background_size', 'data_animation_name', 'angle' );
		}

		if ( 'down' === $direction ) {
			$data_background_size = '100% ' . $background_size_color_count . '%';
			$data_animation_name  = 'AnimatedGradientBgDown';
			$angle                = '0deg';

			return compact( 'data_background_size', 'data_animation_name', 'angle' );
		}

		return compact( 'data_background_size', 'data_animation_name', 'angle' );
	}

	/**
	 * Check if jupiterx is fresh install.
	 *
	 * @since 4.0.0
	 * @return boolean
	 */
	public static function check_fresh_install() {
		$check_by_filter = apply_filters( 'jupiterx_enable_global_colors', null );

		if ( ! is_null( $check_by_filter ) ) {
			return $check_by_filter;
		}

		if ( ! empty( self::$fresh_install ) ) {
			return self::$fresh_install;
		}

		self::$fresh_install = get_option( 'jupiterx_enable_global_colors', false );

		if ( self::$fresh_install ) {
			return true;
		}

		$fresh_install = get_option( 'jupiterx_fresh_install', false );

		$version = wp_get_theme()->get( 'Version' );

		if ( is_a( wp_get_theme()->parent(), '\WP_Theme' ) ) {
			$version = wp_get_theme()->parent()->get( 'Version' );
		}

		/**
		 * @since 4.0.0
		 */
		if ( $fresh_install && ! version_compare( $version, '4.0.0', '<' ) ) {
			delete_option( 'jupiterx_fresh_install' );
			update_option( 'jupiterx_enable_global_colors', true );

			return true;
		}

		return false;
	}


	/**
	 * Set controller old default value.
	 *
	 * @param string $default Old default value.
	 * @since 4.0.0
	 * @return string
	 */
	public static function set_old_default_value( $default ) {
		if ( ! self::check_fresh_install() ) {
			return $default;
		}

		return '';
	}

	/**
	 * Set global color as default value.
	 *
	 * @param string $global_color Elementor global color.
	 * @param string $old_color    Old default value.
	 * @since 4.0.0
	 * @return array
	 */
	public static function set_default_value( $global_color, $old_color = '' ) {
		if ( ! self::check_fresh_install() ) {
			return [
				'default' => $old_color,
			];
		}

		return [
			'default' => self::ELEMENTOR_GLOBAL_COLORS[ $global_color ],
		];
	}

	/**
	 * Set global color as default value for raven-background controller.
	 *
	 * @param string $global_color Elementor global color.
	 * @param string $old_color    Old default value.
	 * @since 4.0.0
	 * @return array
	 */
	public static function set_background_default_value( $global_color, $old_color = '' ) {
		if ( ! self::check_fresh_install() ) {
			return [
				'default' => $old_color,
			];
		}

		return [
			'global' => [
				'default' => self::ELEMENTOR_GLOBAL_COLORS[ $global_color ],
			],
		];
	}

	/**
	 * Set default value for background type.
	 *
	 * @param string $new_type New background type
	 * @param string $old_type Old background type.
	 * @since 4.0.0
	 * @return string
	 */
	public static function set_background_type_default_value( $new_type, $old_type = '' ) {
		if ( ! self::check_fresh_install() ) {
			return $old_type;
		}

		return $new_type;
	}
}
