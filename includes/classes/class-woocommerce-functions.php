<?php

namespace JJ;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * WS_Woocommerce_Functions class
 */
class Woocommerce_Functions
{
    /**
     * WS_Woocommerce_Functions constructor.
     */
    public function __construct()
    {
        $this->add_actions();
        $this->remove_actions();
        $this->add_filters();
        $this->remove_filters();
    }

    /**
     * Add action hooks.
     */
    private function add_actions()
    {
    }

    /**
     * Remove already added action hooks.
     */
    private function remove_actions()
    {
    }

    /**
     * Add filter hooks.
     */
    private function add_filters()
    {
        add_filter('product_type_selector', [$this, 'remove_extra_product_types'], 11);

        add_filter('woocommerce_product_data_tabs', [$this, 'data_panels_displays'], 10, 1);
    }
    /**
     * Remove filter hooks.
     */
    private function remove_filters()
    {
    }


    /**
     * Removes extra product types
     *
     * @param array $product_types
     *
     * @return array
     */
    public function remove_extra_product_types($product_types)
    {

        unset($product_types['grouped']);
        unset($product_types['external']);
        unset($product_types['variable']);

        return $product_types;
    }

    /**
     * Hide Attributes data panel.
     */
    function data_panels_displays($tabs)
    {

        // Other default values for 'attribute' are; general, inventory, shipping, linked_product, variations, advanced
        // $tabs['attribute']['class'][] = 'show_if_simple';
        $tabs['shipping']['class'][] = 'hide_if_simple';
        // $tabs['inventory']['class'][] = 'hide_if_simple';
        // $tabs['linked_product']['class'][] = 'hide_if_simple';
        $tabs['variations']['class'][] = 'hide_if_simple';
        $tabs['advanced']['class'][] = 'hide_if_simple';

        return $tabs;
    }

}
