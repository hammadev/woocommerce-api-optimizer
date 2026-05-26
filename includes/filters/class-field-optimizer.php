<?php
namespace ShopMobi\ApiOptimizer\Filters;

defined( 'ABSPATH' ) || exit;

/**
 * Filters WooCommerce REST API responses to only return requested fields.
 *
 * Clients can specify fields via:
 *   - Header:      X-WC-Fields: id,name,price        (include only)
 *   - Header:      X-WC-Except: meta_data,_links     (exclude)
 *   - Query param: ?fields=id,name,price
 *   - Query param: ?except_fields=meta_data
 *
 * Header takes priority over query param when both are present.
 */
class Field_Optimizer {

    public function filter_fields( $response, $object, \WP_REST_Request $request ) {
        $include = $this->resolve_param( $request, 'X-WC-Fields', 'fields' );
        $exclude = $this->resolve_param( $request, 'X-WC-Except', 'except_fields' );

        if ( empty( $include ) && empty( $exclude ) ) {
            return $response;
        }

        $data = $response->get_data();

        if ( ! empty( $include ) ) {
            $keys = array_map( 'trim', explode( ',', $include ) );
            $data = array_intersect_key( $data, array_flip( $keys ) );
        }

        if ( ! empty( $exclude ) ) {
            $keys = array_map( 'trim', explode( ',', $exclude ) );
            foreach ( $keys as $key ) {
                unset( $data[ $key ] );
            }
        }

        $response->set_data( $data );
        return $response;
    }

    private function resolve_param( \WP_REST_Request $request, string $header, string $param ): string {
        $from_header = $request->get_header( str_replace( '-', '_', strtolower( $header ) ) );
        if ( ! empty( $from_header ) ) {
            return $from_header;
        }
        return (string) ( $request->get_param( $param ) ?? '' );
    }
}
