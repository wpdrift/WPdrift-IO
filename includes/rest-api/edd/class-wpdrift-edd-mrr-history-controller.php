<?php
/**
 * REST API: WPdrift_EDD_MRR_History_Controller class
 *
 * @package WPdrift IO
 * @subpackage REST_API
 * @since 1.0.0
 */

/**
 * [WPdrift_EDD_MRR_History_Controller description]
 */
class WPdrift_EDD_MRR_History_Controller extends WP_REST_Controller {

	/**
	 * Here initialize our namespace and resource name.
	 */
	public function __construct() {
		$this->namespace = 'wpdriftio/v1';
		$this->rest_base = 'mrrhistory';
	}

	/**
	 * Register our routes.
	 * @return [type] [description]
	 */
	public function register_routes() {
		/**
		 * [register_rest_route description]
		 * @var [type]
		 */
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);

		/**
		 * [register_rest_route description]
		 * @var [type]
		 */
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);

		/**
		 * [register_rest_route description]
		 * @var [type]
		 */
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/all',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_all' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);

		/**
		 * [register_rest_route description]
		 * @var [type]
		 */
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/updated',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_updated' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Grabs the most recent posts and outputs them as a rest response.
	 * @param  [type] $request [description]
	 * @return [type]          [description]
	 */
	public function get_items( $request ) {
		global $wpdb;
		return rest_ensure_response( [] );
	}

	/**
	 * [get_item description]
	 * @param  [type] $request [description]
	 * @return [type]          [description]
	 */
	public function get_item( $request ) {
		global $wpdb;
		$id          = is_numeric( $request ) ? $request : (int) $request['id'];
		$mrr_history = $wpdb->get_row(
			$wpdb->prepare(
				"
				SELECT *
				FROM {$wpdb->prefix}edd_mrr_history
				WHERE id = %d
				",
				$id
			)
		);

		if ( empty( $mrr_history ) ) {
			return rest_ensure_response( [] );
		}

		return rest_ensure_response( $mrr_history );
	}

	/**
	 * [get_all description]
	 * @param  [type] $request [description]
	 * @return [type]          [description]
	 */
	public function get_all( $request ) {
		global $wpdb;
		return $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}edd_mrr_history" );
	}

	/**
	 * [get_updated description]
	 * @param  [type] $request [description]
	 * @return [type]          [description]
	 */
	public function get_updated( $request ) {
		global $wpdb;

		if ( ! isset( $request['after'] ) ) {
			return [];
		}

		$date_query = new WP_Date_Query( [ 'after' => $request['after'] ], 'post_modified' );
		$date_sql   = str_replace( "{$wpdb->posts}.post_modified", "{$wpdb->prefix}edd_mrr_history.created", $date_query->get_sql() );
		$updated    = $wpdb->get_col(
			"
			SELECT id
			FROM {$wpdb->prefix}edd_mrr_history
			WHERE 1=1 {$date_sql}
			"
		);

		return $updated;
	}

	/**
	 * Check permissions for the posts.
	 * @param  [type] $request [description]
	 * @return [type]          [description]
	 */
	public function get_items_permissions_check( $request ) {
		return true;

		if ( ! current_user_can( 'list_users' ) ) {
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
