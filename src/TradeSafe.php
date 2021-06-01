<?php


class TradeSafe
{
    public static function init()
    {
        // Actions
        add_action('admin_init', ['TradeSafe', 'settings_api_init']);
        add_action('admin_menu', ['TradeSafe', 'register_options_page']);

        add_action('woocommerce_cart_calculate_fees', ['TradeSafe', 'add_gateway_fee'], PHP_INT_MAX);
        add_action('woocommerce_order_status_completed', ['TradeSafe', 'complete_transaction'], PHP_INT_MAX);
        add_action('woocommerce_review_order_before_payment', ['TradeSafe', 'refresh_checkout']);
        add_action('admin_notices', ['TradeSafe', 'seller_account_incomplete_notice'], -10000, 0);
        add_action('dokan_dashboard_content_inside_before', ['TradeSafe', 'seller_account_incomplete_notice']);
//        add_action('woocommerce_before_checkout_form', ['TradeSafe', 'seller_account_incomplete_notice']);
        add_action('woocommerce_review_order_before_payment', ['TradeSafe', 'buyer_account_incomplete_notice']);

        // Disable publish for standard woocommerce products
        add_action('admin_head', ['TradeSafe', 'disable_publish_button']);

        if (has_dokan()) {
            // Disable add new product button when using dokan
            add_action('wp_head', ['TradeSafe', 'disable_add_product_button']);
        }

        add_filter('woocommerce_my_account_my_orders_actions', ['TradeSafe', 'accept_order'], 10, 2);
        add_filter('woocommerce_available_payment_gateways', ['TradeSafe', 'availability'], 10, 2);

        add_filter('woocommerce_checkout_fields', ['TradeSafe', 'checkout_field_defaults'], 20);

        add_rewrite_rule('^tradesafe/eft-details/([0-9]+)[/]?$', 'index.php?tradesafe=eft-details&order-id=$matches[1]', 'top');
        add_rewrite_rule('^tradesafe/accept/([0-9]+)[/]?$', 'index.php?tradesafe=accept&order-id=$matches[1]', 'top');
        add_rewrite_rule('^tradesafe/callback$', 'index.php?tradesafe=callback', 'top');
        add_rewrite_rule('^tradesafe/unlink?$', 'index.php?tradesafe=unlink', 'top');
        add_action('parse_request', ['TradeSafe', 'parse_request']);

        add_rewrite_endpoint('tradesafe', EP_PAGES);

        add_filter('query_vars', function ($query_vars) {
            $query_vars[] = 'tradesafe';
            $query_vars[] = 'order-id';

            return $query_vars;
        });
    }

    /**
     * Settings Page
     */
    public static function settings_api_init()
    {
        add_settings_section(
            'tradesafe_info_section',
            'Callback URL\'s',
            [
                'TradeSafe',
                'settings_info_callback'
            ],
            'tradesafe'
        );

        add_settings_section(
            'tradesafe_settings_section',
            'Application Settings',
            [
                'TradeSafe',
                'settings_application_callback'
            ],
            'tradesafe'
        );

        add_settings_field(
            'tradesafe_client_id',
            'Client ID',
            [
                'TradeSafe',
                'setting_client_id_callback'
            ],
            'tradesafe',
            'tradesafe_settings_section'
        );
        register_setting('tradesafe', 'tradesafe_client_id');

        add_settings_field(
            'tradesafe_client_secret',
            'Client Secret',
            [
                'TradeSafe',
                'setting_client_secret_callback'
            ],
            'tradesafe',
            'tradesafe_settings_section'
        );
        register_setting('tradesafe', 'tradesafe_client_secret');

        add_settings_field(
            'tradesafe_production_mode',
            'Production Mode',
            [
                'TradeSafe',
                'setting_production_mode_callback'
            ],
            'tradesafe',
            'tradesafe_settings_section'
        );
        register_setting('tradesafe', 'tradesafe_production_mode');

        add_settings_section(
            'tradesafe_transaction_section',
            'Transaction Settings',
            [
                'TradeSafe',
                'settings_transaction_callback'
            ],
            'tradesafe'
        );

        add_settings_field(
            'tradesafe_transaction_industry',
            'Industry',
            [
                'TradeSafe',
                'setting_transaction_industry_callback'
            ],
            'tradesafe',
            'tradesafe_transaction_section'
        );
        register_setting('tradesafe', 'tradesafe_transaction_industry');

        add_settings_field(
            'tradesafe_fee_allocation',
            'TradeSafe Fee Allocation',
            [
                'TradeSafe',
                'setting_tradesafe_fee_allocation_callback'
            ],
            'tradesafe',
            'tradesafe_transaction_section'
        );
        register_setting('tradesafe', 'tradesafe_fee_allocation');

        add_settings_field(
            'tradesafe_gateway_fee_allocation',
            'Payment Gateway Fee Allocation',
            [
                'TradeSafe',
                'setting_tradesafe_gateway_fee_allocation_callback'
            ],
            'tradesafe',
            'tradesafe_transaction_section'
        );
        register_setting('tradesafe', 'tradesafe_gateway_fee_allocation');

        if (has_dokan()) {
            add_settings_field(
                'tradesafe_payout_fee',
                'Payout Fee',
                [
                    'TradeSafe',
                    'setting_payout_fee_dokan_callback'
                ],
                'tradesafe',
                'tradesafe_transaction_section'
            );
            add_settings_field(
                'tradesafe_transaction_fee',
                'Marketplace Fee',
                [
                    'TradeSafe',
                    'setting_transaction_fee_dokan_callback'
                ],
                'tradesafe',
                'tradesafe_transaction_section'
            );

            add_settings_field(
                'tradesafe_transaction_fee_type',
                'Marketplace Fee Type',
                [
                    'TradeSafe',
                    'setting_transaction_fee_type_dokan_callback'
                ],
                'tradesafe',
                'tradesafe_transaction_section'
            );

            add_settings_field(
                'tradesafe_transaction_fee_allocation',
                'Marketplace Fee Allocation',
                [
                    'TradeSafe',
                    'setting_transaction_fee_allocation_dokan_callback'
                ],
                'tradesafe',
                'tradesafe_transaction_section'
            );
        } else {
            add_settings_field(
                'tradesafe_transaction_marketplace',
                'Is this website a Marketplace?',
                [
                    'TradeSafe',
                    'setting_transaction_agent_callback'
                ],
                'tradesafe',
                'tradesafe_transaction_section'
            );
            register_setting('tradesafe', 'tradesafe_transaction_marketplace');

            add_settings_field(
                'tradesafe_transaction_fee',
                'Marketplace Fee',
                [
                    'TradeSafe',
                    'setting_transaction_fee_callback'
                ],
                'tradesafe',
                'tradesafe_transaction_section'
            );

            add_settings_field(
                'tradesafe_transaction_fee_type',
                'Marketplace Fee Type',
                [
                    'TradeSafe',
                    'setting_transaction_fee_type_callback'
                ],
                'tradesafe',
                'tradesafe_transaction_section'
            );

            add_settings_field(
                'tradesafe_transaction_fee_allocation',
                'Marketplace Fee Allocation',
                [
                    'TradeSafe',
                    'setting_transaction_fee_allocation_callback'
                ],
                'tradesafe',
                'tradesafe_transaction_section'
            );
        }

        register_setting('tradesafe', 'tradesafe_transaction_fee');
        register_setting('tradesafe', 'tradesafe_transaction_fee_type');
        register_setting('tradesafe', 'tradesafe_transaction_fee_allocation');
    }

    private static function is_valid_token($role): bool
    {
        $client = woocommerce_tradesafe_api();
        $user = wp_get_current_user();
        $meta_key = 'tradesafe_token_id';
        $valid = false;

        if (get_option('tradesafe_production_mode')) {
            $meta_key = 'tradesafe_prod_token_id';
        }

        $tokenId = get_user_meta($user->ID, $meta_key, true);

        if ($tokenId) {
            $tokenData = $client->getToken($tokenId);

            switch ($role) {
                case 'seller':
                    if (isset($tokenData['bankAccount']['accountNumber']) && $tokenData['bankAccount']['accountNumber'] !== '') {
                        $valid = true;
                    }
                    break;
                case 'buyer':
                    if (isset($tokenData['user']['idNumber']) && $tokenData['user']['idNumber'] !== '') {
                        $valid = true;
                    }
                    break;
            }
        }

        return $valid;
    }

    public static function settings_info_callback()
    {
        $urls = [
            'oauth_callback' => site_url('/tradesafe/oauth/callback/'),
            'callback' => site_url('/tradesafe/callback/'),
            'success' => site_url('my-orders/'),
            'failure' => wc_get_endpoint_url('orders', '', get_permalink(get_option('woocommerce_myaccount_page_id'))),
        ];

        echo '<p>The following URL\'s can be used to register your application with TradeSafe.</p>';
        echo '<table class="form-table">
        <tbody>
        <tr>
            <th scope="row">OAuth Callback URL</th>
            <td>' . esc_attr($urls['oauth_callback']) . '</td>
        </tr>
        <tr>
            <th scope="row">API Callback URL</th>
            <td>' . esc_attr($urls['callback']) . '</td>
        </tr>
        <tr>
            <th scope="row">Success URL</th>
            <td>' . esc_attr($urls['success']) . '</td>
        </tr>
        <tr>
            <th scope="row">Failure URL</th>
            <td>' . esc_attr($urls['failure']) . '</td>
        </tr>
        </tbody>
    </table>';
    }

    public static function settings_application_callback()
    {
        if (get_option('tradesafe_client_id') && get_option('tradesafe_client_secret')) {
            $client = woocommerce_tradesafe_api();

            if (is_null($client)) {
                echo "<table class='form-table' role='presentation'><tbody>";
                echo "<tr><th scope='row'>Error:</th><td> Could not connect to server</td></tr>";
                echo "</tbody></table>";
                return;
            }

            if (is_array($client) && isset($client['error'])) {
                echo "<table class='form-table' role='presentation'><tbody>";
                echo "<tr><th scope='row'>Error:</th><td> Could not connect to server</td></tr>";
                echo "<tr><th scope='row'>Reason:</th><td> " . $client['error'] . "</td></tr>";
                echo "</tbody></table>";
                return;
            }

            try {
                $profile = $client->getProfile();
                $tokenData = $client->getToken($profile['token']);

                echo "<table class='form-table' role='presentation'><tbody>";
                echo "<tr><th scope='row'>Organization Name:</th><td>" . esc_attr($tokenData['organization']['name']) . "</td></tr>";
                echo "<tr><th scope='row'>Registration Number:</th><td>" . esc_attr($tokenData['organization']['registration']) . "</td></tr>";
                if ($tokenData['organization']['taxNumber']) {
                    echo "<tr><th scope='row'>Tax Number:</th><td>" . esc_attr($tokenData['organization']['taxNumber']) . "</td></tr>";
                }
                echo "<tr><th scope='row'>Name:</th><td>" . esc_attr($tokenData['user']['givenName']) . " " . esc_attr($tokenData['user']['familyName']) . "</td></tr>";
                echo "<tr><th scope='row'>Email:</th><td>" . esc_attr($tokenData['user']['email']) . "</td></tr>";
                echo "<tr><th scope='row'>Mobile:</th><td>" . esc_attr($tokenData['user']['mobile']) . "</td></tr>";
                echo "</tbody></table>";
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                echo "<table class='form-table' role='presentation'><tbody>";
                echo "<tr><th scope='row'>Error:</th><td> Could not connect to server</td></tr>";
                echo "<tr><th scope='row'>Code:</th><td> " . $e->getCode() . "</td></tr>";
                echo "</tbody></table>";
                return;
            } catch (Exception $e) {
                echo "<table class='form-table' role='presentation'><tbody>";
                echo "<tr><th scope='row'>Error:</th><td> " . $e->getMessage() . "</td></tr>";
                echo "</tbody></table>";
                return;
            }
        }
    }

    public static function settings_transaction_callback()
    {
        //
    }

    public static function setting_client_id_callback()
    {
        echo '<input name="tradesafe_client_id" id="tradesafe_client_id" type="text" value="' . get_option('tradesafe_client_id') . '" class="regular-text ltr" />';
    }

    public static function setting_client_secret_callback()
    {
        echo '<input name="tradesafe_client_secret" id="tradesafe_client_secret" type="password" value="' . get_option('tradesafe_client_secret') . '" class="regular-text ltr" />';
    }

    public static function setting_production_mode_callback()
    {
        echo '<input name="tradesafe_production_mode" id="tradesafe_production_mode" type="checkbox" value="1" ' . checked(1, get_option('tradesafe_production_mode'), false) . ' />';
    }

    public static function setting_transaction_industry_callback()
    {
        $client = woocommerce_tradesafe_api();

        try {
            $industries = $client->getEnums('Industry');
        } catch (Exception $e) {
            $industries = [[
                'name' => 'GENERAL_GOODS_SERVICES',
                'description' => 'General Goods & Services'
            ]];
        }

        echo '<select name="tradesafe_transaction_industry" class="small-text ltr">';

        foreach ($industries as $industry) {
            echo '<option ' . (get_option('tradesafe_transaction_industry', 'GENERAL_GOODS_SERVICES') === $industry['name'] ? 'selected' : '') . ' value="' . $industry['name'] . '">' . $industry['description'] . '</option>';
        }

        echo '</select>';
    }

    public static function setting_tradesafe_fee_allocation_callback()
    {
        echo '<select name="tradesafe_fee_allocation" class="small-text ltr">';
        echo '<option ' . (get_option('tradesafe_fee_allocation', 'SELLER') === 'seller' ? 'selected' : '') . ' value="SELLER">Seller / Marketplace</option>';
        echo '<option ' . (get_option('tradesafe_fee_allocation') === 'BUYER' ? 'selected' : '') . ' value="BUYER">Buyer</option>';
        echo '</select>';
    }

    public static function setting_tradesafe_gateway_fee_allocation_callback()
    {
        echo '<select name="tradesafe_gateway_fee_allocation" class="small-text ltr">';
        echo '<option ' . (get_option('tradesafe_gateway_fee_allocation', 'SELLER') === 'seller' ? 'selected' : '') . ' value="SELLER">Seller / Marketplace</option>';
        echo '<option ' . (get_option('tradesafe_gateway_fee_allocation') === 'BUYER' ? 'selected' : '') . ' value="BUYER">Buyer</option>';
        echo '</select>';
    }

    public static function setting_transaction_agent_callback()
    {
        echo '<input name="tradesafe_transaction_marketplace" id="tradesafe_transaction_marketplace" type="checkbox" value="1" ' . checked(1, get_option('tradesafe_transaction_marketplace'), false) . ' />';
    }

    public static function setting_transaction_fee_callback()
    {
        echo '<input name="tradesafe_transaction_fee" id="tradesafe_transaction_fee" type="number" value="' . get_option('tradesafe_transaction_fee') . '" class="small-text ltr" />';
    }

    public static function setting_transaction_fee_type_callback()
    {
        echo '<select name="tradesafe_transaction_fee_type" class="small-text ltr">';
        echo '<option ' . (get_option('tradesafe_transaction_fee_type') === 'PERCENT' ? 'selected' : '') . ' value="PERCENT">Percent</option>';
        echo '<option ' . (get_option('tradesafe_transaction_fee_type') === 'FIXED' ? 'selected' : '') . ' value="FIXED">Fixed</option>';
        echo '</select>';
    }

    public static function setting_transaction_fee_allocation_callback()
    {
        echo '<select name="tradesafe_transaction_fee_allocation" class="small-text ltr">';
        echo '<option ' . (get_option('tradesafe_transaction_fee_allocation', 'SELLER') === 'seller' ? 'selected' : '') . ' value="SELLER">Vendor</option>';
        echo '<option ' . (get_option('tradesafe_transaction_fee_allocation') === 'BUYER' ? 'selected' : '') . ' value="BUYER">Buyer</option>';
        echo '</select>';
    }

    public static function setting_payout_fee_dokan_callback()
    {
        echo '<select name="tradesafe_payout_fee" class="small-text ltr">';
        echo '<option ' . (get_option('tradesafe_payout_fee', 'SELLER') === 'seller' ? 'selected' : '') . ' value="SELLER">Marketplace</option>';
        echo '<option ' . (get_option('tradesafe_payout_fee') === 'BUYER' ? 'selected' : '') . ' value="BUYER">Buyer</option>';
        echo '<option ' . (get_option('tradesafe_payout_fee') === 'VENDOR' ? 'selected' : '') . ' value="VENDOR">Vendor</option>';
        echo '</select>';
    }

    public static function setting_transaction_fee_dokan_callback()
    {
        echo dokan_get_option('admin_percentage', 'dokan_selling', 0)
            . ' (<a href="' . admin_url('admin.php?page=dokan#/settings') . '">Change</a>)';
    }

    public static function setting_transaction_fee_type_dokan_callback()
    {
        echo ucwords(dokan_get_option('commission_type', 'dokan_selling', 'percentage'))
            . ' (<a href="' . admin_url('admin.php?page=dokan#/settings') . '">Change</a>)';
    }

    public static function setting_transaction_fee_allocation_dokan_callback()
    {
        echo 'Vendor';
    }

    // Add the link to the settings menu
    public static function register_options_page()
    {
        add_menu_page(
            __('TradeSafe', 'woocommerce-gateway-tradesafe'),
            __('TradeSafe', 'woocommerce-gateway-tradesafe'),
            'manage_options',
            'tradesafe',
            [
                'TradeSafe',
                'settings_page',
            ],
            'dashicons-admin-settings',
            58
        );
    }

    // Display settings page
    public static function settings_page()
    {
        include_once __DIR__ . '/../partials/settings.php';
    }


    public static function parse_request($wp)
    {
        if (array_key_exists('tradesafe', $wp->query_vars)) {
            switch ($wp->query_vars['tradesafe']) {
                case "callback":
                    $data = json_decode(file_get_contents('php://input'), true);

                    if (is_null($data)) {
                        wp_die('No Data', 'An Error Occurred While Processing Callback', [
                            'code' => 400
                        ]);
                    }

                    $signature = $data['signature'];
                    unset($data['signature']);

                    $request = '';
                    foreach ($data as $value) {
                        $request .= $value;
                    }

                    $signatureCheck = hash_hmac('sha256', $request, get_option('tradesafe_client_id'));

                    if ($signature === $signatureCheck) {
                        $query = wc_get_orders(array(
                            'meta_key' => 'tradesafe_transaction_id',
                            'meta_value' => $data['id'],
                            'meta_compare' => '=',
                        ));

                        if (!isset($query[0])) {
                            wp_die('Invalid Transaction ID', 'An Error Occurred While Processing Callback', [
                                'code' => 400
                            ]);
                        }

                        $order = $query[0];

                        if ($data['state'] === 'FUNDS_DEPOSITED') {
                            $order->update_status('on-hold', __('Awaiting Manual EFT payment.', 'woocommerce-gateway-tradesafe'));
                        }

                        if (($order->has_status('on-hold') || $order->has_status('pending')) && $data['state'] === 'FUNDS_RECEIVED') {
                            $client = woocommerce_tradesafe_api();

                            $transaction = $client->getTransaction($order->get_meta('tradesafe_transaction_id', true));
                            $client->allocationStartDelivery($transaction['allocations'][0]['id']);

                            $order->update_status('processing', 'Funds have been received by TradeSafe.');
                        }

                        exit;
                    } else {
                        wp_die('Invalid Signature', 'An Error Occurred While Processing Callback', [
                            'code' => 400
                        ]);
                    }
                case "eft-details":
                    self::eft_details_page($wp->query_vars['order-id']);
                    break;
                case "accept":
                    $order = wc_get_order($wp->query_vars['order-id']);
                    $order->update_status('completed', 'Transaction Completed. Paying out funds to parties.');
                    wp_redirect(wc_get_endpoint_url('orders', '', get_permalink(get_option('woocommerce_myaccount_page_id'))));
                    break;
                case "unlink":
                    $user = wp_get_current_user();

                    $meta_key = 'tradesafe_token_id';

                    if (get_option('tradesafe_production_mode')) {
                        $meta_key = 'tradesafe_prod_token_id';
                    }

                    delete_user_meta($user->ID, $meta_key);
                    wp_redirect(wc_get_endpoint_url('edit-account', '', get_permalink(get_option('woocommerce_myaccount_page_id'))));
                    break;
                default:
                    status_header(404);
                    include get_query_template('404');
                    exit;
            }
        }
    }

    public static function add_gateway_fee()
    {
        if (is_admin() && !defined('DOING_AJAX')) return;

        $client = woocommerce_tradesafe_api();

        $totals = WC()->cart->get_totals();

        $baseValue = $totals['subtotal']
            + $totals['shipping_total']
            - $totals['discount_total']
            + $totals['fee_total'];

        foreach (WC()->cart->get_taxes() as $tax) {
            $baseValue += $tax;
        }

        $calculation = $client->getCalculation($baseValue, get_option('tradesafe_fee_allocation'), get_option('tradesafe_transaction_industry'));

        if (get_option('tradesafe_transaction_fee_allocation') === 'BUYER') {
            $fee = 0;

            switch (get_option('tradesafe_transaction_fee_type')) {
                case 'FIXED':
                    $fee = get_option('tradesafe_transaction_fee');
                    break;
                case 'PERCENTAGE':
                    $fee = $baseValue * (get_option('tradesafe_transaction_fee') / 100);
                    break;
            }

            WC()->cart->add_fee('Marketplace Fee', $fee, false);
        }

        if (get_option('tradesafe_fee_allocation') === 'BUYER') {
            WC()->cart->add_fee('Escrow Fee', $calculation['processingFeeTotal'], false);
        }

        // Getting current chosen payment gateway
        $chosen_payment_method = false;
        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
        if (isset(WC()->session->chosen_payment_method)) {
            $chosen_payment_method = WC()->session->chosen_payment_method;
        } elseif (!empty($_REQUEST['payment_method'])) {
            $chosen_payment_method = sanitize_key($_REQUEST['payment_method']);
        } elseif ('' != ($default_gateway = get_option('woocommerce_default_gateway'))) {
            $chosen_payment_method = $default_gateway;
        } elseif (!empty($available_gateways)) {
            $chosen_payment_method = current(array_keys($available_gateways));
        }
        if (!isset($available_gateways[$chosen_payment_method])) {
            $chosen_payment_method = false;
        }
    }

    public static function refresh_checkout()
    {
        ?>
        <script type="text/javascript">
            (function ($) {
                $('form.checkout').on('change', 'input[name^="payment_method"]', function () {
                    $('body').trigger('update_checkout');
                });
            })(jQuery);
        </script>
        <?php
    }

    public static function accept_order($actions, $order)
    {
        if ($order->has_status('processing')) {
            $action_slug = 'tradesafe_accept';

            $actions[$action_slug] = array(
                'url' => home_url('/tradesafe/accept/' . $order->get_order_number()),
                'name' => 'Accept',
            );
        }

        return $actions;
    }

    public static function complete_transaction($order_id)
    {
        $client = woocommerce_tradesafe_api();
        $order = wc_get_order($order_id);

        try {
            $transaction = $client->getTransaction($order->get_meta('tradesafe_transaction_id', true));
            $client->allocationAcceptDelivery($transaction['allocations'][0]['id']);
        } catch (\Exception $e) {
            $order->set_status('processing', 'Error occurred while completing transaction on TradeSafe.');
            wp_die($e->getMessage(), 'Error occurred while completing transaction on TradeSafe', [
                'code' => 400
            ]);
        }
    }

    public static function availability($available_gateways)
    {
        if (is_admin()) {
            return $available_gateways;
        }

        if (isset($_GET['key'])) {
            $order_id = wc_get_order_id_by_order_key($_GET['key']);
            $order = wc_get_order($order_id);

            if ($order->get_total() < 50) {
                unset($available_gateways['tradesafe']);
            }
        }

        if (WC()->cart->total !== 0 && WC()->cart->total < 50) {
            unset($available_gateways['tradesafe']);
        }

        return $available_gateways;
    }

    public static function seller_account_incomplete_notice()
    {
        $validAccount = self::is_valid_token('seller');

        if ($validAccount === false) {
            $class = 'notice notice-warning';
            $title = __('Your account is incomplete!', 'woocommerce-gateway-tradesafe');
            $message = __('Our payment service provider is TradeSafe Escrow. TradeSafe keeps the funds safe in the middle and will release the funds to you once delivery is completed successfully. Sellers are guaranteed payment.', 'woocommerce-gateway-tradesafe');
            $more = __('TradeSafe forces HTTPS for all services using TLS (SSL) including their public website and the Application. All bank account details are encrypted with AES-256. Decryption keys are stored on separate machines from the application. In English, your details are encrypted with the highest industry-specific standards (which can be found in most banks), making your information confidential, secure, and safe.', 'woocommerce-gateway-tradesafe');

            printf('<div class="%1$s"><h3>%2$s</h3><p>%3$s</p><p>%4$s</p><p><a href="%5$s" class="button-secondary button alt button-large button-next">Update Account</a></p></div>', esc_attr($class), esc_html($title), esc_html($message), esc_html($more), wc_get_endpoint_url('edit-account', '', get_permalink(get_option('woocommerce_myaccount_page_id'))));
        }
    }

    public static function buyer_account_incomplete_notice()
    {
        $validAccount = self::is_valid_token('buyer');

        if ($validAccount === false) {
            $class = 'notice notice-warning';
            $title = __('Your account is incomplete!', 'woocommerce-gateway-tradesafe');
            $message = __('You may receive a message below that there are no available payment providers as your user account is incomplete. Please click on the button below to update your account to access additional payment methods. Once done, you will be able to proceed with checkout.', 'woocommerce-gateway-tradesafe');

            printf('<div class="%1$s"><h3>%2$s</h3><p>%3$s</p><p><a href="%4$s" class="button-secondary button alt button-large button-next">Update Account</a></p></div>', esc_attr($class), esc_html($title), esc_html($message), wc_get_endpoint_url('edit-account', '', get_permalink(get_option('woocommerce_myaccount_page_id'))));
        }
    }

    public static function checkout_field_defaults($fields): array
    {
        $client = woocommerce_tradesafe_api();
        $user = wp_get_current_user();

        $meta_key = 'tradesafe_token_id';

        if (get_option('tradesafe_production_mode')) {
            $meta_key = 'tradesafe_prod_token_id';
        }

        $tokenId = get_user_meta($user->ID, $meta_key, true);

        if ($tokenId) {
            $tokenData = $client->getToken($tokenId);

            if (isset($tokenData['user']['mobile']) && $tokenData['user']['mobile'] !== '') {
                $fields['billing']['billing_phone']['placeholder'] = $tokenData['user']['mobile'];
                $fields['billing']['billing_phone']['default'] = $tokenData['user']['mobile'];
            }
        }

        return $fields;
    }

    public static function disable_publish_button()
    {
        $validAccount = self::is_valid_token('seller');

        if ($validAccount) {
            return;
        }

        ?>
        <script type="text/javascript">
            window.onload = function () {
                document.getElementById('publish').disabled = true;
            }
        </script>
        <?php
    }

    public static function disable_add_product_button()
    {
        if (str_contains($_SERVER['REQUEST_URI'], 'dashboard/products')) {
            $validAccount = self::is_valid_token('seller');

            if ($validAccount) {
                return;
            }

            ?>
            <script type="text/javascript">
                window.onload = function () {
                    let buttons = document.getElementsByClassName('dokan-add-new-product');

                    Array.prototype.forEach.call(buttons, function(el) {
                        el.style.visibility = 'hidden'
                    });
                }
            </script>
            <?php
        }
    }
}