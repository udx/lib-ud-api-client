<?php
/**
 * Scaffold
 *
 * @namespace UsabilityDynamics
 *
 */
namespace UsabilityDynamics\UD_API {

  if( !class_exists( 'UsabilityDynamics\UD_API\Scaffold' ) ) {

    /**
     *
     * @class Scaffold
     * @author: peshkov@UD
     */
    abstract class Scaffold {
            
      /**
       * Storage for dynamic properties
       * Used by magic __set, __get
       *
       * @protected
       * @type array
       */
      protected $_properties = array();
      
      /**
       * Constructor
       *
       * @author peshkov@UD
       */
      public function __construct( $args = array() ) {
        //** Setup our plugin's data */
        $this->name = isset( $args[ 'name' ] ) ? trim( $args[ 'name' ] ) : false;
        $this->plugin = sanitize_key( $this->name );
        $this->args = $args;
      }
      
      /**
       *
       */
      public function __get( $key ) {
        return isset( $this->_properties[ $key ] ) ? $this->_properties[ $key ] : NULL;
      }

      /**
       *
       */
      public function __set( $key, $value ) {
        $this->_properties[ $key ] = $value;
      }
      
    }
  
  }
  
}