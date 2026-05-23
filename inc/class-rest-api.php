<?php

class BM_REST_API {

    private $nav_manager;

    public function __construct( BM_Nav_Manager $nav_manager ) {
        $this->nav_manager = $nav_manager;
    }

    private function get_current_user_id() {
        return get_current_user_id();
    }

    public function register_routes() {
        $namespace = 'bm/v1';

        register_rest_route( $namespace, '/init-data', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_init_data' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'page_id' => [
                    'sanitize_callback' => 'absint',
                ],
            ],
        ] );

        register_rest_route( $namespace, '/nav-items', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_nav_items' ],
                'permission_callback' => [ $this, 'check_read_permission' ],
                'args'                => [
                    'group_id' => [
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'create_nav_item' ],
                'permission_callback' => [ $this, 'check_write_permission' ],
                'args'                => [
                    'source_type'      => [ 'sanitize_callback' => 'sanitize_text_field' ],
                    'source_id'        => [ 'sanitize_callback' => 'absint' ],
                    'title'            => [ 'sanitize_callback' => 'sanitize_text_field' ],
                    'url'              => [ 'sanitize_callback' => 'esc_url_raw' ],
                    'icon'             => [ 'sanitize_callback' => 'sanitize_text_field' ],
                    'describe'         => [ 'sanitize_callback' => 'sanitize_text_field' ],
                    'group_id'         => [ 'sanitize_callback' => 'absint' ],
                    'layout'           => [ 'sanitize_callback' => 'sanitize_text_field' ],
                    'bg_color'         => [ 'sanitize_callback' => 'sanitize_text_field' ],
                    'text_icon'        => [ 'sanitize_callback' => 'sanitize_text_field' ],
                    'open_in_iframe'   => [ 'sanitize_callback' => 'absint' ],
                    'component_id'     => [ 'sanitize_callback' => 'absint' ],
                    'component_config' => [ 'sanitize_callback' => [ $this, 'sanitize_json' ] ],
                    'page_id'          => [ 'sanitize_callback' => 'absint' ],
                ],
            ],
        ] );

        register_rest_route( $namespace, '/nav-items/reorder', [
            'methods'             => 'PUT',
            'callback'            => [ $this, 'reorder_nav_items' ],
            'permission_callback' => [ $this, 'check_write_permission' ],
        ] );

        register_rest_route( $namespace, '/nav-items/(?P<id>\d+)', [
            [
                'methods'             => 'PUT',
                'callback'            => [ $this, 'update_nav_item' ],
                'permission_callback' => [ $this, 'check_write_permission' ],
                'args'                => [
                    'id' => [ 'sanitize_callback' => 'absint' ],
                ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ $this, 'delete_nav_item' ],
                'permission_callback' => [ $this, 'check_write_permission' ],
                'args'                => [
                    'id' => [ 'sanitize_callback' => 'absint' ],
                ],
            ],
        ] );

        register_rest_route( $namespace, '/candidates', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_candidates' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'page'     => [ 'sanitize_callback' => 'absint' ],
                'per_page' => [ 'sanitize_callback' => 'absint' ],
                'search'   => [ 'sanitize_callback' => 'sanitize_text_field' ],
                'category' => [ 'sanitize_callback' => 'absint' ],
            ],
        ] );

        register_rest_route( $namespace, '/groups', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_groups' ],
                'permission_callback' => [ $this, 'check_read_permission' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'create_group' ],
                'permission_callback' => [ $this, 'check_write_permission' ],
                'args'                => [
                    'title'      => [ 'sanitize_callback' => 'sanitize_text_field' ],
                    'icon'       => [ 'sanitize_callback' => 'sanitize_text_field' ],
                    'sort_order' => [ 'sanitize_callback' => 'absint' ],
                    'columns'    => [ 'sanitize_callback' => 'absint' ],
                    'icon_size'  => [ 'sanitize_callback' => 'sanitize_text_field' ],
                    'show_text'  => [ 'sanitize_callback' => 'absint' ],
                    'text_color' => [ 'sanitize_callback' => 'sanitize_text_field' ],
                    'is_folder'  => [ 'sanitize_callback' => 'absint' ],
                    'layout'     => [ 'sanitize_callback' => 'sanitize_text_field' ],
                ],
            ],
        ] );

        register_rest_route( $namespace, '/groups/(?P<id>\d+)', [
            [
                'methods'             => 'PUT',
                'callback'            => [ $this, 'update_group' ],
                'permission_callback' => [ $this, 'check_write_permission' ],
                'args'                => [
                    'id' => [ 'sanitize_callback' => 'absint' ],
                ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ $this, 'delete_group' ],
                'permission_callback' => [ $this, 'check_write_permission' ],
                'args'                => [
                    'id' => [ 'sanitize_callback' => 'absint' ],
                ],
            ],
        ] );

        register_rest_route( $namespace, '/pages', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_pages' ],
                'permission_callback' => [ $this, 'check_read_permission' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'create_page' ],
                'permission_callback' => [ $this, 'check_write_permission' ],
                'args'                => [
                    'title' => [ 'sanitize_callback' => 'sanitize_text_field' ],
                    'icon'  => [ 'sanitize_callback' => 'sanitize_text_field' ],
                ],
            ],
        ] );

        register_rest_route( $namespace, '/pages/(?P<id>\d+)', [
            [
                'methods'             => 'PUT',
                'callback'            => [ $this, 'update_page' ],
                'permission_callback' => [ $this, 'check_write_permission' ],
                'args'                => [
                    'id' => [ 'sanitize_callback' => 'absint' ],
                ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ $this, 'delete_page' ],
                'permission_callback' => [ $this, 'check_write_permission' ],
                'args'                => [
                    'id' => [ 'sanitize_callback' => 'absint' ],
                ],
            ],
        ] );

        register_rest_route( $namespace, '/pages/reorder', [
            'methods'             => 'PUT',
            'callback'            => [ $this, 'reorder_pages' ],
            'permission_callback' => [ $this, 'check_write_permission' ],
        ] );

        register_rest_route( $namespace, '/settings', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_settings' ],
                'permission_callback' => [ $this, 'check_read_permission' ],
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [ $this, 'save_settings' ],
                'permission_callback' => [ $this, 'check_write_permission' ],
            ],
        ] );

        register_rest_route( $namespace, '/dock/add/(?P<id>\d+)', [
            'methods'             => 'PUT',
            'callback'            => [ $this, 'add_to_dock' ],
            'permission_callback' => [ $this, 'check_write_permission' ],
            'args'                => [
                'id' => [ 'sanitize_callback' => 'absint' ],
            ],
        ] );

        register_rest_route( $namespace, '/dock/remove/(?P<id>\d+)', [
            'methods'             => 'PUT',
            'callback'            => [ $this, 'remove_from_dock' ],
            'permission_callback' => [ $this, 'check_write_permission' ],
            'args'                => [
                'id' => [ 'sanitize_callback' => 'absint' ],
            ],
        ] );

        register_rest_route( $namespace, '/dock/reorder', [
            'methods'             => 'PUT',
            'callback'            => [ $this, 'reorder_dock' ],
            'permission_callback' => [ $this, 'check_write_permission' ],
        ] );

        register_rest_route( $namespace, '/categories', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_categories' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( $namespace, '/sync-local', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'sync_local_data' ],
            'permission_callback' => [ $this, 'check_write_permission' ],
        ] );

        register_rest_route( $namespace, '/memo', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_memo' ],
                'permission_callback' => [ $this, 'check_write_permission' ],
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [ $this, 'save_memo' ],
                'permission_callback' => [ $this, 'check_write_permission' ],
            ],
        ] );

        register_rest_route( $namespace, '/init-user', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'init_user_data' ],
            'permission_callback' => [ $this, 'check_write_permission' ],
        ] );
    }

    public function check_write_permission() {
        return current_user_can( 'edit_posts' );
    }

    public function check_read_permission() {
        return is_user_logged_in();
    }

    public function sanitize_json( $value ) {
        if ( is_string( $value ) ) {
            $decoded = json_decode( $value, true );
            if ( json_last_error() === JSON_ERROR_NONE ) {
                return $decoded;
            }
            return [];
        }
        if ( is_array( $value ) ) {
            return $value;
        }
        return [];
    }

    public function get_init_data( WP_REST_Request $request ) {
        try {
            $wp_page_id  = $request->get_param( 'page_id' );
            $active_page = $request->get_param( 'active_page' );
            $user_id     = $this->get_current_user_id();
            $data        = $this->nav_manager->get_init_data( $wp_page_id, $active_page, $user_id );

            if ( is_wp_error( $data ) ) {
                return $data;
            }

            return rest_ensure_response( $data );
        } catch ( Exception $e ) {
            return new WP_Error( 'bm_init_error', $e->getMessage(), [ 'status' => 500 ] );
        }
    }

    public function get_nav_items( WP_REST_Request $request ) {
        $group_id = $request->get_param( 'group_id' );
        $user_id  = $this->get_current_user_id();
        $data     = $this->nav_manager->get_nav_items( $group_id, $user_id );

        if ( is_wp_error( $data ) ) {
            return $data;
        }

        return rest_ensure_response( $data );
    }

    public function create_nav_item( WP_REST_Request $request ) {
        $user_id     = $this->get_current_user_id();
        $source_type = $request->get_param( 'source_type' );
        $source_id   = $request->get_param( 'source_id' );

        if ( $source_type === 'onenav' && ! empty( $source_id ) ) {
            $params = $request->get_params();
            $result = $this->nav_manager->add_item_from_onenav( $source_id, $params, $user_id );
        } else {
            $data = [
                'user_id'          => $user_id,
                'title'            => $request->get_param( 'title' ),
                'url'              => $request->get_param( 'url' ),
                'icon'             => $request->get_param( 'icon' ),
                'describe'         => $request->get_param( 'describe' ),
                'group_id'         => $request->get_param( 'group_id' ),
                'layout'           => $request->get_param( 'layout' ),
                'bg_color'         => $request->get_param( 'bg_color' ),
                'text_icon'        => $request->get_param( 'text_icon' ),
                'open_in_iframe'   => $request->get_param( 'open_in_iframe' ),
                'component_id'     => $request->get_param( 'component_id' ),
                'component_config' => $request->get_param( 'component_config' ),
                'page_id'          => $request->get_param( 'page_id' ),
            ];
            $result = $this->nav_manager->add_item( $data );
        }

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( $result );
    }

    public function update_nav_item( WP_REST_Request $request ) {
        $id      = absint( $request['id'] );
        $user_id = $this->get_current_user_id();
        $data    = [];

        $updatable = [ 'title', 'url', 'icon', 'describe', 'group_id', 'layout', 'bg_color', 'text_icon', 'open_in_iframe', 'component_id', 'component_config' ];

        foreach ( $updatable as $field ) {
            $value = $request->get_param( $field );
            if ( $value !== null ) {
                $data[ $field ] = $value;
            }
        }

        if ( empty( $data ) ) {
            return new WP_Error( 'no_data', __( 'No data provided for update.', 'bookmark-nav' ), [ 'status' => 400 ] );
        }

        $result = $this->nav_manager->update_item( $id, $data, $user_id );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( $result );
    }

    public function delete_nav_item( WP_REST_Request $request ) {
        $id      = absint( $request['id'] );
        $user_id = $this->get_current_user_id();
        $result  = $this->nav_manager->remove_item( $id, $user_id );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( [ 'deleted' => true ] );
    }

    public function reorder_nav_items( WP_REST_Request $request ) {
        $items   = $request->get_json_params();
        $user_id = $this->get_current_user_id();

        if ( empty( $items['items'] ) || ! is_array( $items['items'] ) ) {
            return new WP_Error( 'invalid_data', __( 'Invalid reorder data.', 'bookmark-nav' ), [ 'status' => 400 ] );
        }

        $result = $this->nav_manager->reorder_items( $items['items'], $user_id );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( [ 'reordered' => true ] );
    }

    public function get_candidates( WP_REST_Request $request ) {
        $user_id = $this->get_current_user_id();
        $params  = [
            'page'     => $request->get_param( 'page' ) ?: 1,
            'per_page' => $request->get_param( 'per_page' ) ?: 20,
            'search'   => $request->get_param( 'search' ),
            'category' => $request->get_param( 'category' ),
        ];

        $data = $this->nav_manager->get_candidates( $params, $user_id );

        if ( is_wp_error( $data ) ) {
            return $data;
        }

        return rest_ensure_response( $data );
    }

    public function get_groups( WP_REST_Request $request ) {
        $user_id = $this->get_current_user_id();
        $data    = $this->nav_manager->get_groups( null, $user_id );

        if ( is_wp_error( $data ) ) {
            return $data;
        }

        return rest_ensure_response( $data );
    }

    public function create_group( WP_REST_Request $request ) {
        $user_id = $this->get_current_user_id();
        $data    = [
            'user_id'    => $user_id,
            'title'      => $request->get_param( 'title' ),
            'icon'       => $request->get_param( 'icon' ),
            'page_id'    => $request->get_param( 'page_id' ),
            'sort_order' => $request->get_param( 'sort_order' ),
            'columns'    => $request->get_param( 'columns' ),
            'icon_size'  => $request->get_param( 'icon_size' ),
            'show_text'  => $request->get_param( 'show_text' ),
            'text_color' => $request->get_param( 'text_color' ),
            'is_folder'  => $request->get_param( 'is_folder' ),
            'layout'     => $request->get_param( 'layout' ),
        ];

        $result = $this->nav_manager->add_group( $data );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( $result );
    }

    public function update_group( WP_REST_Request $request ) {
        $id      = absint( $request['id'] );
        $user_id = $this->get_current_user_id();
        $data    = [];

        $updatable = [ 'title', 'icon', 'sort_order', 'columns', 'icon_size', 'show_text', 'text_color', 'layout' ];

        foreach ( $updatable as $field ) {
            $value = $request->get_param( $field );
            if ( $value !== null ) {
                $data[ $field ] = $value;
            }
        }

        if ( empty( $data ) ) {
            return new WP_Error( 'no_data', __( 'No data provided for update.', 'bookmark-nav' ), [ 'status' => 400 ] );
        }

        $result = $this->nav_manager->update_group( $id, $data, $user_id );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( $result );
    }

    public function delete_group( WP_REST_Request $request ) {
        $id      = absint( $request['id'] );
        $user_id = $this->get_current_user_id();
        $result  = $this->nav_manager->delete_group( $id, $user_id );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( [ 'deleted' => true ] );
    }

    public function get_pages() {
        $user_id = $this->get_current_user_id();
        return rest_ensure_response( $this->nav_manager->get_pages( $user_id ) );
    }

    public function create_page( WP_REST_Request $request ) {
        $user_id = $this->get_current_user_id();
        $data    = [
            'user_id' => $user_id,
            'title'   => $request->get_param( 'title' ),
            'icon'    => $request->get_param( 'icon' ) ?: '📁',
        ];
        $id = $this->nav_manager->add_page( $data );
        return rest_ensure_response( [ 'id' => $id, 'title' => $data['title'], 'icon' => $data['icon'] ] );
    }

    public function update_page( WP_REST_Request $request ) {
        $id      = absint( $request['id'] );
        $user_id = $this->get_current_user_id();
        $data    = array_filter( [
            'title' => $request->get_param( 'title' ),
            'icon'  => $request->get_param( 'icon' ),
        ], function ( $v ) { return $v !== null; } );
        $this->nav_manager->update_page( $id, $data, $user_id );
        return rest_ensure_response( [ 'updated' => true ] );
    }

    public function delete_page( WP_REST_Request $request ) {
        $id      = absint( $request['id'] );
        $user_id = $this->get_current_user_id();
        $result  = $this->nav_manager->delete_page( $id, $user_id );
        if ( ! $result ) {
            return new WP_Error( 'cannot_delete', 'Cannot delete default page', [ 'status' => 400 ] );
        }
        return rest_ensure_response( [ 'deleted' => true ] );
    }

    public function reorder_pages( WP_REST_Request $request ) {
        $items   = $request->get_json_params();
        $user_id = $this->get_current_user_id();
        if ( empty( $items['items'] ) ) {
            return new WP_Error( 'invalid_data', 'Invalid data', [ 'status' => 400 ] );
        }
        $this->nav_manager->reorder_pages( $items['items'], $user_id );
        return rest_ensure_response( [ 'reordered' => true ] );
    }

    public function get_settings( WP_REST_Request $request ) {
        $user_id = $this->get_current_user_id();
        $data    = $this->nav_manager->get_all_config( $user_id );

        if ( is_wp_error( $data ) ) {
            return $data;
        }

        return rest_ensure_response( $data );
    }

    public function save_settings( WP_REST_Request $request ) {
        $user_id = $this->get_current_user_id();
        $data    = $request->get_json_params();

        if ( empty( $data ) || ! is_array( $data ) ) {
            return new WP_Error( 'no_data', __( 'No settings data provided.', 'bookmark-nav' ), [ 'status' => 400 ] );
        }

        $result = $this->nav_manager->save_settings( $data, $user_id );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( [ 'saved' => true ] );
    }

    public function add_to_dock( WP_REST_Request $request ) {
        $id      = absint( $request['id'] );
        $user_id = $this->get_current_user_id();
        $result  = $this->nav_manager->add_to_dock( $id, $user_id );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( $result );
    }

    public function remove_from_dock( WP_REST_Request $request ) {
        $id      = absint( $request['id'] );
        $user_id = $this->get_current_user_id();
        $result  = $this->nav_manager->remove_from_dock( $id, $user_id );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( $result );
    }

    public function reorder_dock( WP_REST_Request $request ) {
        $items   = $request->get_json_params();
        $user_id = $this->get_current_user_id();

        if ( empty( $items['items'] ) || ! is_array( $items['items'] ) ) {
            return new WP_Error( 'invalid_data', __( 'Invalid reorder data.', 'bookmark-nav' ), [ 'status' => 400 ] );
        }

        $result = $this->nav_manager->reorder_dock( $items['items'], $user_id );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( [ 'reordered' => true ] );
    }

    public function get_categories() {
        $data_source = BM_Core::get_instance()->get_data_source();
        $nav_manager = BM_Core::get_instance()->get_nav_manager();
        $terms = [];

        if ( $data_source && $data_source->is_onenav_mode() ) {
            $enabled_ids_str = $nav_manager->get_config( 'store_categories', '' );
            $enabled_ids = array_filter( array_map( 'absint', explode( ',', $enabled_ids_str ) ) );

            $query_args = [
                'taxonomy'   => $data_source->get_taxonomy_category(),
                'hide_empty' => false,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ];

            if ( ! empty( $enabled_ids ) ) {
                $query_args['include'] = $enabled_ids;
            }

            $terms = get_terms( $query_args );

            if ( is_wp_error( $terms ) ) {
                $terms = [];
            }
        }

        $result = [];
        foreach ( $terms as $term ) {
            $result[] = [
                'id'   => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'count' => $term->count,
            ];
        }

        return rest_ensure_response( $result );
    }

    public function sync_local_data( WP_REST_Request $request ) {
        $user_id = $this->get_current_user_id();
        $body    = $request->get_json_params();

        if ( empty( $user_id ) ) {
            return new WP_Error( 'not_logged_in', '需要登录才能同步', [ 'status' => 401 ] );
        }

        $bookmarks = $body['bookmarks'] ?? [];
        $memo      = $body['memo'] ?? null;
        $settings  = $body['settings'] ?? [];

        $result = $this->nav_manager->sync_local_data( $user_id, $bookmarks, $memo, $settings );

        return rest_ensure_response( $result );
    }

    public function get_memo( WP_REST_Request $request ) {
        $user_id = $this->get_current_user_id();

        if ( empty( $user_id ) ) {
            return rest_ensure_response( null );
        }

        $data = $this->nav_manager->get_memo( $user_id );

        return rest_ensure_response( $data );
    }

    public function save_memo( WP_REST_Request $request ) {
        $user_id = $this->get_current_user_id();
        $body    = $request->get_json_params();

        if ( empty( $user_id ) ) {
            return new WP_Error( 'not_logged_in', '需要登录才能保存', [ 'status' => 401 ] );
        }

        $result = $this->nav_manager->save_memo( $user_id, $body );

        if ( ! $result ) {
            return new WP_Error( 'save_failed', '保存备忘录失败', [ 'status' => 500 ] );
        }

        return rest_ensure_response( [ 'saved' => true ] );
    }

    public function init_user_data( WP_REST_Request $request ) {
        $user_id = $this->get_current_user_id();

        if ( empty( $user_id ) ) {
            return new WP_Error( 'not_logged_in', '需要登录', [ 'status' => 401 ] );
        }

        $page_id = $this->nav_manager->init_default_data( $user_id );

        return rest_ensure_response( [ 'page_id' => $page_id, 'initialized' => true ] );
    }
}
