<?php
/**
 * EDD_GetDownloads_Endpoint class
 *
 * @category Edd
 * @package  Edd
 * @author   Rajendra Banker <bankerrajendra@upnrunn.com>
 * @license  GNU
 * @link     NA
 */

defined( 'ABSPATH' ) || exit;

/**
 * EDD GetDownload endpoints.
 *
 * @category Edd
 * @package  Edd
 * @author   Rajendra Banker <bankerrajendra@upnrunn.com>
 * @license  GNU
 * @link     NA
 * @since    1.0.0
 */
class EDD_GetDownloads_Endpoint extends WP_REST_Controller {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->post_type = 'download';
		$this->namespace = 'wpdriftio/v1';
		$this->rest_base = 'getdownloads';
	}

	/**
	 * Register the component routes.
	 *
	 * @since  1.0.0
	 * @return return
	 */
	public function registerRoutes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(),
				),
			)
		);

		$download_endpoint = '/' . $this->rest_base . '/(?P<id>[\d]+)';

		register_rest_route(
			$this->namespace,
			$download_endpoint,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param(
							array(
								'default' => 'view',
							)
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/ids',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_ids' ),
					'permission_callback' => array( $this, 'get_ids_permissions_check' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/ids_updated',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_ids_updated' ),
					'permission_callback' => array( $this, 'get_ids_permissions_check' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/counts',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_counts' ),
					'permission_callback' => array( $this, 'get_counts_permissions_check' ),
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * Get a collection of items
	 * @param  [type] $request [description]
	 * @return [type]          [description]
	 */
	public function get_items( $request ) {
		$parameters             = $request->get_params();
		$items                  = array();
		$items['edd_downloads'] = $this->retrieveEddDownlads( $parameters );
		$data                   = array();
		foreach ( $items as $key => $item ) {
			$itemdata     = $this->prepareItemForResponse( $item, $request );
			$data[ $key ] = $this->prepare_response_for_collection( $itemdata );
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Retrieve EDD Downloads
	 * @param  [type] $parameters [description]
	 * @return [type]             [description]
	 */
	public function retrieveEddDownlads( $parameters ) {
		$posts_per_page = ( isset( $parameters['per_page'] ) && trim( $parameters['per_page'] ) != '' )
							? trim( $parameters['per_page'] )
							: 1;
		$offset         = ( isset( $parameters['offset'] ) && trim( $parameters['offset'] ) != '' )
					? trim( $parameters['offset'] )
					: 0;
		$task           = ( isset( $parameters['task'] ) && trim( $parameters['task'] ) != '' ) ? trim( $parameters['task'] ) : '';
		$post_id        = ( isset( $parameters['id'] ) && trim( $parameters['id'] ) != '' ) ? trim( $parameters['id'] ) : '';

		$args = array(
			'post_type'      => 'download',
			'post_status'    => 'any',
			'posts_per_page' => $posts_per_page,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		);
		if ( $task == 'get_totals' ) {
			$downloads                      = new WP_Query( $args );
			$edd_downloads['found_posts']   = $downloads->found_posts;
			$edd_downloads['max_num_pages'] = $downloads->max_num_pages;
		} elseif ( $task == 'get_single' ) {
			$edd_downloads = get_post( (int) $post_id );
		} else {
			$args['offset'] = $offset;
			$edd_downloads  = get_posts( $args );
		}
		return $edd_downloads;
	}

	/**
	 * Prepare the item for the REST response
	 * @param  [type] $item    [description]
	 * @param  [type] $request [description]
	 * @return [type]          [description]
	 */
	public function prepareItemForResponse( $item, $request ) {
		return $item;
	}

	/**
	 * Check if a given request has access to get items
	 * @param  [type] $request [description]
	 * @return [type]          [description]
	 */
	public function get_items_permissions_check( $request ) {
		return current_user_can( 'list_users' );
	}

	/**
	 * [get_item description]
	 * @param  [type] $request [description]
	 * @return [type]          [description]
	 */
	public function get_item( $request ) {
		$download_id = is_numeric( $request ) ? $request : (int) $request['id'];
		return get_post( $download_id );
	}

	/**
	 * [get_item_permissions_check description]
	 * @param  [type] $request [description]
	 * @return [type]          [description]
	 */
	public function get_item_permissions_check( $request ) {
		return current_user_can( 'list_users' );
	}

	/**
	 * [get_ids description]
	 * @param  [type] $request [description]
	 * @return [type]          [description]
	 */
	public function get_ids( $request ) {
		global $wpdb;
		$download_ids = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_type = 'download'" );
		return $download_ids;
	}

	/**
	 * [get_ids_updated description]
	 * @param  [type] $request [description]
	 * @return [type]          [description]
	 */
	public function get_ids_updated( $request ) {
		global $wpdb;

		$date = [];
		if ( isset( $request['after'] ) ) {
			$date[] = [
				'after' => $request['after'],
			];
		}
		$date_query   = new WP_Date_Query( $date, 'post_modified' );
		$query_where  = "WHERE post_type = 'download'";
		$query_where .= $date_query->get_sql();
		$ids_updated  = $wpdb->get_col( "SELECT ID FROM $wpdb->posts $query_where" );
		return $ids_updated;
	}

	/**
	 * [get_ids_permissions_check description]
	 * @param  [type] $request [description]
	 * @return [type]          [description]
	 */
	public function get_ids_permissions_check( $request ) {
		return current_user_can( 'list_users' );
	}

	/**
	 * [get_counts description]
	 * @param  [type] $request [description]
	 * @return [type]          [description]
	 */
	public function get_counts( $request ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( * ) FROM {$wpdb->posts} WHERE post_type = %s", $this->post_type ) );
	}

	/**
	 * [get_counts_permissions_check description]
	 * @param  [type] $request [description]
	 * @return [type]          [description]
	 */
	public function get_counts_permissions_check( $request ) {
		return current_user_can( 'list_users' );
	}
}
