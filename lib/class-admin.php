<?php
/**
 * Licenses Admin
 *
 * @namespace UsabilityDynamics
 *
 */
namespace UsabilityDynamics\UD_API {

  if( !class_exists( 'UsabilityDynamics\UD_API\Admin' ) ) {

    /**
     * 
     * @author: peshkov@UD
     */
    class Admin extends Scaffold {
    
      /**
       *
       */
      public static $version = '0.1.0';
      
      /**
       *
       */
      private $api_url = 'http://woo.ud-dev.com/';
      
      /**
       * Don't ever change this, as it will mess with the data stored of which products are activated, etc.
       *
       */
      private $token = 'ud-license-manager';
      
      /**
       *
       */
      private $api;

      /**
       *
       */
      private $installed_products = array();
      
      /**
       *
       */
      private $pending_products = array();
      
      /**
       *
       */
      public function __construct( $args = array() ) {
        parent::__construct( $args );
        
        //** */
        $this->api = new API();
        
        //** Load the updaters. */
        add_action( 'admin_init', array( $this, 'load_updater_instances' ) );
        
      }
      
      /**
       * Load an instance of the updater class for each activated WooThemes Product.
       * @access public
       * @since  0.1.0
       * @return void
       */
      public function load_updater_instances () {
        $products = $this->get_detected_products();
        $activated_products = $this->get_activated_products();
        if ( 0 < count( $products ) ) {
          foreach ( $products as $k => $v ) {
            if ( isset( $v['product_id'] ) && isset( $v['instance_key'] ) ) {
              new Update_Checker( array(
                'upgrade_url' => $this->api_url,
                'plugin_name' => $v[ 'product_name' ],
                'product_id' => $v[ 'product_id' ],
                'api_key' => ( isset( $activated_products[ $k ][2] ) ? $activated_products[ $k ][2] : '' ),
                'activation_email' => ( isset( $activated_products[ $k ][3] ) ? $activated_products[ $k ][3] : '' ),
                'renew_license_url' => trailingslashit( $this->api_url ) . 'my-account',
                'instance' => $v[ 'instance_key' ],
                'software_version' => $v[ 'product_version' ],
                'text_domain' => $this->domain,
              ), $v[ 'errors_callback' ] );
            }
          }
        }
      }
      
      /**
       * Detect which products have been activated.
       *
       * @access public
       * @since   0.1.0
       * @return   void
       */
      protected function get_activated_products () {
        $response = array();
        $response = get_option( $this->token . '-activated', array() );
        if ( ! is_array( $response ) ) $response = array();
        return $response;
      }
      
      /**
       * Get a list of UsabilityDynamics products ( plugins ) found on this installation.
       *
       * @access public
       * @since   0.1.0
       * @return   void
       */
      protected function get_detected_products () {
        $response = array();
        $products = get_plugins();
        if ( is_array( $products ) && ( 0 < count( $products ) ) ) {
          $reference_list = $this->get_product_reference_list();
          $activated_products = $this->get_activated_products();
          if ( is_array( $reference_list ) && ( 0 < count( $reference_list ) ) ) {
            foreach ( $products as $k => $v ) {
              if ( in_array( $k, array_keys( $reference_list ) ) ) {
                $status = 'inactive';
                if ( in_array( $k, array_keys( $activated_products ) ) ) { 
                  $status = 'active'; 
                }
                $response[$k] = array( 
                  'product_name' => $v['Name'], 
                  'product_version' => $v['Version'], 
                  'instance_key' => $reference_list[$k]['instance_key'], 
                  'product_id' => $reference_list[$k]['product_id'],
                  'product_status' => $status, 
                  'product_file_path' => $k,
                  'errors_callback' => isset( $reference_list[$k]['errors_callback'] ) ? $reference_list[$k]['errors_callback'] : false,
                );
              }
            }
          }
        }
        return $response;
      }
      
      /**
       * Get a list of products from UsabilityDynamics.
       *
       * @access public
       * @since   0.1.0
       * @return   void
       */
      protected function get_product_reference_list () {
        global $_ud_license_updater;
        $response = array();
        if( 
          isset( $_ud_license_updater[ $this->plugin ] ) 
          && is_callable( array( $_ud_license_updater[ $this->plugin ], 'get_products' ) ) 
        ) {
          $response = $_ud_license_updater[ $this->plugin ]->get_products();
        }
        return $response;
      }
      
    }
  
  }
  
}