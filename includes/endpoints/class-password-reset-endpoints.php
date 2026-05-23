<?php
namespace WC_API_Optz\Endpoints;

defined( 'ABSPATH' ) || exit;

class Password_Reset_Endpoints {

    private const CODE_TTL = 3600; // 1 hour

    public function register_routes() {
        register_rest_route( 'wp/v2', '/users/reset-password/generate', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'generate' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( 'wp/v2', '/users/reset-password/verify', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'verify' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function generate( \WP_REST_Request $request ) {
        $user = get_user_by( 'login', sanitize_text_field( $request['username'] ) );

        if ( ! $user ) {
            return new \WP_Error( 'user_not_found', 'User not found.', [ 'status' => 404 ] );
        }

        $code = str_pad( mt_rand( 0, 9999 ), 4, '0', STR_PAD_LEFT );
        update_user_meta( $user->ID, 'reset_code',            $code );
        update_user_meta( $user->ID, 'reset_code_expiration', time() + self::CODE_TTL );

        wp_mail( $user->user_email, 'Password Reset Code', "Your password reset code is: {$code}" );

        return [ 'status' => true, 'message' => 'Reset code sent to email.' ];
    }

    public function verify( \WP_REST_Request $request ) {
        $user = get_user_by( 'login', sanitize_text_field( $request['username'] ) );

        if ( ! $user ) {
            return new \WP_Error( 'user_not_found', 'User not found.', [ 'status' => 404 ] );
        }

        $stored_code = get_user_meta( $user->ID, 'reset_code', true );
        $expiry      = (int) get_user_meta( $user->ID, 'reset_code_expiration', true );

        if ( empty( $stored_code ) || $expiry < time() ) {
            return new \WP_Error( 'invalid_reset_code', 'Reset code expired or invalid.', [ 'status' => 400 ] );
        }

        if ( sanitize_text_field( $request['reset_code'] ) !== $stored_code ) {
            return new \WP_Error( 'invalid_reset_code', 'Invalid reset code.', [ 'status' => 400 ] );
        }

        wp_set_password( $request['new_password'], $user->ID );
        delete_user_meta( $user->ID, 'reset_code' );
        delete_user_meta( $user->ID, 'reset_code_expiration' );

        return [ 'status' => true, 'message' => 'Password updated successfully.' ];
    }
}
