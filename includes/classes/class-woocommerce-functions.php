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

        add_action('woocommerce_add_order_item_meta', [$this, 'add_product_booking_dates_to_order'], 10, 3);

        add_action('woocommerce_order_status_processing', [$this, 'save_product_booking_dates']);
        add_action('woocommerce_order_status_cancelled', [$this, 'update_product_reservation_status']);
        add_action('woocommerce_order_status_completed', [$this, 'update_product_reservation_status_completed']);

        add_action('wp_ajax_jj_checkout', [$this, 'jj_checkout']);
        add_action('wp_ajax_nopriv_jj_checkout', [$this, 'jj_checkout']);

        add_action('admin_menu', [$this, 'add_boxes_reservations_sub_page'], 9999);
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

    public function add_boxes_reservations_sub_page()
    {
        add_submenu_page('edit.php?post_type=product', 'Bokside broneeringud', 'Bokside broneeringud', 'edit_products', 'boxes_reservations', [$this, 'display_boxes_reservations'], 9999);
    }


    public function display_boxes_reservations()
    {
        /**
         * @var \wpdb $wpdb
         */
        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    br.check_in_date,
                    br.check_out_date,
                    p.post_title,
                    t.name
                FROM {$wpdb->box_reservations} br
                JOIN {$wpdb->posts} p ON br.product_id = p.ID
                JOIN {$wpdb->term_relationships} tr ON tr.object_id = br.product_id
                JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_id AND tt.taxonomy = 'product_tag'
                JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
                WHERE `status`=1 AND (YEAR(check_out_date)>=YEAR(NOW()) OR check_out_date IS NULL)
                ORDER BY br.id desc",
            )
        );
        $locations = \array_unique(\array_map(function ($row) {
            return $row->name;
        }, $rows));
?>
        <div class="wrap">
            <h1 class="">Bokside broneeringud</h1>
            <?php foreach ($locations as $location) : ?>
                <table>
                    <thead>
                        <tr>
                            <th>Boksi number</th>
                            <th>Asukoht</th>
                            <th>Check in</th>
                            <th>Check out</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row) : if($location !== $row->name) continue;?>
                            <tr>
                                <td><?= $row->post_title; ?></td>
                                <td><?= $row->name; ?></td>
                                <td><?= $row->check_in_date; ?></td>
                                <td><?= $row->check_out_date; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>

        </div>

        <!-- 1. Link Vue Javascript -->
        <!-- <script src='https://unpkg.com/vue/dist/vue.js'></script>

        <script src='https://unpkg.com/v-calendar'></script>

        <script>
            new Vue({
                el: '#app',
                data: {
                    mode: 'single',
                    selectedDate: null
                },
                computed: {
                    attributes() {
                        let reservations = JSON.parse('<?= \json_encode($rows); ?>');
                        if (reservations.length) {
                            const locations = [...new Set(reservations.map(obj => obj.name))]
                            console.log(locations);
                            const themes = ['blue', 'red', 'purple', 'green', 'yellow'];
                            let iterator = 0;
                            return reservations.map((reservation, index) => {
                                iterator = iterator > themes.length ? 0 : iterator
                                return {
                                    key: 'reserved_' + reservation.order_id,
                                    highlight: themes[iterator++],
                                    dates: [{
                                        start: new Date(reservation.check_in_date),
                                        end: reservation.check_out_date === null ? null : new Date(reservation.check_out_date)
                                    }]
                                }
                            });
                            return locations.map((location, index) => {
                                iterator = iterator > themes.length ? 0 : iterator
                                // debugger
                                return {
                                    key: 'reserved_' + location.replace(' ', '_'),
                                    highlight: themes[iterator++],
                                    dates: reservations.map(reservation => {
                                        if (reservation.name === location) {
                                            return {
                                                start: new Date(reservation.check_in_date),
                                                end: reservation.check_out_date === null ? null : new Date(reservation.check_out_date)
                                            }
                                        }
                                    }).filter(reservation => reservation !== undefined)
                                }
                            });
                        }
                        return [];
                    }
                }
            })
        </script> -->
    <?php
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

    public function add_product_booking_dates_to_order($item_id, $values, $cart_item_key)
    {
        if (isset($values['check_in_date'])) {
            wc_add_order_item_meta($item_id, 'Alguskuupäev', $values['check_in_date']);
        }
        if (isset($values['check_in_date'], $values['check_out_date']) && $values['check_in_date'] !== $values['check_out_date']) {
            wc_add_order_item_meta($item_id, 'Lõppkuupäev', $values['check_out_date']);
        }
    }

    public function save_product_booking_dates($order_id)
    {
        /**
         * @var \wpdb $wpdb
         */
        global $wpdb;
        $order = \wc_get_order($order_id);

        $order_items = $order->get_items();
        if (!empty($order_items)) {

            /**
             * @var \WC_Order_Item_Product $order_line_item
             */
            foreach ($order_items as $key => $order_line_item) {
                $product_id = $order_line_item->get_product_id();
                $check_in_date = $order_line_item->get_meta('Alguskuupäev');
                $check_out_date = $order_line_item->get_meta('Lõppkuupäev');

                // Todo check if correct on end date less
                if (!empty($check_in_date) && !empty($check_out_date)) {
                    $wpdb->insert(
                        $wpdb->box_reservations,
                        [
                            'product_id'    => $product_id,
                            'order_id'      => $order_id,
                            'check_in_date' => $check_in_date,
                            'check_out_date' => $check_in_date === $check_out_date ? null : $check_out_date,
                        ]
                    );

                    $keys_cron = get_option('jj_keys_cron', []);

                    $time = strtotime('-36 hours', strtotime($check_in_date));
                    if (isset($keys_cron[$time])) {
                        $keys_cron[$time][] = [
                            'user_id' => $order->get_user_id(),
                            'box_id'  => $product_id
                        ];
                    } else {
                        $keys_cron[$time] = [[
                            'user_id' => $order->get_user_id(),
                            'box_id'  => $product_id
                        ]];
                    }

                    \update_option('jj_keys_cron', $keys_cron, false);
                }
            }
        }
    }

    public function update_product_reservation_status($order_id)
    {
        /** @var \wpdb $wpdb */
        global $wpdb;

        $wpdb->update($wpdb->box_reservations, ['status' => 0], ['order_id' => $order_id]);
    }

    public function update_product_reservation_status_completed($order_id)
    {
        /** @var \wpdb $wpdb */
        global $wpdb;

        $wpdb->update($wpdb->box_reservations, ['status' => 2], ['order_id' => $order_id]);
    }

    public function jj_checkout()
    {
        $check_in_date = \strtotime(sanitize_text_field($_POST["checkIn"])) + \DAY_IN_SECONDS;
        $check_out_date = \strtotime(sanitize_text_field($_POST["checkOut"])) + \DAY_IN_SECONDS;
        $selected_location = sanitize_text_field($_POST["location"]);
        $selected_box_id = sanitize_text_field($_POST["box"]);
        $selected_extras = $_POST["selected_extras"];
        $_POST["billing_postcode"] = 13245;

        WC()->cart->empty_cart();

        $terms = \get_the_terms($selected_box_id, 'product_tag');
        if (\is_wp_error($terms) || $terms === false) {
            \wp_send_json_error(__('Valitud boksiga ei ole seotud asukohta. Palun vali uus boks või võta ühendust adminiga.', THEME_TEXT_DOMAIN));
        }
        /**
         * @var WP_Term $term
         */
        $foundMatchingTerm = \false;
        foreach ($terms as $key => $term) {
            if ($term->slug === $selected_location) {
                $foundMatchingTerm = true;
            }
        }
        if (!$foundMatchingTerm) {
            \wp_send_json_error(__('Valitud boks ei sobi kokku valitud asukohaga. Palun vali uus boks või võta ühendust adminiga.', THEME_TEXT_DOMAIN));
        }

        $check_in_datetime = new \DateTime("@$check_in_date");
        $check_out_datetime = new \DateTime("@$check_out_date");

        WC()->cart->add_to_cart($selected_box_id, 1, 0, [], [
            'check_in_date' => $check_in_datetime->format('Y-m-d'),
            'check_out_date' => $check_out_datetime->format('Y-m-d'),
        ]);
        if (!empty($selected_extras)) {
            foreach ($selected_extras as $key => &$extra) {
                $extra = sanitize_text_field($extra);
                WC()->cart->add_to_cart($extra);
            }
        }
        \wc_maybe_define_constant('WOOCOMMERCE_CHECKOUT', true);
        WC()->checkout()->process_checkout();
    }
}

abstract class Meta_box
{
    public static function add()
    {
        $screens = ['product'];
        foreach ($screens as $screen) {
            add_meta_box(
                'jj_box_reservations',          // Unique ID
                'Boksi reserveeringud', // Box title
                [self::class, 'html_reservations'],   // Content callback, must be of type callable
                $screen,                  // Post type
                'normal',
                'high'
            );
            add_meta_box(
                'jj_box_key',          // Unique ID
                'Boksi info', // Box title
                [self::class, 'html_info'],   // Content callback, must be of type callable
                $screen,                  // Post type
                'normal',
                'high'
            );
        }
    }
    /**
     * When the post is saved, saves our custom data.
     *
     * @param int $post_id
     */
    function save_box_key($post_id)
    {
        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check the user's permissions.
        if (isset($_POST['post_type']) && 'product' == $_POST['post_type']) {

            if (!current_user_can('edit_page', $post_id)) {
                return;
            }
        } else {
            return;
        }

        /* OK, it's safe for us to save the data now. */

        // Make sure that it is set.
        if (!isset($_POST['current_key'])) {
            return;
        }

        // Sanitize user input.
        $current_key = sanitize_text_field($_POST['current_key']);

        // Update the meta field in the database.
        $previous_keys = \get_post_meta($post_id, 'jj_previous_door_keys', true);
        if (!is_array($previous_keys)) {
            $previous_keys = [];
        }
        if (!isset($previous_keys[count($previous_keys) - 1]['code']) || $previous_keys[count($previous_keys) - 1]['code'] !== $current_key) {
            $previous_keys[] = [
                'code'  => $current_key,
                'datetime'  => current_time('Y-m-d H:i:s'),
                'user'  => \wp_get_current_user()->display_name,
            ];
            update_post_meta($post_id, 'jj_current_door_key', $current_key);
            update_post_meta($post_id, 'jj_previous_door_keys', $previous_keys);
        }
    }

    public static function html_reservations($post)
    {
        /**
         * @var \wpdb $wpdb
         */
        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->box_reservations} WHERE product_id=%s AND `status`=1 AND (check_out_date>NOW() OR check_out_date IS NULL)",
                $post->ID
            )
        );
    ?>
        <script>
            // window.datepicker_locale = JSON.parse('<?= \json_encode(ThemeSetup::get_datepicker_locale()); ?>');
        </script>
        <div id='app'>
            <v-date-picker :mode='mode' v-model='selectedDate' is-expanded :columns="$screens({ default: 1, lg: 2 })" :rows="$screens({ default: 1, lg: 2 })" is-inline :attributes='attributes' />
        </div>

        <!-- 1. Link Vue Javascript -->
        <script src='https://unpkg.com/vue/dist/vue.js'></script>

        <!-- 2. Link VCalendar Javascript (Plugin automatically installed) -->
        <script src='https://unpkg.com/v-calendar'></script>

        <!--3. Create the Vue instance-->
        <script>
            new Vue({
                el: '#app',
                data: {
                    mode: 'single',
                    selectedDate: null
                },
                computed: {
                    attributes() {
                        let reservations = JSON.parse('<?= \json_encode($rows); ?>');
                        console.log(reservations);
                        if (reservations.length) {

                            const themes = ['blue', 'red', 'purple', 'green', 'yellow'];
                            let iterator = 0;
                            return reservations.map((reservation, index) => {
                                iterator = iterator > themes.length ? 0 : iterator
                                return {
                                    key: 'reserved_' + reservation.order_id,
                                    highlight: themes[iterator++],
                                    dates: [{
                                        start: new Date(reservation.check_in_date),
                                        end: reservation.check_out_date === null ? null : new Date(reservation.check_out_date)
                                    }]
                                }
                            });
                        }
                        return [];
                    }
                }
            })
        </script>
    <?php
    }
    public static function html_info($post)
    {
        /**
         * @var \wpdb $wpdb
         */
        global $wpdb;
        $current_key = \get_post_meta($post->ID, 'jj_current_door_key', true);
        $previous_keys = \get_post_meta($post->ID, 'jj_previous_door_keys', true);
        if (!is_array($previous_keys)) {
            $previous_keys = [];
        }

        /**
         * TODO: Fix query to not display on extras
         */
    ?>
        <style>
            .previous_keys {
                max-height: 150px;
                overflow: auto;
            }
        </style>

        <div class="container">
            <div class="current_key"><label for="current_key"><?php _e('Praegune võti', THEME_TEXT_DOMAIN); ?><input type="text" value="<?= $current_key; ?>" name="current_key" id="current_key"></label></div>
            <div class="previous_keys">
                <table>
                    <thead>
                        <tr>
                            <th>Kood</th>
                            <th>Muutmise aeg</th>
                            <th>Muutja</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (\array_reverse($previous_keys) as $key) : ?>
                            <tr>
                                <td><?= $key['code']; ?></td>
                                <td><?= $key['datetime']; ?></td>
                                <td><?= $key['user']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
<?php
    }
}

add_action('add_meta_boxes', ['JJ\Meta_box', 'add']);

add_action('save_post', ['JJ\Meta_box', 'save_box_key']);

if (isset($_GET['generate_products'])) {
    add_action('init', function () {
        $terms = \get_terms([
            'taxonomy' => 'product_tag',
            'hide_empty' => false,
        ]);

        /**
         * @var WP_Term $term
         */
        foreach ($terms as $key => $term) {
            for ($i = 0; $i < 20; $i++) {
                $item = array(
                    'Name' => 'Boks ' . $i,
                    'Description' => '',
                    'SKU' => \substr($term->slug, 0, 3) . '-' . $i,
                );
                $user_id = get_current_user(); // this has NO SENSE AT ALL, because wp_insert_post uses current user as default value
                // $user_id = $some_user_id_we_need_to_use; // So, user is selected..
                $post_id = wp_insert_post(array(
                    'post_author' => $user_id,
                    'post_title' => $item['Name'],
                    'post_content' => $item['Description'],
                    'post_status' => 'publish',
                    'post_type' => "product",
                ));
                wp_set_object_terms($post_id, 'simple', 'product_type');
                wp_set_object_terms($post_id, 'boks', 'product_cat');
                wp_set_object_terms($post_id, $term->slug, 'product_tag');
                update_post_meta($post_id, '_visibility', 'visible');
                update_post_meta($post_id, '_stock_status', 'instock');
                update_post_meta($post_id, 'total_sales', '0');
                update_post_meta($post_id, '_downloadable', 'no');
                update_post_meta($post_id, '_virtual', 'yes');
                update_post_meta($post_id, '_purchase_note', '');
                update_post_meta($post_id, '_featured', 'no');
                update_post_meta($post_id, '_weight', '');
                update_post_meta($post_id, '_length', '');
                update_post_meta($post_id, '_width', '');
                update_post_meta($post_id, '_height', '');
                update_post_meta($post_id, '_sku', $item['SKU']);
                update_post_meta($post_id, '_product_attributes', array());
                update_post_meta($post_id, '_sale_price_dates_from', '');
                update_post_meta($post_id, '_sale_price_dates_to', '');
                update_post_meta($post_id, '_sold_individually', '');
                update_post_meta($post_id, '_manage_stock', 'no');
                update_post_meta($post_id, '_backorders', 'no');
                update_post_meta($post_id, '_stock', '');

                $product = \wc_get_product($post_id);

                $product->set_regular_price(rand(25, 45));
                if (rand(1, 10) > 5) {
                    $product->set_sale_price(rand(15, 25));
                }
                $product->save();
            }
        }
    });
}

if (isset($_GET['remove_products'])) {
    add_action('init', function () {
        $products = new \WP_Query([
            'post_type' => 'product',
            'status'    => 'publish',
            'posts_per_page' => -1,
            'tax_query' => [
                [
                    'taxonomy'        => 'product_cat',
                    'field'           => 'slug',
                    'terms'           =>  array('boks'),
                    'operator'        => 'IN',
                ]
            ]

        ]);

        /**
         * @var WP_Term $term
         */
        foreach ($products->posts as $key => $post) {
            wh_deleteProduct($post->ID, true);
        }
    });
}

/**
 * Method to delete Woo Product
 *
 * @param int $id the product ID.
 * @param bool $force true to permanently delete product, false to move to trash.
 * @return \WP_Error|boolean
 */
function wh_deleteProduct($id, $force = FALSE)
{
    $product = wc_get_product($id);

    if (empty($product))
        return new \WP_Error(999, sprintf(__('No %s is associated with #%d', 'woocommerce'), 'product', $id));

    // If we're forcing, then delete permanently.
    if ($force) {
        if ($product->is_type('variable')) {
            foreach ($product->get_children() as $child_id) {
                $child = wc_get_product($child_id);
                $child->delete(true);
            }
        } elseif ($product->is_type('grouped')) {
            foreach ($product->get_children() as $child_id) {
                $child = wc_get_product($child_id);
                $child->set_parent_id(0);
                $child->save();
            }
        }

        $product->delete(true);
        $result = $product->get_id() > 0 ? false : true;
    } else {
        $product->delete();
        $result = 'trash' === $product->get_status();
    }

    if (!$result) {
        return new \WP_Error(999, sprintf(__('This %s cannot be deleted', 'woocommerce'), 'product'));
    }

    // Delete parent product transients.
    if ($parent_id = wp_get_post_parent_id($id)) {
        wc_delete_product_transients($parent_id);
    }
    return true;
}
