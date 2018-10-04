<?php
/**
 * EDD_GetCustomers_Metas_Endpoint class
 *
 * @category Edd
 * @package  Edd
 * @author   Rajendra Banker <bankerrajendra@upnrunn.com>
 * @license  GNU
 * @link     NA
 */

defined('ABSPATH') || exit;

/**
 * EDD Get Customers Meta endpoints.
 *
 * @category Edd
 * @package  Edd
 * @author   Rajendra Banker <bankerrajendra@upnrunn.com>
 * @license  GNU
 * @link     NA
 * @since    1.0.0
 */
class EDD_GetCustomers_Metas_Endpoint extends WP_REST_Controller
{
    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->namespace = 'wpdriftio/v1';
        $this->rest_base = 'getcustomers-metas';
    }

    /**
     * Register the component routes.
     *
     * @since  1.0.0
     * @return return
     */
    public function registerRoutes()
    {
        register_rest_route(
            $this->namespace, '/' . $this->rest_base, array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'getItems' ),
                    'permission_callback' => array( 
                                                $this, 
                                                'getItemsPermissionsCheck'
                                            ),
                    'args'                => array(

                    ),
                )
            )
        );
    }

    /**
     * Get a collection of items
     *
     * @param WP_REST_Request $request Full data about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function getItems($request)
    {
        $parameters = $request->get_params();
        $items = array();
        $items['edd_customers_metas'] 
            = $this->retrieveEddCustomersMetas($parameters);
        $data = array();
        foreach ($items as $key => $item) {
            $itemdata = $this->prepareItemForResponse($item, $request);
            $data[$key] = $this->prepare_response_for_collection($itemdata);
        }

        return rest_ensure_response($data);
    }

    /**
     * Prepare the item for the REST response
     *
     * @param mixed           $item    WordPress representation of the item.
     * @param WP_REST_Request $request Request object.
     *
     * @return mixed
     */
    public function prepareItemForResponse($item, $request)
    {
        return $item;
    }

    /**
     * Check if a given request has access to get items
     *
     * @param WP_REST_Request $request Full data about the request.
     *
     * @return WP_Error|bool
     */
    public function getItemsPermissionsCheck($request)
    {
        return current_user_can('list_users');
    }

    /**
     * Retrieve EDD Customers Meta
     *
     * @param string $parameters parameters.
     *
     * @return WP_Error|bool
     */
    public function retrieveEddCustomersMetas($parameters)
    {
        global $wpdb;
        $posts_per_page = (isset($parameters['per_page']) && trim($parameters['per_page']) != "") ? trim($parameters['per_page']) : 1;
        $offset = (isset($parameters['offset']) && trim($parameters['offset']) != "") ? trim($parameters['offset']) : 0;
        $task = (isset($parameters['task']) && trim($parameters['task']) != "") ? trim($parameters['task']) : "";
        $post_id = (isset($parameters['id']) && trim($parameters['id']) != "") ? trim($parameters['id']) : "";

        if ($task == "get_totals") {
            $found_posts = $wpdb->get_var( 
                "SELECT count(`meta_id`) FROM ".$wpdb->prefix."edd_customermeta" 
            );
            $customers_metas['found_posts'] = $found_posts;
            $max_num_pages = ceil($found_posts / $posts_per_page);
            $customers_metas['max_num_pages'] = $max_num_pages;
        } else if ($task == "get_single") {
            $customers_metas = $wpdb->get_results( 
                $wpdb->prepare(
                    "SELECT * FROM 
                    ".$wpdb->prefix."edd_customermeta 
                    WHERE customer_id = %d", $post_id
                )
            );
        } else {
            $customers_metas = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM 
                    ".$wpdb->prefix."edd_customermeta 
                    LIMIT %d,%d", $offset, $posts_per_page 
                )
            );
        }
        return $customers_metas;
    }
}
