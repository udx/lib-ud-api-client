<?php
/**
 * Client API
 *
 * @namespace UsabilityDynamics
 *
 */
namespace UsabilityDynamics\UD_API {

  if( !class_exists( 'UsabilityDynamics\UD_API\API' ) ) {

    /**
     * 
     * @author: peshkov@UD
     */
    class API extends Scaffold {
    
      /**
       * 
       */
      protected $api_url;

      /**
       * 
       */
      protected $errors;
      
      /**
       * 
       */
      protected $token;
      
      /**
       *
       */
      public function __construct( $args ) {
        parent::__construct( $args );
        $this->api_url = isset( $args[ 'api_url' ] ) ? $args[ 'api_url' ] : false;
        $this->token = isset( $args[ 'token' ] ) ? $args[ 'token' ] : false;
      }
      
      /**
       * API Key URL
       */
      public function create_software_api_url( $args ) {
        $api_url = add_query_arg( 'wc-api', 'am-software-api', $this->api_url );
        return $api_url . '&' . http_build_query( $args );
      }

      /**
       * Activate Product
       */
      public function activate( $args, $product ) {
        $defaults = array(
          'request' 			    => 'activation',
          'product_id' 		    => '',
          'instance' 			    => '',
          'email'             => '',
          'licence_key'       => '',
          'software_version' 	=> '',
          'platform' 			    => $this->blog,
        );
        $args = wp_parse_args( $args, $defaults );
        return $this->request( $args, $product );
      }

      /**
       * Deactivate Product
       */
      public function deactivate( $args, $product ) {
        $defaults = array(
          'request' 		=> 'deactivation',
          'product_id' 	=> '',
          'instance' 		=> '',
          'email'       => '',
          'licence_key' => '',
          'platform' 		=> $this->blog,
        );
        $args = wp_parse_args( $args, $defaults );
        return $this->request( $args, $product );
      }

      /**
       * Checks if the software is activated or deactivated
       * @param  array $args
       * @return array
       */
      public function status( $args, $product ) {
        $defaults = array(
          'request' 		=> 'status',
          'product_id' 	=> '',
          'instance' 		=> '',
          'platform' 	  => $this->blog,
        );
        $args = wp_parse_args( $args, $defaults );
        return $this->request( $args, $product );
      }
      
      /**
       *
       * @author peshkov@UD
       */
      protected function request( $args, $product ) {
        //** Add nocache hack. We must be sure we do not get CACHE result. peshkov@UD */
        $args = array_merge( $args, array( 'nocache' => rand( 10000, 99999 ) ) );
        $target_url = $this->create_software_api_url( $args );
        //echo "<pre>"; print_r( $target_url ); echo "</pre>"; die();
        $request = wp_remote_get( $target_url );
        if( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
          $this->log_request_error( sprintf( __( 'There was an error making %s request for %s. Could not do request to UsabilityDynamics.', $this->domain ), $args[ 'request' ], $product[ 'product_name' ] ) );
        } else {
          $response = wp_remote_retrieve_body( $request );
          $response = @json_decode( $response, true );
          //echo "<pre>"; print_r( $response ); echo "</pre>"; die();
          if( empty( $response ) || !is_array( $response ) ) {
            $this->log_request_error( sprintf( __( 'There was an error making %s request for %s, please try again', $this->domain ), $args[ 'request' ], $product[ 'product_name' ] ) );
          } elseif( !empty( $response[ 'error' ] ) ) {
            $this->log_request_error( sprintf( __( 'There was an error making %s request for %s: %s.' ), $args[ 'request' ], $product[ 'product_name' ], $response[ 'error' ] ) );
          } else {
            return $response;
          }
        }
        return false;
      }
      
      /**
       * Log an error from an API request.
       *
       * @access private
       * @since 0.1.0
       * @param string $error
       */
      public function log_request_error ( $error ) {
        $this->errors[] = $error;
      }
      
      /**
       * Store logged errors in a temporary transient, such that they survive a page load.
       * @since  0.1.0
       * @return  void
       */
      public function store_error_log () {
        set_transient( $this->token . '-request-error', $this->errors );
      }
      
      /**
       * Get the current error log.
       *
       * @since  0.1.0
       * @return  void
       */
      public function get_error_log () {
        return get_transient( $this->token . '-request-error' );
      }
      
      /**
       * Clear the current error log.
       *
       * @since  0.1.0
       * @return  void
       */
      public function clear_error_log () {
        return delete_transient( $this->token . '-request-error' );
      }
    
    }
  
  }
  
}