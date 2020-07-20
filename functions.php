<?php

namespace JJ;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('THEME_TEXT_DOMAIN', 'eladu');

require_once 'includes/classes/class-woocommerce-functions.php';
require_once 'includes/classes/class-acf-json.php';
/**
 * Class ThemeSetup
 */
class ThemeSetup
{
    /**
     * ThemeSetup constructor.
     */
    public function __construct()
    {
        $this->add_reservations_table();
        $this->remove_actions();
        $this->remove_filters();
        $this->add_actions();
        $this->add_filters();

        new Acf_Controller();
        if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')), true)) {
            new Woocommerce_Functions();
        }
        add_shortcode('jj_checkout', [$this, 'shortcode_html']);
    }

    /**
     * Add WordPress actions
     */
    private function add_actions()
    {
        add_action('after_setup_theme', [$this, 'jj_theme_setup']);
        add_action('wp_enqueue_scripts', [$this, 'jj_theme_enqueue_scripts_and_styles']);

        add_action('wp_ajax_get_makecommerce_data', [$this, 'get_makecommerce_data']);
        add_action('wp_ajax_nopriv_get_makecommerce_data', [$this, 'get_makecommerce_data']);

        add_action('wp_ajax_get_storage_locations', [$this, 'get_storage_locations']);
        add_action('wp_ajax_nopriv_get_storage_locations', [$this, 'get_storage_locations']);

        add_action('wp_ajax_get_available_extras', [$this, 'get_available_extras']);
        add_action('wp_ajax_nopriv_get_available_extras', [$this, 'get_available_extras']);

        add_action('wp_ajax_get_available_boxes', [$this, 'get_available_boxes']);
        add_action('wp_ajax_nopriv_get_available_boxes', [$this, 'get_available_boxes']);

        add_action('wp_ajax_send_verification_code', [$this, 'send_verification_code']);
        add_action('wp_ajax_nopriv_send_verification_code', [$this, 'send_verification_code']);

        add_action('wp_ajax_validate_phone', [$this, 'validate_phone']);
        add_action('wp_ajax_nopriv_validate_phone', [$this, 'validate_phone']);

        add_action('wp_ajax_jj_checkout', [$this, 'jj_checkout']);
        add_action('wp_ajax_nopriv_jj_checkout', [$this, 'jj_checkout']);

        add_action('woocommerce_add_order_item_meta', [$this, 'add_product_booking_dates_to_order'], 10, 3);

        add_action('woocommerce_order_status_processing', [$this, 'save_product_booking_dates']);
        add_action('woocommerce_order_status_cancelled', [$this, 'update_product_reservation_status']);
        // add_action('woocommerce_thankyou', [$this, 'save_product_booking_dates']);
    }

    /**
     * Remove WordPress existing filters
     */
    private function remove_filters()
    {
    }

    /**
     * Remove WordPress existing actions
     */
    private function remove_actions()
    {
    }

    /**
     * Add WordPress custom filters
     */
    private function add_filters()
    {
    }

    private function add_reservations_table()
    {
        $saved_version = (int) get_site_option('jj_reservations_table_version');
        $currentVersion = 5;

        global $wpdb;
        $wpdb->box_reservations = "{$wpdb->prefix}box_reservations";

        if ($saved_version < $currentVersion) {
            update_site_option('jj_reservations_table_version', $currentVersion);

            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE `{$wpdb->box_reservations}` (
            id MEDIUMINT NOT NULL AUTO_INCREMENT,
            product_id SMALLINT NOT NULL,
            status SMALLINT UNSIGNED NOT NULL DEFAULT 1 ,
            check_in_date datetime NOT NULL,
            check_out_date datetime NULL,
            PRIMARY KEY  (id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
    /**
     * Setup various WordPress theme variables
     */
    public function jj_theme_setup()
    {
        // Make theme available for translation.
        load_theme_textdomain('eladu', get_template_directory() . '/languages');

        add_image_size('1440p', 2560, 1440);
        add_image_size('4k', 4096, 2160);

        // Let WordPress manage the document title.
        add_theme_support('title-tag');

        // Enable support for Post Thumbnails on posts and pages.
        add_theme_support('post-thumbnails');

        // Switch default core markup for search form, comment form, and comments.
        add_theme_support(
            'html5',
            array(
                'search-form',
                'comment-form',
                'comment-list',
                'gallery',
                'caption',
            )
        );

        register_nav_menus(
            array(
                'header-menu' => esc_html__('Header', 'jj-starter-theme'),
            )
        );
    }

    /** Enqueues theme stylesheet and Javascript files.
     */
    public function jj_theme_enqueue_scripts_and_styles()
    {
        $asset_version = wp_get_theme()->get('Version');

        $parent_style = 'parent-style'; // This is 'twentyfifteen-style' for the Twenty Fifteen theme.

        wp_enqueue_style($parent_style, get_template_directory_uri() . '/style.css');
        wp_enqueue_style(
            'style-for-wordpress',
            get_stylesheet_directory_uri() . '/style.css',
            array($parent_style),
            $asset_version
        );
        wp_enqueue_style(
            'child-style',
            get_stylesheet_directory_uri() . '/assets/css/main.css',
            [],
            $asset_version
        );


        wp_enqueue_script('jj-custom-js', get_stylesheet_directory_uri() . '/assets/js/build.js', array(), $asset_version, true); // 'vue', 'vue-datepicker'
        wp_localize_script(
            'jj-custom-js',
            'php_object',
            [
                'checkoutNonce' => wp_create_nonce('woocommerce-process_checkout'),
                'ajax_url' => admin_url('admin-ajax.php'),
                'sum_template' => wc_price(0),
                'translations' => [
                    'datepicker' => self::get_datepicker_locale()
                ]
            ]
        );
    }


    public static function get_datepicker_locale()
    {
        return [
            'night' => __('päev', THEME_TEXT_DOMAIN),
            'nights' => __('päeva', THEME_TEXT_DOMAIN),
            'day-names' => [
                __('Esmap', THEME_TEXT_DOMAIN),
                __('Teisip', THEME_TEXT_DOMAIN),
                __('Kolmap', THEME_TEXT_DOMAIN),
                __('Neljap', THEME_TEXT_DOMAIN),
                __('Reede', THEME_TEXT_DOMAIN),
                __('Laup', THEME_TEXT_DOMAIN),
                __('Pühap', THEME_TEXT_DOMAIN)
            ],
            'check-in' => __('Alates', THEME_TEXT_DOMAIN),
            'check-out' => __('Kuni', THEME_TEXT_DOMAIN),
            'month-names' => [
                __('Jaanuar', THEME_TEXT_DOMAIN),
                __('Veebruar', THEME_TEXT_DOMAIN),
                __('Märts', THEME_TEXT_DOMAIN),
                __('Kolmapäev', THEME_TEXT_DOMAIN),
                __('Aprill', THEME_TEXT_DOMAIN),
                __('Mai', THEME_TEXT_DOMAIN),
                __('Juuni', THEME_TEXT_DOMAIN),
                __('Juulis', THEME_TEXT_DOMAIN),
                __('August', THEME_TEXT_DOMAIN),
                __('September', THEME_TEXT_DOMAIN),
                __('Oktoober', THEME_TEXT_DOMAIN),
                __('November', THEME_TEXT_DOMAIN),
                __('Detsember', THEME_TEXT_DOMAIN)
            ],
        ];
    }
    public function get_makecommerce_data()
    {
        $url = plugins_url('/css/makecommerce.css', \ABSPATH . 'wp-content/plugins/makecommerce/makecommerce-payment.php');
        echo "<link rel='stylesheet' href=" . $url . ">";

        // $mk = new \woocommerce_makecommerce();
        // $mk->payment_fields();
        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
?>
        <div id="payment">
            <?php if (true) : //$order->needs_payment()) :
            ?>
                <ul class="wc_payment_methods payment_methods methods">
                    <?php
                    if (!empty($available_gateways)) {
                        foreach ($available_gateways as $gateway) {
                            wc_get_template('checkout/payment-method.php', array('gateway' => $gateway));
                        }
                    } else {
                        echo '<li class="woocommerce-notice woocommerce-notice--info woocommerce-info">' . apply_filters('woocommerce_no_available_payment_methods_message', esc_html__('Sorry, it seems that there are no available payment methods for your location. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce')) . '</li>'; // @codingStandardsIgnoreLine
                    }
                    ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php
        exit;
    }


    public function get_storage_locations()
    {
        $terms = \get_terms(array(
            'taxonomy' => 'product_tag',
            'hide_empty' => true,
        ));
        if (\is_wp_error($terms)) {
            \wp_send_json_error($terms->get_error_message());
        }

        $returnValue = [];

        /**
         * @var WP_Term $term
         */
        foreach ($terms as $key => $term) {
            $returnValue[] = [
                'label' => $term->name,
                'value' => $term->slug,
                'href'  => get_field('term_maps_link', $term)
            ];
        }


        \wp_send_json_success($returnValue);
    }

    public function get_available_extras()
    {
        $products = new \WP_Query([
            'post_type' => 'product',
            'status'    => 'publish',
            'limit'     => -1,
            'tax_query' => [
                [
                    'taxonomy'        => 'product_cat',
                    'field'           => 'slug',
                    'terms'           =>  array('lisateenus'),
                    'operator'        => 'IN',
                ]
            ]

        ]);

        if (count($products->posts)) {
            foreach ($products->posts as $key => $post) {
                /**
                 * @var WC_Product $products
                 */
                $product = \wc_get_product($post);
                $returnValue[] = [
                    'label' => $product->get_name(),
                    'id' => $product->get_id(),
                    'price_html'  => $product->get_price_html(),
                    'price'  => $product->get_price(),
                    'tippy'  => get_field('tippy', $product->get_id()),
                ];
            }


            \wp_send_json_success($returnValue);
        } else {
            \wp_send_json_error(__('Lisateenuseid ei leitud', THEME_TEXT_DOMAIN));
        }
    }

    public function get_available_boxes()
    {
        $check_in_datetime = new \DateTime($_GET["checkIn"]);
        $check_in_datetime = $check_in_datetime->setTimezone(\wp_timezone());
        $check_out_datetime = new \DateTime($_GET["checkIn"]);
        $check_out_datetime = $check_out_datetime->setTimezone(\wp_timezone());

        $selectedLocation = \sanitize_text_field($_GET["location"]);

        $products = new \WP_Query([
            'post_type' => 'product',
            'status'    => 'publish',
            'posts_per_page' => -1,
            'tax_query' => [
                [
                    'taxonomy'        => 'product_tag',
                    'field'           => 'slug',
                    'terms'           =>  array($selectedLocation),
                    'operator'        => 'IN',
                ]
            ],
            'orderby'   => 'post_title'

        ]);

        // \usort($products->posts, function ($a, $b) {
        //     $a = \str_replace("Boks ", "", $a->post_title);
        //     $b = \str_replace("Boks ", "", $b->post_title);
        //     if ($a == $b) {
        //         return 0;
        //     }
        //     return ($a < $b) ? -1 : 1;
        // });

        if (count($products->posts)) {
            foreach ($products->posts as $key => $post) {

                /**
                 * @var \WC_Product $products
                 * @var \wpdb $wpdb
                 */
                $product = \wc_get_product($post);
                global $wpdb;

                $res = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM $wpdb->box_reservations WHERE `status`=1 AND product_id=%s ORDER BY id DESC LIMIT 1",
                        $product->get_id()
                    )
                );
                $can_book_box = false;

                if (empty($res)) {
                    $can_book_box = true;
                }
                //  else {
                //     $last_booking = $res[0];
                //     $last_booking_check_in_datetime = new \DateTime($last_booking->check_in_date);
                //     $last_booking_check_out_datetime = new \DateTime($last_booking->check_out_date);

                //     if ($last_booking_check_in_datetime !== $last_booking_check_out_datetime && $last_booking_check_out_datetime < $check_in_datetime) {
                //         $can_book_box = true;
                //     }
                // }


                $returnValue[] = [
                    'can_book' => $can_book_box,
                    'box_number' => \str_replace("Boks ", "", $product->get_name()),
                    'value' => $product->get_id(),
                    'name' => $product->get_name(),
                    'name_price' => $product->get_name() . ' ( $' . \number_format($product->get_price(), wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator()) . ' )',
                    'price_html' => $product->get_price_html(),
                    'price'  => $product->get_price(),
                ];

                // Old style
                // if ($can_book_box) {
                //     $returnValue[] = [
                //         'value' => $product->get_id(),
                //         'name' => $product->get_name(),
                //         'name_price' => $product->get_name() . ' ( $' . \number_format($product->get_price(), wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator()) . ' )',
                //         'price_html' => $product->get_price_html(),
                //         'price'  => $product->get_price(),
                //     ];
                // }
            }
            array_multisort(
                array_column($returnValue, 'can_book'),
                SORT_DESC,
                SORT_NUMERIC,
                array_column($returnValue, 'box_number'),
                SORT_ASC,
                SORT_NUMERIC,
                $returnValue
            );
            \wp_send_json_success($returnValue);
        } else {
            \wp_send_json_error(__('Saadaolevaid bokse ei leitud', THEME_TEXT_DOMAIN));
        }
    }

    public function shortcode_html()
    {
        \ob_start();
        require_once __DIR__ . '/tpl-checkout.php';
        return \ob_get_clean();
    }

    public function send_verification_code()
    {
        // Define recipients
        $mobile = trim(sanitize_text_field($_GET['phone_nr']));
        if ($mobile && \strpos($mobile, '+372') !== false && trim(\substr($mobile, 3)) !== "") {
            $recipients = [$mobile];
            $url = "https://gatewayapi.com/rest/mtsms";
            // $verfication_code = \substr(bin2hex(openssl_random_pseudo_bytes(16)), 0, 8);
            $verfication_code = \rand(10000000, 99999999);
            $api_token = "sC97khRmTVucZ33s5dDbc1mfYtuT-569tIYxNJxeHrH5_lz4H0yRppASO4r6gah-";
            $json = [
                'sender' => 'Eladu OÜ',
                'message' => __('Teie verifitseerimise kood on', THEME_TEXT_DOMAIN) . ': ' . $verfication_code,
                'recipients' => [],
            ];
            foreach ($recipients as $msisdn) {
                $json['recipients'][] = ['msisdn' => $msisdn];
            }
            \update_option($mobile, $verfication_code, false);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
            curl_setopt($ch, CURLOPT_USERPWD, $api_token . ":");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // $result = curl_exec($ch);
            curl_close($ch);
            // wp_send_json_success();
            wp_send_json_success($verfication_code); // For automatic filling when testing
        }
        wp_send_json_error(__('Telefoni nr-ga oli probleeme. Kontrolliga palun üle, et on olemas eesliide +372.', THEME_TEXT_DOMAIN));
    }
    public function validate_phone()
    {
        // Define recipients
        $mobile = trim(sanitize_text_field($_GET['phone_nr']));
        $code = trim(sanitize_text_field($_GET['code']));
        if ($mobile && \strpos($mobile, '+372') !== false && trim(\substr($mobile, 3)) !== "") {
            if (trim($code) !== "") {
                if (get_option($mobile, null) === $code) {
                    \wp_send_json_success();
                }
            }
            wp_send_json_error(__('Valideerimiskoodiga oli probleeme. Palun kontrolliga, et kood on sisestatud', THEME_TEXT_DOMAIN));
        }
        wp_send_json_error(__('Telefoni nr-ga oli probleeme. Kontrolliga palun üle, et on olemas eesliide +372.', THEME_TEXT_DOMAIN));
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

    public function add_product_booking_dates_to_order($item_id, $values, $cart_item_key)
    {
        if (isset($values['check_in_date'])) {
            wc_add_order_item_meta($item_id, 'Alguskuupäev', $values['check_in_date']);
        }
        if (isset($values['check_in_date'], $values['check_out_date']) && $values['check_in_date'] == $values['check_out_date']) {
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
}

new ThemeSetup();

abstract class Meta_box
{
    public static function add()
    {
        $screens = ['product'];
        foreach ($screens as $screen) {
            add_meta_box(
                'jj_box_id',          // Unique ID
                'Boksi reserveeringud', // Box title
                [self::class, 'html'],   // Content callback, must be of type callable
                $screen,                  // Post type
                'normal',
                'high'
            );
        }
    }

    public static function html($post)
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
}

add_action('add_meta_boxes', ['JJ\Meta_box', 'add']);

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
