<?php

class Postsapi_Scripts {
  
  private static $instance;

  function __construct() {
    add_action( 'wp_head',array( $this, 'postsapi_ajaxurl' ) );
  }

  /**
   * [postsapi_ajaxurl description]
   * @return [type] [description]
   */
  function postsapi_ajaxurl(){
      $nonce = wp_create_nonce( 'postsapi_query' );
      ?>
      <script>
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        var secure = '<?php echo $nonce; ?>';
      </script>
      <?php
  }
  
  public static function get_instance(){
    if( null === self::$instance ){
      self::$instance = new Postsapi_Scripts();
    }
    return self::$instance;
  }
}

?>
