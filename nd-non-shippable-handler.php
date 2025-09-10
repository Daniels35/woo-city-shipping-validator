<?php
/*
Plugin Name: ND - Gestión de productos no enviables
Description: Elimina productos del carrito según la ciudad del cliente y los vuelve a agregar automáticamente si cambia a una ciudad con cobertura. Muestra mensaje en el checkout.
Version: 1.5
Author: Daniel Díaz
*/

if (!defined('ABSPATH')) exit;

/**
 * Slug de la categoría objetivo (no enviable)
 */
function nd_get_target_category_slug() {
    return 'combo-que-empiece-la-parranda';
}

/**
 * Ciudades con cobertura
 */
function nd_get_coverage_cities_for_plugin() {
    return array(
        'BELLO (ANT) (05088000)', 'MEDELLIN (ANT) (05001000)', 'ENVIGADO (ANT) (05266000)',
        'ITAGUI (ANT) (05360000)', 'SABANETA (ANT) (05631000)', 'COPACABANA (ANT) (05212000)',
        'GIRARDOTA (ANT) (05308000)', 'CALDAS (ANT) (05129000)', 'LA ESTRELLA (ANT) (05380000)'
    );
}

/**
 * Hook AJAX que se dispara al actualizar el checkout.
 * Valida la ciudad y gestiona los productos.
 *
 * @param string $post_data Los datos del formulario de checkout.
 */
add_action('woocommerce_checkout_update_order_review', 'nd_validate_city_on_checkout_update');
function nd_validate_city_on_checkout_update($post_data) {
    parse_str($post_data, $checkout_data);

    if (!isset($checkout_data['billing_city']) || !WC()->cart) {
        return;
    }

    $city = sanitize_text_field($checkout_data['billing_city']);
    $target_cat = nd_get_target_category_slug();
    $allowed_cities = nd_get_coverage_cities_for_plugin();

    $city_normalized = mb_strtoupper(trim($city));
    $allowed_upper = array_map(function($c) { return mb_strtoupper(trim($c)); }, $allowed_cities);

    $removed_products_from_session = WC()->session->get('nd_removed_products', []);

    // LÓGICA DE ELIMINACIÓN: Si la ciudad NO tiene cobertura
    if (!in_array($city_normalized, $allowed_upper)) {
        $products_to_remove_now = [];
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if (has_term($target_cat, 'product_cat', $cart_item['product_id'])) {
                // Guardamos en un array temporal los que vamos a quitar
                $products_to_remove_now[$cart_item['product_id']] = $cart_item['quantity'];
                WC()->cart->remove_cart_item($cart_item_key);
            }
        }
        // Si acabamos de quitar productos, los guardamos en la sesión
        if (!empty($products_to_remove_now)) {
            WC()->session->set('nd_removed_products', $products_to_remove_now);
        }
    } 
    // LÓGICA DE RE-AGREGAR: Si la ciudad SÍ tiene cobertura y hay productos guardados en la sesión
    else if (!empty($removed_products_from_session)) {
        foreach ($removed_products_from_session as $product_id => $qty) {
            WC()->cart->add_to_cart($product_id, $qty);
        }
        
        // **LA CORRECCIÓN CLAVE ESTÁ AQUÍ**
        // Limpiamos la sesión de forma definitiva para que el mensaje desaparezca.
        WC()->session->__unset('nd_removed_products');
        
        // Opcional: Notificación de éxito
        if (!wc_has_notice('¡Buenas noticias! Hemos vuelto a agregar los productos a tu carrito porque tu ciudad tiene cobertura.', 'success')) {
             wc_add_notice('¡Buenas noticias! Hemos vuelto a agregar los productos a tu carrito porque tu ciudad tiene cobertura.', 'success');
        }
    }
}


/**
 * Mostrar mensaje en el checkout sobre productos eliminados.
 * (Esta función no necesita cambios)
 */
add_action('woocommerce_review_order_before_order_total', 'nd_show_removed_products_message');
function nd_show_removed_products_message() {
    if (WC()->session) {
        $removed_products = WC()->session->get('nd_removed_products');

        if (!empty($removed_products)) {
            echo '<tr class="nd-removed-products-notice"><td colspan="2" style="padding-top: 1em;">';
            echo '<p style="color: #c0392b; font-style: italic;">Los siguientes productos fueron eliminados porque no están disponibles para tu ciudad:</p>';
            echo '<ul style="list-style-type: none; margin: 0; padding-left: 10px; color: #c0392b;">';
            foreach ($removed_products as $product_id => $qty) {
                $product = wc_get_product($product_id);
                if ($product) {
                    echo '<li>&ndash; ' . esc_html($product->get_name()) . '</li>';
                }
            }
            echo '</ul></td></tr>';
        }
    }
}

/**
 * Limpiar la sesión si el carrito se vacía manualmente.
 * (Esta función no necesita cambios)
 */
add_action('woocommerce_cart_emptied', 'nd_clear_removed_products_session');
function nd_clear_removed_products_session(){
    if (WC()->session) {
        WC()->session->__unset('nd_removed_products');
    }
}