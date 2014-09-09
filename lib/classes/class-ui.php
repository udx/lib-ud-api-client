<?php
/**
 * Screen UI
 *
 * @namespace UsabilityDynamics
 *
 */
namespace UsabilityDynamics\UD_API {

  if( !class_exists( 'UsabilityDynamics\UD_API\UI' ) ) {

    /**
     * 
     * @author: peshkov@UD
     */
    class UI {
      
      /**
       * Available Screens
       */
      public $available_screens;
      
      /**
       * Constructor
       */
      public function __construct( $args ) {
        $this->available_screens = isset( $args[ 'screens' ] ) ? $args[ 'screens' ] : array();
      }
      
      /**
       * Generate header HTML.
       * @access  public
       * @since   0.1.0
       * @return  void
       */
      public function get_header ( $token = 'ud-license-manager', $screen_icon = 'tools' ) {
        do_action( 'woothemes_updater_screen_before', $token, $screen_icon );
        $html = '<div class="wrap woothemes-updater-wrap">' . "\n";
        $html .= get_screen_icon( $screen_icon );
        $html .= '<h2 class="nav-tab-wrapper">' . "\n";
        $html .= $this->get_navigation_tabs();
        $html .= '</h2>' . "\n";
        echo $html;
        do_action( 'woothemes_updater_screen_header_before_content', $token, $screen_icon );
      }

      /**
       * Generate footer HTML.
       * @access  public
       * @since   0.1.0
       * @return  void
       */
      public function get_footer ( $token = 'ud-license-manager', $screen_icon = 'tools' ) {
        do_action( 'woothemes_updater_screen_footer_after_content', $token, $screen_icon );
        $html = '</div><!--/.wrap woothemes-updater-wrap-->' . "\n";
        echo $html;
        do_action( 'woothemes_updater_screen_after', $token, $screen_icon );
      }

      /**
       * Generate navigation tabs HTML, based on a specific admin menu.
       * @access  public
       * @since   0.1.0
       * @return  string/WP_Error
       */
      public function get_navigation_tabs () {
        $html = '';

        $screens = !empty( $this->available_screens ) && is_array( $this->available_screens ) ? $this->available_screens : array();
        
        $current_tab = self::get_current_screen();
        if ( 0 < count( $screens ) ) {
          foreach ( $screens as $k => $v ) {
            $class = 'nav-tab';
            if ( $current_tab == $k ) {
              $class .= ' nav-tab-active';
            }

            $url = add_query_arg( 'page', 'woothemes-helper', network_admin_url( 'index.php' ) );
            $url = add_query_arg( 'screen', $k, $url );
            $html .= '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $class ) . '">' . esc_html( $v ) . '</a>';
          }
        }

        return $html;
      }

      /**
       * Return the token for the current screen.
       * @access  public
       * @since   0.1.0
       * @return  string The token for the current screen.
       */
      public function get_current_screen () {
        $screen = 'licenses'; // Default.
        if ( isset( $_GET['screen'] ) && '' != $_GET['screen'] ) $screen = esc_attr( $_GET['screen'] );
        return $screen;
      }
      
    }
  
  }
  
}