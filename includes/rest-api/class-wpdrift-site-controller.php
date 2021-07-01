<?php
/**
 * REST API: WPdrift_Site_Controller class
 *
 * @package WPdrift IO
 * @subpackage REST_API
 * @since 1.0.0
 */

/**
 * [WPdrift_Site_Controller description]
 */
class WPdrift_Site_Controller extends WP_REST_Controller {

	/**
	 * Here initialize our namespace and resource name.
	 */
	public function __construct() {
		$this->namespace = 'wpdriftio/v1';
		$this->rest_base = 'site';
	}

	/**
	 * Register our routes.
	 * @return [type] [description]
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/plugin-status',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_plugin_status' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/edd-plugin-status',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_edd_plugin_status' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
			]
		);
	}

	/**
	 * Grabs site details.
	 * @return [type] [description]
	 */
	public function get_items( $request ) {
		return rest_ensure_response(
			[
				'name'              => get_bloginfo( 'name' ),
				'description'       => get_bloginfo( 'description' ),
				'version'           => get_bloginfo( 'version' ),
				'url'               => get_bloginfo( 'url' ),
				'admin_email'       => get_bloginfo( 'admin_email' ),
				'language'          => get_bloginfo( 'language' ),
				'rss2_url'          => get_bloginfo( 'rss2_url' ),
				'comments_rss2_url' => get_bloginfo( 'comments_rss2_url' ),
				'admin_url'         => admin_url(),
				'ajax_url'          => admin_url( 'admin-ajax.php' ),
			]
		);
	}

	/**
	 * [public description]
	 * @var [type]
	 */
	public function get_plugin_status( $request ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		// check for plugin using plugin name
		if ( ! is_plugin_active( plugin_basename( WPDRIFT_WORKER_PLUGIN_FILE ) ) ) {
			return rest_ensure_response( array() );
		}

		return [ 'version' => WPDRIFT_WORKER_VERSION ];
	}

	/**
	 * Get the edd plugin status.
	 * @var [type]
	 */
	public function get_edd_plugin_status( $request ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		// check for plugin using plugin name
		if ( ! is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' ) ) {
			return rest_ensure_response( array() );
		}

		return [ 'version' => EDD_VERSION ];
	}

	/**
	 * Check permissions for the posts.
	 * @param WP_REST_Request $request Current request.
	 */
	public function get_items_permissions_check( $request ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return true;
		}

		if ( ! in_array( $_SERVER['REMOTE_ADDR'], [ '67.205.168.206', '167.99.167.87' ], true ) ) {
			return new WP_Error( 'rest_forbidden', esc_html__( 'You cannot view the resource.' ), array( 'status' => $this->authorization_status_code() ) );
		}

		return true;
	}

	/**
	 * Sets up the proper HTTP status code for authorization.
	 * @return [type] [description]
	 */
	public function authorization_status_code() {
		$status = 401;

		if ( is_user_logged_in() ) {
			$status = 403;
		}

		return $status;
	}
}
