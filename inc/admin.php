<?php

/**
 * Posts API class
 */
class Postsapi_Admin {
  
  private static $instance;
  function __construct() {
    $this->ajax = Postsapi_Ajax::get_instance();
    $this->scripts = Postsapi_Scripts::get_instance();
    add_action( 'init', array( $this, 'postsapi_add_rewrites' ) );
  }

  function postsapi_add_rewrites(){  
    add_rewrite_rule( 'postsapi/([^/]*)/([^/]*)/?', '/wp-admin/admin-ajax.php?action=postsapi_$1&id=$2', 'top' );
  }
  
  public static function get_instance(){
    if( null === self::$instance ){
      self::$instance = new Postsapi_Admin();
    }
    return self::$instance;
  }
}

?>
