<?php
namespace WC_API_Optz\Filters;

defined( 'ABSPATH' ) || exit;

/**
 * Replaces the raw variation IDs array with full variation objects.
 */
class Product_Response_Filter {

    public function enhance_variations( $response, $object, \WP_REST_Request $request ) {
        $variation_ids = $response->data['variations'] ?? [];

        if ( empty( $variation_ids ) || ! is_array( $variation_ids ) ) {
            return $response;
        }

        $variations = [];
        foreach ( $variation_ids as $variation_id ) {
            $variation    = new \WC_Product_Variation( $variation_id );
            $variations[] = $this->format_variation( $variation, $variation_id );
        }

        $response->data['variations'] = $variations;
        return $response;
    }

    private function format_variation( \WC_Product_Variation $variation, int $id ): array {
        $qty = $variation->get_stock_quantity();

        return [
            'id'            => $id,
            'sku'           => $variation->get_sku(),
            'on_sale'       => $variation->is_on_sale(),
            'regular_price' => (float) $variation->get_regular_price(),
            'sale_price'    => (float) $variation->get_sale_price(),
            'quantity'      => $qty ?? '',
            'stock_status'  => $variation->get_stock_status(),
            'attributes'    => $this->format_attributes( $variation ),
            'image'         => $this->format_image( $variation ),
        ];
    }

    private function format_attributes( \WC_Product_Variation $variation ): array {
        $attributes = [];
        foreach ( $variation->get_variation_attributes() as $raw_name => $option ) {
            $name = str_replace( 'attribute_', '', $raw_name );
            $attributes[] = [
                'name'   => \wc_attribute_label( $name, $variation ),
                'slug'   => str_replace( 'attribute_', '', \wc_attribute_taxonomy_slug( $name ) ),
                'option' => $option,
            ];
        }
        return $attributes;
    }

    private function format_image( \WC_Product_Variation $variation ): ?string {
        $image_id = $variation->get_image_id();
        if ( ! $image_id ) return null;

        $src = \wp_get_attachment_image_src( $image_id, 'full' );
        return $src ? $src[0] : null;
    }
}
