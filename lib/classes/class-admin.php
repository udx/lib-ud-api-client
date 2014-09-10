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
      public $ui;
      
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
        $this->api = new API( $this->api_url, $this->token );
        
        $this->ui = new UI( array(
          'screens' => array(
            'licenses' => __( 'Licenses', $this->domain ),
          )
        ) );
        
        $path = dirname( dirname( __DIR__ ) );
        $this->screens_path = trailingslashit( $path . '/static/templates' );
        $this->assets_url = trailingslashit( plugin_dir_url( $path . '/readme.md' ) . 'static' );
        
        //** Load the updaters. */
        add_action( 'admin_init', array( $this, 'load_updater_instances' ) );
        
        //** Check Activation Statuses */
        add_action( 'plugins_loaded', array( $this, 'check_activation_status' ), 11 );
        
        //** Add Licenses page */
        $menu_hook = is_multisite() ? 'network_admin_menu' : 'admin_menu';
        add_action( $menu_hook, array( $this, 'register_licenses_screen' ) );
      }
      
      /**
       * Register the admin screen.
       *
       * @access public
       * @since   0.1.0
       * @return   void
       */
      public function register_licenses_screen () {
        $args = $this->args;
        $screen = !empty( $args[ 'screen' ] ) ? $args[ 'screen' ] : false;
        $this->screen_type = !empty( $screen[ 'parent' ] ) ? 'submenu' : 'menu';
        $this->icon_url = !empty( $screen[ 'icon_url' ] ) ? $screen[ 'icon_url' ] : '';
        $this->position = !empty( $screen[ 'position' ] ) ? $screen[ 'position' ] : 66;
        $this->page_title = !empty( $screen[ 'title' ] ) ? $screen[ 'title' ] : __( 'Licenses', $this->domain );
        $this->menu_slug = $this->plugin . '_' . sanitize_key( $this->page_title );
        
        switch( $this->screen_type ) {
          case 'menu':
            $this->hook = add_menu_page( $this->page_title, $this->page_title, 'manage_options', $this->menu_slug, array( $this, 'settings_screen' ), $this->icon_url, $this->position );
            break;
          case 'submenu':
            $this->hook = add_submenu_page( $screen[ 'parent' ], $this->page_title, $this->page_title, 'manage_options', $this->menu_slug, array( $this, 'settings_screen' ) );
            break;
        }
        
        add_action( 'load-' . $this->hook, array( $this, 'process_request' ) );
        add_action( 'admin_print_styles-' . $this->hook, array( $this, 'enqueue_styles' ) );
        add_action( 'admin_print_scripts-' . $this->hook, array( $this, 'enqueue_scripts' ) );
        
        $notices_hook = is_multisite() ? 'network_admin_notices' : 'admin_notices';
        add_action( $notices_hook, array( $this, 'admin_notices' ) );
      }
      
      /**
       * Load the main management screen.
       *
       * @access public
       * @since   0.1.0
       * @return   void
       */
      public function settings_screen () {
        
        $this->ui->get_header();

        $screen = $this->ui->get_current_screen();
        
        switch ( $screen ) {
          //** Licenses screen. */
          case 'license':
          default:
            $this->installed_products = $this->get_detected_products();
            $this->pending_products = $this->get_pending_products();
            //$this->ensure_keys_are_actually_active();
            require_once( $this->screens_path . 'screen-manage.php' );
          break;
        }

        $this->ui->get_footer();
      }
      
      /**
       * Process the action for the admin screen.
       * @since  0.1.0
       * @return  void
       */
      public function process_request () {
        
        $supported_actions = array( 'activate-products', 'deactivate-product' );
        if ( !isset( $_REQUEST['action'] ) || !in_array( $_REQUEST['action'], $supported_actions ) || !check_admin_referer( 'bulk-' . 'licenses' ) ) {
          return null;
        }
        
        $response = false;
        $status = 'false';
        $type = $_REQUEST['action'];

        switch ( $type ) {
          case 'activate-products':
            $products = array();
            if ( isset( $_POST[ 'products' ] ) && 0 < count( $_POST[ 'products' ] ) ) {
              foreach ( $_POST[ 'products' ] as $k => $v ) {
                if ( '' != $v ) {
                  $products[$k] = $v;
                }
              }
            }
            if ( false && 0 < count( $products ) ) {
              echo "<pre>"; print_r( $products ); echo "</pre>"; die();
              $response = $this->activate_products( $products );
            } else {
              $response = false;
              $type = 'no-license-keys';
            }
          break;

          case 'deactivate-product':
            if ( isset( $_GET['filepath'] ) && ( '' != $_GET['filepath'] ) ) {
              $response = $this->deactivate_product( $_GET['filepath'] );
            }
          break;

          default:
          break;
        }

        if ( $response == true ) {
          $status = 'true';
        }
        
        $redirect_url = \UsabilityDynamics\Utility::current_url( array( 'type' => urlencode( $type ), 'status' => urlencode( $status ) ) );
        wp_safe_redirect( $redirect_url );
        exit;
      }
      
      /**
       * Enqueue admin styles.
       * @access  public
       * @since   0.1.0
       * @return  void
       */
      public function enqueue_styles () {
        wp_enqueue_style( 'lib-ud-api-client-admin', esc_url( $this->assets_url . 'css/admin.css' ), array(), '0.1.0', 'all' );
      }
      
      /**
       * Enqueue admin scripts.
       *
       * @access  public
       * @since   0.1.0
       * @return  void
       */
      public function enqueue_scripts () {
        wp_enqueue_script( 'post' );
      }
      
      /**
       * Run checks against the API to ensure the product keys are actually active on UsabilityDynamics. If not, deactivate them locally as well.
       *
       * @access public
       * @since  0.1.0
       * @return void
       */
      public function ensure_keys_are_actually_active () {
        $products = (array)$this->get_activated_products();
        if ( 0 < count( $products ) ) {
          foreach ( $products as $k => $v ) {
            $status = $this->api->product_active_status_check( $k, $v[0], $v[1], $v[2] );
            if ( false == $status ) {
              $this->deactivate_product( $k, true );
            }
          }
        }
      }
      
      /**
       * Deactivate a given product key.
       *
       * @since    0.1.0
       * @param    string $filename File name of the to deactivate plugin licence
       * @param    bool $local_only Deactivate the product locally without pinging UsabilityDynamics.
       * @return   boolean          Whether or not the deactivation was successful.
       */
      protected function deactivate_product ( $filename, $local_only = false ) {
        $response = false;
        $already_active = $this->get_activated_products();

        if ( 0 < count( $already_active ) ) {
          $deactivated = true;
          if ( isset( $already_active[ $filename ][0] ) ) {
            $key = $already_active[ $filename ][2];
            if ( false == $local_only ) {
              $deactivated = $this->api->deactivate( $key );
            }
          }
          if ( $deactivated ) {
            unset( $already_active[ $filename ] );
            $response = update_option( $this->token . '-activated', $already_active );
          } else {
            $this->api->store_error_log();
          }
        }
        return $response;
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
          //echo "<pre>"; print_r( $reference_list ); echo "</pre>"; die();
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
        //echo "<pre>"; print_r( $_ud_license_updater ); echo "</pre>"; die();
        $response = array();
        if( 
          isset( $_ud_license_updater[ $this->plugin ] ) 
          && is_callable( array( $_ud_license_updater[ $this->plugin ], 'get_products' ) ) 
        ) {
          $response = $_ud_license_updater[ $this->plugin ]->get_products();
        }
        return $response;
      }
      
      /**
       * Get an array of products that haven't yet been activated.
       *
       * @access public
       * @since   0.1.0
       * @return  array Products awaiting activation.
       */
      protected function get_pending_products () {
        $response = array();
        $products = $this->installed_products;
        if ( is_array( $products ) && ( 0 < count( $products ) ) ) {
          $activated_products = $this->get_activated_products();
          if ( is_array( $activated_products ) && ( 0 <= count( $activated_products ) ) ) {
            foreach ( $products as $k => $v ) {
              if ( !in_array( $k, array_keys( $activated_products ) ) ) {
                $response[$k] = array( 'product_name' => $v['product_name'] );
              }
            }
          }
        }
        //echo "<pre>"; print_r( $response ); echo "</pre>"; die();
        return $response;
      }
      
      /**
       * Determine, if there are licenses that are not yet activated.
       * @access  public
       * @since   0.1.0
       * @return  void
       */
      public function check_activation_status () {
        $products = $this->get_detected_products();
        //echo "<pre>"; print_r( $products ); echo "</pre>"; die();
        $messages = array();
        if ( 0 < count( $products ) ) {
          foreach ( $products as $k => $v ) {
            if ( isset( $v['product_status'] ) && 'inactive' == $v['product_status'] ) {
              $message = sprintf( __( '%s License is not active. To get started, activate it <a href="%s">here</a>.', $this->domain ), $v['product_name'], 'http://example.com' );
              if( !empty( $v[ 'errors_callback' ] ) && is_callable( $v[ 'errors_callback' ] ) ) {
                call_user_func( $v[ 'errors_callback' ], $message );
              } else {
                $messages[] = $message;
              }
            }
          }
        }
        if( !empty( $messages ) ) {
          $this->messages = $messages;
        }
      }
      
      /**
       * Admin notices
       */
      public function admin_notices() {
        
        //** Step 1. Look for default messages */
        $messages = $this->messages;
        if( !empty( $messages ) && is_array( $messages ) ) {
          foreach( $messages as $message ) {
            echo '<div class="error fade"><p>' . $message . '</p></div>';
          }
        }
        
        //** Step 2. Look for status messages */
        $message = '';
        $response = '';

        if ( isset( $_GET['status'] ) && in_array( $_GET['status'], array( 'true', 'false' ) ) && isset( $_GET['type'] ) ) {
          $classes = array( 'true' => 'updated', 'false' => 'error' );
          $request_errors = $this->api->get_error_log();

          switch ( $_GET['type'] ) {
            case 'no-license-keys':
              $message = __( 'No license keys were specified for activation.', 'woothemes-updater' );
            break;

            case 'deactivate-product':
              if ( 'true' == $_GET['status'] && ( 0 >= count( $request_errors ) ) ) {
                $message = __( 'Product deactivated successfully.', 'woothemes-updater' );
              } else {
                $message = __( 'There was an error while deactivating the product.', 'woothemes-updater' );
              }
            break;

            default:

              if ( 'true' == $_GET['status'] && ( 0 >= count( $request_errors ) ) ) {
                $message = __( 'Products activated successfully.', 'woothemes-updater' );
              } else {
                $message = __( 'There was an error and not all products were activated.', 'woothemes-updater' );
              }
            break;
          }

          $response = '<div class="' . esc_attr( $classes[$_GET['status']] ) . ' fade">' . "\n";
          $response .= wpautop( $message );
          $response .= '</div>' . "\n";

          // Cater for API request error logs.
          if ( is_array( $request_errors ) && ( 0 < count( $request_errors ) ) ) {
            $message = '';

            foreach ( $request_errors as $k => $v ) {
              $message .= wpautop( $v );
            }

            $response .= '<div class="error fade">' . "\n";
            $response .= make_clickable( $message );
            $response .= '</div>' . "\n";

            // Clear the error log.
            $this->api->clear_error_log();
          }

          if ( '' != $response ) {
            echo $response;
          }
        }        
        
      }
      
    }
  
  }
  
}