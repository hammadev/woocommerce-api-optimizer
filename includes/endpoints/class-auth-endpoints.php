<?php
namespace ShopMobi\ApiOptimizer\Endpoints;

defined( 'ABSPATH' ) || exit;

class Auth_Endpoints {

    public function register_routes() {
        \register_rest_route( 'shopmobi/v1', '/users/login', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'login' ],
            'permission_callback' => '__return_true',
        ] );

        \register_rest_route( 'shopmobi/v1', '/users/register', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'register' ],
            'permission_callback' => '__return_true',
        ] );

        \register_rest_route( 'shopmobi/v1', '/users/update-profile', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'update_profile' ],
            'permission_callback' => [ $this, 'is_logged_in' ],
        ] );
    }

    public function is_logged_in(): bool {
        return \is_user_logged_in();
    }

    public function login( \WP_REST_Request $request ) {
        $user = \wp_signon( [
            'user_login'    => \sanitize_text_field( $request['username'] ),
            'user_password' => $request['password'],
            'remember'      => true,
        ], false );

        if ( \is_wp_error( $user ) ) {
            return new \WP_Error( 403, \wp_strip_all_tags( $user->get_error_message() ), [ 'status' => 403 ] );
        }

        return new \WP_REST_Response( [
            'user' => $user,
            'meta' => $this->get_user_meta( $user->ID ),
        ], 200 );
    }

    public function register( \WP_REST_Request $request ) {
        $username = \sanitize_text_field( $request['username'] ?? '' );
        $email    = \sanitize_email( $request['email'] ?? '' );
        $password = $request['password'] ?? '';
        $name     = \sanitize_text_field( $request['name'] ?? '' );

        if ( empty( $username ) ) return new \WP_Error( 403, "Username is required.", [ 'status' => 403 ] );
        if ( empty( $email ) )    return new \WP_Error( 403, "Email is required.", [ 'status' => 403 ] );
        if ( empty( $password ) ) return new \WP_Error( 403, "Password is required.", [ 'status' => 403 ] );

        if ( \username_exists( $username ) || \email_exists( $email ) ) {
            return new \WP_Error( 406, "Email already exists.", [ 'status' => 406 ] );
        }

        $user_id = \wp_create_user( $username, $password, $email );
        if ( \is_wp_error( $user_id ) ) return $user_id;

        if ( ! empty( $name ) ) {
            $parts = explode( ' ', $name, 2 );
            \update_user_meta( $user_id, 'first_name', $parts[0] );
            \update_user_meta( $user_id, 'last_name', $parts[1] ?? '' );
            \update_user_meta( $user_id, 'nickname', $username );
        }

        $user = \get_user_by( 'id', $user_id );
        $user->set_role( 'customer' );

        return new \WP_REST_Response( [
            'code'    => 200,
            'message' => "User '{$username}' registered successfully.",
            'user'    => [
                'user' => $user,
                'meta' => $this->get_user_meta( $user_id ),
            ],
        ], 200 );
    }

    public function update_profile( \WP_REST_Request $request ) {
        $user_id    = \absint( $request['user_id'] ?? 0 );
        $first_name = \sanitize_text_field( $request['first_name'] ?? '' );
        $last_name  = \sanitize_text_field( $request['last_name'] ?? '' );
        $user_phone = \sanitize_text_field( $request['user_phone'] ?? '' );

        if ( empty( $first_name ) ) return new \WP_Error( 403, "First name is required.", [ 'status' => 403 ] );
        if ( empty( $last_name ) )  return new \WP_Error( 403, "Last name is required.", [ 'status' => 403 ] );
        if ( empty( $user_phone ) ) return new \WP_Error( 403, "Phone is required.", [ 'status' => 403 ] );

        \update_user_meta( $user_id, 'first_name', $first_name );
        \update_user_meta( $user_id, 'last_name',  $last_name );
        \update_user_meta( $user_id, 'user_phone', $user_phone );

        return new \WP_REST_Response( [
            'message' => 'Profile updated successfully.',
            'user'    => [
                'user' => \get_userdata( $user_id ),
                'meta' => $this->get_user_meta( $user_id ),
            ],
        ], 200 );
    }

    private function get_user_meta( int $user_id ): array {
        return [
            'first_name' => \get_user_meta( $user_id, 'first_name', true ),
            'last_name'  => \get_user_meta( $user_id, 'last_name', true ),
            'nickname'   => \get_user_meta( $user_id, 'nickname', true ),
            'user_phone' => \get_user_meta( $user_id, 'user_phone', true ),
        ];
    }
}
