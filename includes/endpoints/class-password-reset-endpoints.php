<?php
namespace ShopMobi\ApiOptimizer\Endpoints;

defined( 'ABSPATH' ) || exit;

class Password_Reset_Endpoints {

    public function register_routes() {
        \register_rest_route( 'shopmobi/v1', '/users/reset-password/generate', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'generate' ],
            'permission_callback' => '__return_true',
        ] );

        \register_rest_route( 'shopmobi/v1', '/users/reset-password/verify', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'verify' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function generate( \WP_REST_Request $request ) {
        $username = \sanitize_text_field( $request['username'] ?? '' );

        if ( empty( $username ) ) {
            return new \WP_Error( 'missing_username', 'Username is required.', [ 'status' => 400 ] );
        }

        $user = \get_user_by( 'login', $username );
        if ( ! $user ) {
            $user = \get_user_by( 'email', $username );
        }

        // Generic message to avoid username enumeration
        if ( ! $user ) {
            return [ 'status' => true, 'message' => 'If that account exists, a reset key has been sent.' ];
        }

        // WordPress's own secure reset key — security plugins can intercept this hook
        $reset_key = \get_password_reset_key( $user );

        if ( \is_wp_error( $reset_key ) ) {
            return new \WP_Error( 'reset_key_error', 'Could not generate reset key.', [ 'status' => 500 ] );
        }

        $subject  = 'Password Reset — ' . \get_bloginfo( 'name' );
        $message  = "Hi {$user->display_name},\n\n";
        $message .= "Use the key below in the app to reset your password:\n\n";
        $message .= "Key: {$reset_key}\n\n";
        $message .= "This key expires in 24 hours. If you did not request a reset, ignore this email.";

        \wp_mail( $user->user_email, $subject, $message );

        return [ 'status' => true, 'message' => 'If that account exists, a reset key has been sent.' ];
    }

    public function verify( \WP_REST_Request $request ) {
        $username     = \sanitize_text_field( $request['username'] ?? '' );
        $key          = \sanitize_text_field( $request['key'] ?? '' );
        $new_password = $request['new_password'] ?? '';

        if ( empty( $username ) || empty( $key ) || empty( $new_password ) ) {
            return new \WP_Error( 'missing_params', 'username, key, and new_password are required.', [ 'status' => 400 ] );
        }

        // WordPress's own key validation — honours expiry and marks key as used
        $user = \check_password_reset_key( $key, $username );

        if ( \is_wp_error( $user ) ) {
            return new \WP_Error( 'invalid_key', 'The reset key is invalid or has expired.', [ 'status' => 400 ] );
        }

        // WordPress's own reset function — fires hooks that security plugins listen to
        \reset_password( $user, $new_password );

        return [ 'status' => true, 'message' => 'Password updated successfully.' ];
    }
}
