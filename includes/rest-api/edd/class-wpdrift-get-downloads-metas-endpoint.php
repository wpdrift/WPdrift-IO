<?php
/**
 * EDD_GetDownloads_Metas_Endpoint class
 */

defined('ABSPATH') || exit;

/**
 * EDD Downloads Metas endpoints.
 *
 * @since 1.0.0
 */
class EDD_GetDownloads_Metas_Endpoint extends WP_REST_Controller
{
    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->namespace = 'wpdriftio/v1';
        $this->rest_base = 'getdownloads-metas';
    }

    /**
     * Register the component routes.
     *
     * @since 1.0.0
     */
    public function register_routes()
    {
        register_rest_route($this->namespace, '/' . $this->rest_base, array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_items' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
                'args'                => array(

                ),
            )
        ));
    }

    /**
     * Get a collection of items
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_items($request)
    {
        $parameters = $request->get_params();
        $items = array();
        $items['edd_downloads_metas'] = $this->retrieve_edd_downlads_metas($parameters);
        $data = array();
        foreach ($items as $key => $item) {
            $itemdata = $this->prepare_item_for_response($item, $request);
            $data[$key] = $this->prepare_response_for_collection($itemdata);
        }

        return rest_ensure_response($data);
    }

    /**
     * Prepare the item for the REST response
     *
     * @param mixed $item WordPress representation of the item.
     * @param WP_REST_Request $request Request object.
     * @return mixed
     */
    public function prepare_item_for_response($item, $request)
    {
        return $item;
    }

    /**
     * Check if a given request has access to get items
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function get_items_permissions_check($request)
    {
        return current_user_can('list_users');
    }


    /**
    * Retrieve EDD Downloads Metas
    *
    * @since 1.0.0
    */
    public function retrieve_edd_downlads_metas($parameters)
    {
        global $wpdb;
        
        $edd_downloads = get_posts( array(
            'post_type'              => 'download',
            'post_status'            => 'any',
            'posts_per_page'         => -1,
            'orderby'                => 'ID',
            'order'                  => 'ASC',
        ) );
        
        $new_array = array();
        $i = 0;
        foreach ($edd_downloads as $edd_download) {
            $new_array[$i]['post_id'] = $edd_download->ID;
            // get posts meta
            $post_meta_arry = get_metadata('post', $edd_download->ID, '', false);
            // add post metas as main array elements
            foreach ($post_meta_arry as $post_meta_key => $post_meta_value) {
                $new_array[$i][$post_meta_key] = $post_meta_value[0];
            }

            $i++;
        }
        return $new_array;
    }
}