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
    class API {
    
      /**
       * 
       */
      protected $api_url;
      
      /**
       * 
       */
      protected $token;
      
      /**
       * 
       */
      public function __construct( $api_url, $token ) {
        $this->api_url = $api_url;
        $this->token = $token;
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
      public function activate( $args ) {
        $defaults = array(
          'request' 			=> 'activation',
          'product_id' 		=> '',
          'instance' 			=> '',
          'platform' 			=> '',
          'software_version' 	=> ''
        );
        $args = wp_parse_args( $defaults, $args );
        return $this->request( $args );
      }

      /**
       * Deactivate Product
       */
      public function deactivate( $args ) {
        $defaults = array(
          'request' 		=> 'deactivation',
          'product_id' 	=> '',
          'instance' 		=> '',
          'platform' 		=> ''
        );
        $args = wp_parse_args( $defaults, $args );
        return $this->request( $args );
      }

      /**
       * Checks if the software is activated or deactivated
       * @param  array $args
       * @return array
       */
      public function status( $args ) {
        $defaults = array(
          'request' 		=> 'status',
          'product_id' 	=> '',
          'instance' 		=> '',
          'platform' 		=> '',
        );
        $args = wp_parse_args( $defaults, $args );
        return $this->request( $args );
      }
      
      /**
       *
       */
      protected function request( $args ) {
        $target_url = $this->create_software_api_url( $args );
        $request = wp_remote_get( $target_url );
        if( is_wp_error( $request ) ) {
          return false;
        } elseif( wp_remote_retrieve_response_code( $request ) != 200 ) {
          
        } else {
          $response = wp_remote_retrieve_body( $request );
          echo "<pre>"; print_r( $response ); echo "</pre>"; die();
          return $response;
        }
        return false;
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