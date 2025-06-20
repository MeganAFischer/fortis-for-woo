<?php
/**
 * Handles TGMPA functionalities.
 *
 * @since 1.5.0
 *
 * @package Jupiter\Framework\Admin\TGMPA
 */

add_action( 'tgmpa_register', 'jupiterx_register_tgmpa_plugins' );
/**
 * Register the required plugins.
 *
 * @since 1.5.0
 *
 * @SuppressWarnings(PHPMD.ElseExpression)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 */
function jupiterx_register_tgmpa_plugins() {
	if ( ! jupiterx_is_premium() ) :
		$free_plugins = [
			[
				'name' => esc_html__( 'Jupiter X Core', 'jupiterx' ),
				'slug' => 'jupiterx-core',
				'required' => false,
				'force_activation' => false,
				'force_deactivation' => false,
			],
			[
				'name' => esc_html__( 'Elementor', 'jupiterx' ),
				'slug' => 'elementor',
				'required' => false,
				'force_activation' => false,
				'force_deactivation' => false,
			],
		];

		$plugins = apply_filters( 'jupiterx_tgmpa_plugins', $free_plugins );
	else :
		$transient_key = 'jupiterx_tgmpa_plugins_check';
		$option_key    = 'jupiterx_tgmpa_plugins';

		$plugins       = get_option( $option_key );
		$plugins_check = get_transient( $transient_key );

		if ( version_compare( JUPITERX_VERSION, '2.0.0', '>=' ) ) {
			$sellkit = [];
			$body    = [];

			$jupiterx_settings = get_option( 'jupiterx' );

			if ( defined( 'SELLKIT_PARTNER_ID' ) ) {
				$body = [
					'partner_id' => SELLKIT_PARTNER_ID,
				];
			}

			if ( isset( $jupiterx_settings['api_key'] ) && ! empty( $jupiterx_settings['api_key'] ) ) {
				$body['artbees_api_key'] = $jupiterx_settings['api_key'];
			}

			if ( isset( $jupiterx_settings['envato_purchase_code_5177775'] ) && ! empty( $jupiterx_settings['envato_purchase_code_5177775'] ) ) {
				$body['purchase_key'] = $jupiterx_settings['envato_purchase_code_5177775'];
			}

			if ( empty( get_transient( 'jupiterx_sellkit_pro_link' ) ) ) {
				$sellkit_repo = jupiterx_get_sellkit_download_link( $body );

				if ( empty( $sellkit_repo ) ) {
					wp_schedule_single_event( time() + 60, 'jupiterx_get_sellkit_link_schedule_hook', [ $body ] );
				}
			}

			$sellkit = [
				'id' => 99999,
				'theme_name' => 'jupiterx',
				'name' => 'Sellkit Pro',
				'slug' => 'sellkit-pro',
				'source' => get_transient( 'jupiterx_sellkit_pro_link' ),
				'source_type' => 'external',
				'file_path' => 'sellkit-pro/sellkit-pro.php',
				'basename' => 'sellkit-pro/sellkit-pro.php',
			];

			$sellkit_free = [
				'id' => 99998,
				'theme_name' => 'jupiterx',
				'name' => 'Sellkit',
				'slug' => 'sellkit',
				'basename' => 'sellkit/sellkit.php',
			];

			if ( ! is_array( $plugins ) ) {
				$plugins = [];
			}

			array_push( $plugins, $sellkit_free );
			array_push( $plugins, $sellkit );
		}

		if ( ! is_array( $plugins ) ) {
			$plugins = [];
		}

		if ( false === $plugins_check && jupiterx_is_premium() ) {
			$response = json_decode( wp_remote_retrieve_body( wp_remote_get( 'https://my.artbees.net/wp-json/plugins/v1/list?theme_name=jupiterx' ) ) );

			if ( ! is_array( $response ) ) {
				set_transient( $transient_key, [], 2 * HOUR_IN_SECONDS );

				return;
			}

			$required_plugins = [
				'jupiterx-core',
				'elementor',
			];

			foreach ( $response as $index => $plugin ) {
				$plugins[ $index ] = (array) $plugin;

				if ( 'wp-repo' === $plugins[ $index ]['source'] ) {
					unset( $plugins[ $index ]['version'] );
					unset( $plugins[ $index ]['source'] );
				}

				if (
					! empty( $plugins[ $index ]['label_type'] ) &&
					'Optional' === $plugins[ $index ]['label_type']
				) {
					$plugins[ $index ]['label_type'] = __( 'Optional', 'jupiterx' );
				}

				if ( ! in_array( $plugins[ $index ]['slug'], $required_plugins, true ) ) {
					$plugins[ $index ]['required'] = false;
				}
			}

			$plugins = apply_filters( 'jupiterx_tgmpa_api_plugins', $plugins );

			update_option( $option_key, $plugins, 'no' );
			set_transient( $transient_key, [], DAY_IN_SECONDS );
		}
	endif;

	if ( empty( $plugins ) ) {
		return;
	}

	$config = [
		'id'           => 'jupiterx',
		'default_path' => '',
		'menu'         => 'tgmpa-install-plugins',
		'has_notices'  => false,
		'dismissable'  => false,
		'dismiss_msg'  => '',
		'is_automatic' => false,
		'message'      => '',
	];

	tgmpa( $plugins, $config );
}

add_filter( 'tgmpa_plugin_action_links', 'jupiterx_tgmpa_go_pro_link' );
add_filter( 'tgmpa_network_admin_plugin_action_links', 'jupiterx_tgmpa_go_pro_link' );
/**
 * Change go pro action links in TGMPA.
 *
 * @param array $action_links List of action links.
 *
 * @since 1.10.0
 *
 * @return array $action_links Modified list of action links.
 */
function jupiterx_tgmpa_go_pro_link( $action_links ) {
	if ( isset( $action_links['pro'] ) ) {
		$action_links['pro'] = '<a href="' . esc_url( jupiterx_upgrade_link( 'plugins' ) ) . '" class="jupiterx-tgmpa-pro-plugin-action-link" target="_blank">' . esc_html__( 'Go Pro', 'jupiterx' ) . '<span class="screen-reader-text">' . esc_html__( 'Buy Jupiter X', 'jupiterx' ) . '</span></a>';
	}

	return $action_links;
}

if ( ! function_exists( 'jupiterx_filter_tgmpa_plugins' ) ) {
	/**
	 * Hide optional plugins in TGMPA page but load plugins for POST, DELETE type requests.
	 *
	 * @since 1.18.0
	 *
	 * @param array $plugins Array of plugins.
	 * @param array $includes Array of plugins that shouldn't be excluded.
	 * @return array
	 */
	function jupiterx_filter_tgmpa_plugins( $plugins, $includes = [] ) {
		if ( ! jupiterx_is_premium() ) {
			return array_filter( $plugins, function ( $plugin ) use ( $includes ) {
				if ( in_array( $plugin['slug'], $includes, true ) ) {
					return true;
				}

				return empty( $plugin['label_type'] ) || 'Optional' !== $plugin['label_type'];
			} );
		}

		return array_filter( $plugins, function ( $plugin ) use ( $includes ) {
			if ( in_array( $plugin['slug'], $includes, true ) ) {
				return true;
			}

			return ! empty( $plugin['required'] );
		} );
	}
}

/**
 * Hide Optional plugins in TGMPA table.
 *
 * @since 1.18.0
 */
add_action( 'before_tgmpa_plugins_table_render', function () {
	if ( empty( $GLOBALS['tgmpa'] ) || ! class_exists( 'TGM_Plugin_Activation' ) ) {
		return;
	}

	$instance = call_user_func( array( get_class( $GLOBALS['tgmpa'] ), 'get_instance' ) );

	if ( empty( $instance ) ) {
		return;
	}

	$instance->plugins = jupiterx_filter_tgmpa_plugins( $instance->plugins, [
		'sellkit',
		'sellkit-pro',
	] );
});

/**
 * Exclude Optional plugins in TGMPA notice
 *
 * @since 1.18.0
 */
add_filter( 'jupiterx_tgmpa_filter_admin_notice_plugins', function ( $plugins ) {
	return jupiterx_filter_tgmpa_plugins( $plugins );
} );

/**
 * Loads TGMPA.
 *
 * @since 3.5.6
 */
add_filter( 'tgmpa_load', '__return_true', 10, 1 );
