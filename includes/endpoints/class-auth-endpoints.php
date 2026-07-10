<?php
namespace ShopMobi\ApiOptimizer\Endpoints;

defined( 'ABSPATH' ) || exit;

class Auth_Endpoints {

    private const LOGIN_ATTEMPTS_LIMIT  = 5;
    private const LOGIN_LOCKOUT_SECONDS = 300; // 5 minutes
    private const REGISTER_LIMIT        = 3;
    private const REGISTER_WINDOW       = 3600; // 1 hour

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
        return \current_user_can( 'read' );
    }

    public function login( \WP_REST_Request $request ) {
        $username = \sanitize_text_field( $request['username'] ?? '' );
        $password = $request['password'] ?? '';

        if ( empty( $username ) ) return new \WP_Error( 'username_required', 'Username is required.', [ 'status' => 422 ] );
        if ( empty( $password ) ) return new \WP_Error( 'password_required', 'Password is required.', [ 'status' => 422 ] );

        // Rate limit by username — cannot be spoofed unlike IP headers
        $user_key = 'shopmobi_ao_login_user_' . md5( $username );

        // Rate limit by server IP (REMOTE_ADDR only — cannot be set by the client)
        $ip_key = 'shopmobi_ao_login_ip_' . md5( $this->get_remote_addr() );

        if ( $this->is_rate_limited( $user_key, self::LOGIN_ATTEMPTS_LIMIT, self::LOGIN_LOCKOUT_SECONDS )
            || $this->is_rate_limited( $ip_key, self::LOGIN_ATTEMPTS_LIMIT, self::LOGIN_LOCKOUT_SECONDS ) ) {
            return new \WP_Error(
                'too_many_attempts',
                'Too many login attempts. Please try again in 5 minutes.',
                [ 'status' => 429 ]
            );
        }

        // wp_signon() goes through the WordPress authenticate filter chain,
        // so security plugins (Wordfence, Limit Login Attempts, etc.) can intercept it.
        $user = \wp_signon( [
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => true,
        ], false );

        if ( \is_wp_error( $user ) ) {
            $this->increment_rate_limit( $user_key, self::LOGIN_LOCKOUT_SECONDS );
            $this->increment_rate_limit( $ip_key, self::LOGIN_LOCKOUT_SECONDS );
            return new \WP_Error( 'login_failed', \wp_strip_all_tags( $user->get_error_message() ), [ 'status' => 403 ] );
        }

        \delete_transient( $user_key );
        \delete_transient( $ip_key );

        return new \WP_REST_Response( [
            'user' => $user,
            'meta' => $this->get_user_meta( $user->ID ),
        ], 200 );
    }

    public function register( \WP_REST_Request $request ) {
        // Rate limit by server IP only (REMOTE_ADDR — cannot be spoofed)
        $ip_key = 'shopmobi_ao_register_ip_' . md5( $this->get_remote_addr() );

        if ( $this->is_rate_limited( $ip_key, self::REGISTER_LIMIT, self::REGISTER_WINDOW ) ) {
            return new \WP_Error(
                'too_many_attempts',
                'Too many registration attempts. Please try again later.',
                [ 'status' => 429 ]
            );
        }

        $username = \sanitize_text_field( $request['username'] ?? '' );
        $email    = \sanitize_email( $request['email'] ?? '' );
        $password = $request['password'] ?? '';
        $name     = \sanitize_text_field( $request['name'] ?? '' );

        if ( empty( $username ) ) return new \WP_Error( 'username_required', 'Username is required.', [ 'status' => 422 ] );
        if ( empty( $email ) )    return new \WP_Error( 'email_required', 'Email is required.', [ 'status' => 422 ] );
        if ( empty( $password ) ) return new \WP_Error( 'password_required', 'Password is required.', [ 'status' => 422 ] );

        if ( \username_exists( $username ) || \email_exists( $email ) ) {
            return new \WP_Error( 409, 'An account with that username or email already exists.', [ 'status' => 409 ] );
        }

        // wp_create_user() is WordPress core — respects the registration_errors filter.
        $user_id = \wp_create_user( $username, $password, $email );
        if ( \is_wp_error( $user_id ) ) return $user_id;

        $this->increment_rate_limit( $ip_key, self::REGISTER_WINDOW );

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
            'message' => 'Registration successful.',
            'user'    => [
                'user' => $user,
                'meta' => $this->get_user_meta( $user_id ),
            ],
        ], 200 );
    }

    public function update_profile( \WP_REST_Request $request ) {
        $user_id    = \get_current_user_id();
        $first_name = \sanitize_text_field( $request['first_name'] ?? '' );
        $last_name  = \sanitize_text_field( $request['last_name'] ?? '' );
        $user_phone = \sanitize_text_field( $request['user_phone'] ?? '' );

        if ( empty( $first_name ) ) return new \WP_Error( 'first_name_required', 'First name is required.', [ 'status' => 422 ] );
        if ( empty( $last_name ) )  return new \WP_Error( 'last_name_required', 'Last name is required.', [ 'status' => 422 ] );
        if ( empty( $user_phone ) ) return new \WP_Error( 'user_phone_required', 'Phone is required.', [ 'status' => 422 ] );

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

    // Only REMOTE_ADDR — set by the web server, cannot be forged by the client.
    private function get_remote_addr(): string {
        return isset( $_SERVER['REMOTE_ADDR'] )
            ? \sanitize_text_field( \wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
            : '0.0.0.0';
    }

    private function is_rate_limited( string $key, int $limit, int $window ): bool {
        return (int) \get_transient( $key ) >= $limit;
    }

    private function increment_rate_limit( string $key, int $window ): void {
        $current = (int) \get_transient( $key );
        \set_transient( $key, $current + 1, $window );
    }
}
