<?php

class Woocommerce_Myaccount_Page
{

    /**
     * The instance of the class
     *
     * @var self
     */
    protected static $instance;

    /**
     * Ensures only one instance of the class is loaded or can be loaded.
     *
     * @return self
     */
    public static function init()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();

            /* add | Remove | Rename my account page menus */
            add_filter('woocommerce_account_menu_items', [self::$instance, 'woocommerce_account_menu_items']);

            /* add content to the custom menu option */
            add_filter('woocommerce_get_endpoint_url', [self::$instance, 'woocommerce_hook_endpoint'], 10, 4);

        }

        return self::$instance;
    }


    /**
     * Woocommerce My Accounts Page menu Adding Rename and removing
     *
     * @param $menu_links
     *
     * @return mixed
     */
    public function woocommerce_account_menu_items($menu_links)
    {


        /* unset menu links  */
        unset($menu_links['edit-address']); // Addresses

        //unset( $menu_links['dashboard'] ); // Remove Dashboard
        //unset( $menu_links['payment-methods'] ); // Remove Payment Methods
        //unset( $menu_links['orders'] ); // Remove Orders
        //unset( $menu_links['downloads'] ); // Disable Downloads
        //unset( $menu_links['edit-account'] ); // Remove Account details tab
        //unset( $menu_links['customer-logout'] ); // Remove Logout link


        /*  Rename  Menu links
         *  $menu_links['TAB ID HERE'] = 'NEW TAB NAME HERE'; */
        $menu_links['downloads'] = 'My Files';


        // we will hook "anyuniquetext" later
        $new = array('anyuniquetext' => 'Gift for you');

        // or in case you need 2 links
        // $new = array( 'link1' => 'Link 1', 'link2' => 'Link 2' );

        // array_slice() is good when you want to add an element between the other ones
        $menu_links = array_slice($menu_links, 0, 1, true)
            + $new
            + array_slice($menu_links, 1, NULL, true);


        return $menu_links;

    }


    /**
     * @param $url
     * @param $endpoint
     * @param $value
     * @param $permalink
     * @return mixed
     */
    public function woocommerce_hook_endpoint($url, $endpoint, $value, $permalink)
    {

        if ($endpoint === 'anyuniquetext') {

            // ok, here is the place for your custom URL, it could be external
            $url = site_url();

        }

        return $url;

    }


}