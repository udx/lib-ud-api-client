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
      public function __construct( $api_url ) {
        $this->api_url = $api_url;
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
        if( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
          //** Request failed */
          return false;
        }
        $response = wp_remote_retrieve_body( $request );
        return $response;
      }
    
    }
  
  }
  
}