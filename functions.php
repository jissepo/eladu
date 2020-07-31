<?php

namespace JJ;

use WP_Query;

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
    private $api_key = "sC97khRmTVucZ33s5dDbc1mfYtuT-569tIYxNJxeHrH5_lz4H0yRppASO4r6gah-";
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
        add_shortcode('jj-get-free-boxes-count', [$this, 'get_free_boxes_count']);
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

        // add_action('wp_ajax_get_available_extras', [$this, 'get_available_extras']);
        // add_action('wp_ajax_nopriv_get_available_extras', [$this, 'get_available_extras']);

        add_action('wp_ajax_get_available_countries', [$this, 'get_available_countries']);
        add_action('wp_ajax_nopriv_get_available_countries', [$this, 'get_available_countries']);

        add_action('wp_ajax_get_available_boxes', [$this, 'get_available_boxes']);
        add_action('wp_ajax_nopriv_get_available_boxes', [$this, 'get_available_boxes']);

        add_action('wp_ajax_send_verification_code', [$this, 'send_verification_code']);
        add_action('wp_ajax_nopriv_send_verification_code', [$this, 'send_verification_code']);

        add_action('wp_ajax_validate_phone', [$this, 'validate_phone']);
        add_action('wp_ajax_nopriv_validate_phone', [$this, 'validate_phone']);

        add_action('admin_post_send_cron_keys', [$this, 'send_cron_keys']);
        add_action('admin_post_nopriv_send_cron_keys', [$this, 'send_cron_keys']);
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
        $asset_version = '1.0.7';

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
                'label' => $term->name . " ({$this->get_free_boxes_count(['tag_id' =>$term->term_id])})",
                'value' => $term->slug,
                'href'  => \get_field('term_maps_link', $term),
                'image'  => \get_field('storage_layout', $term),
                'extras'  => $this->get_available_extras(get_field('jj_product_connected_extras', $term)),
            ];
        }


        \wp_send_json_success($returnValue);
    }

    public function get_available_extras($product_ids)
    {

        if (count($product_ids)) {
            $returnValue = [];
            foreach ($product_ids as $key => $post_id) {
                /**
                 * @var WC_Product $products
                 */
                $product = \wc_get_product($post_id);
                /**
                 * @var \wpdb $wpdb
                 */
                global $wpdb;
                $connected_products = $wpdb->get_col(
                    $wpdb->prepare(
                        "SELECT post_id FROM {$wpdb->postmeta}
                            WHERE meta_key = 'jj_product_connected_extras' AND
                            meta_value LIKE %s",
                        "%\"{$product->get_id()}\"%"
                    )
                );

                $returnValue[] = [
                    'label' => $product->get_name(),
                    'id' => $product->get_id(),
                    'price_html'  => $product->get_price_html(),
                    'price'  => $product->get_price(),
                    'tippy'  => get_field('tippy', $product->get_id()),
                    'connected_products' => $connected_products,
                ];
            }


            return $returnValue;
        } else {
            return [];
        }
    }


    public function get_available_countries()
    {
        $countries_obj   = new \WC_Countries();
        $returnArray = [];

        foreach (WC()->countries->get_allowed_countries() as $code => $label) {
            $returnArray[] = [
                'code'  => $code,
                'label' => $label
            ];
        }
        \wp_send_json_success($returnArray);
    }

    public function get_available_boxes()
    {
        // $check_in_datetime = new \DateTime($_GET["checkIn"]);
        // $check_in_datetime = $check_in_datetime->setTimezone(\wp_timezone());
        // $check_out_datetime = new \DateTime($_GET["checkOut"]);
        // $check_out_datetime = $check_out_datetime->setTimezone(\wp_timezone());

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

    public function get_free_boxes_count($atts)
    {
        global $wpdb;

        $rows = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT
                    p.ID
                FROM {$wpdb->posts} p
                JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID AND tr.term_taxonomy_id = %s
                WHERE p.ID NOT IN (SELECT product_id from {$wpdb->box_reservations} WHERE status = 1)",
                $atts['tag_id']
            )
        );

        return count($rows);
    }

    public function send_verification_code()
    {
        // Define recipients
        $mobile = trim(sanitize_text_field($_GET['phone_nr']));
        if ($mobile && \strpos($mobile, '+372') !== false && \strlen(\trim($mobile)) > strlen('+372')) {
            $recipients = [$mobile];
            $url = "https://gatewayapi.com/rest/mtsms";
            // $verfication_code = \substr(bin2hex(openssl_random_pseudo_bytes(16)), 0, 8);
            $verfication_code = \rand(10000000, 99999999);
            $api_token = $this->api_key;
            $json = [
                'sender' => 'Eladu OÜ',
                'message' => __('Teie verifitseerimise kood on', THEME_TEXT_DOMAIN) . ': ' . $verfication_code,
                'recipients' => [],
            ];
            foreach ($recipients as $msisdn) {
                $json['recipients'][] = ['msisdn' => $msisdn];
            }
            \update_option(\esc_attr($mobile), $verfication_code, false);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
            curl_setopt($ch, CURLOPT_USERPWD, $api_token . ":");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            curl_close($ch);
            wp_send_json_success(['message' => __('Teie telefoni nr on saadetud sõnum valideerimis koodiga.', THEME_TEXT_DOMAIN)]);
            wp_send_json_success(['message' => __('Teie telefoni nr on saadetud sõnum valideerimis koodiga.', THEME_TEXT_DOMAIN), 'verify' => $verfication_code]);
            // wp_send_json_success($verfication_code); // For automatic filling when testing
        }
        if (\strlen(\trim($mobile)) <= strlen('+372')) {
            wp_send_json_error(__('Palun sisestage korrektne telefoni nr.', THEME_TEXT_DOMAIN));
            # code...
        }
        wp_send_json_error(__('Telefoni nr-ga oli probleeme. Kontrolliga palun üle, et on olemas eesliide +372.', THEME_TEXT_DOMAIN));
    }

    public function send_cron_keys()
    {
        $keys_cron = get_option('jj_keys_cron', []);
        foreach ($keys_cron as $timestamp => $boxes) {
            if ($timestamp > time()) {
                break;
            }
            foreach ($boxes as $key => $value) {
                $user_id = $value['user_id'];
                $product_id = $value['box_id'];

                $recipients = [\get_user_meta($user_id, 'billing_phone', true)];
                $url = "https://gatewayapi.com/rest/mtsms";
                $api_token = $this->api_key;
                $json = [
                    'sender' => 'Eladu OÜ',
                    'message' => __('Teie boksi kood on', THEME_TEXT_DOMAIN) . ': ' . \get_post_meta($product_id, 'jj_current_door_key', true),
                    'recipients' => [],
                ];
                foreach ($recipients as $msisdn) {
                    $json['recipients'][] = ['msisdn' => $msisdn];
                }
                $result = "";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
                curl_setopt($ch, CURLOPT_USERPWD, $api_token . ":");
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $result = curl_exec($ch);
                curl_close($ch);
                $decoded = json_decode($result, TRUE);
                if (isset($decoded['ids']) && !empty($decoded['ids'])) {
                    unset($boxes[$key]);
                }
            }
            if (empty($boxes)) {
                unset($keys_cron[$timestamp]);
            }
        }
        update_option('jj_keys_cron', $keys_cron, false);
    }

    public function validate_phone()
    {
        // Define recipients
        $mobile = trim(sanitize_text_field($_GET['phone_nr']));
        $code = trim(sanitize_text_field($_GET['code']));
        if ($mobile && \strpos($mobile, '+372') !== false && \strlen(\trim($mobile)) > strlen('+372')) {
            if (trim($code) !== "") {
                if (get_option(\esc_attr($mobile), null) === $code) {
                    \wp_send_json_success();
                }
                wp_send_json_error(__('Valideerimiskood on ebakorrektne. Palun proovige uuesti', THEME_TEXT_DOMAIN));
            }
            wp_send_json_error(__('Valideerimiskoodiga oli probleeme. Palun kontrolliga, et kood on sisestatud', THEME_TEXT_DOMAIN));
        }
        if (\strlen(\trim($mobile)) <= strlen('+372')) {
            wp_send_json_error(__('Palun sisestage korrektne telefoni nr.', THEME_TEXT_DOMAIN));
        }
        wp_send_json_error(__('Telefoni nr-ga oli probleeme. Kontrolliga palun üle, et on olemas eesliide +372.', THEME_TEXT_DOMAIN));
    }
}

new ThemeSetup();
