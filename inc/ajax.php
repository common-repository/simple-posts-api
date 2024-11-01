<?php

class Postsapi_Ajax {
  
  private static $instance;

  /**
  * Set up the defaults for the Postapi_Ajax class
  *
  * @return none
  */
  function __construct() {

    // Set up the various AJAX functions
    $actions = array(
        'postsapi_get',
        'postsapi_put',
        'postsapi_delete',
        'postsapi_post'
      );
    foreach( $actions as $action ){
      add_action( 'wp_ajax_'.$action, array( $this, $action ) );
    }

    add_action ( 'wp_ajax_nopriv_postsapi_get', array( $this, 'postsapi_get' ) );
  }

  /**
  * Get posts based on passed URL parameter
  *
  * @return JSON list of posts
  */
  function postsapi_get(){

    // Security
    if( !$this->postsapi_check_security( $_POST['nonce'], false ) ){
      
      // Security failed; exit
      exit;
    }

    // Define the GET arguments
    $args = array( 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC' );
    if( $_GET['id'] != '' ){

      // Do any actions for this stage
      do_action( 'postsapi_before_get' );

      // Check if second parameter is an ID (int) or Post Type (string)
      if( intval( $_GET['id'] ) > 0 ){

        // Provided parameter is an ID, get single post
        $post_id = $_GET['id'];
        $post = get_post( $post_id );

        if( null == $post ){

          // Post not found, return error and empty post value
          $return['status']['code'] = '400';
          $return['status']['message'] = 'No posts found';
          $return['posts'] = array();
        }else{

          // Post found, get custom and set up $return
          $custom = get_post_custom( $post->ID );

          // Quick trick to convert the WP_Post object to an array
          $post = json_decode( json_encode( $post ), true );

          // Set the post custom value and add the post to $return
          $post['custom'] = $custom;
          $return['posts'][] = $post;
        }
      }else{
        $args['post_type'] = $_GET['id'];

        // Set up further arguments if passed in $_POST
        if( $_POST['arguments'] ){
          foreach( $_POST['arguments'] as $key=>$value ){
            $args[$key] = $value;
          }
        }

        // Get the posts based on our parameters
        $posts = get_posts( $args );
        if( count( $posts ) > 0 ){

          // We've found posts; let's set the status to 200 and process them
          $return['status']['code'] = '200';
          $return['status']['message'] = 'ok';

          // Loop through each post and grab the Custom field values
          foreach( $posts as $post ){
            $custom = get_post_custom( $post->ID );

            // Quick trick to convert the WP_Post object to an array
            $post = json_decode( json_encode( $post ), true );

            // Set the post custom value and add the post to $return
            $post['custom'] = $custom;

            // Get all associated post terms
            $terms = $this->postsapi_get_post_terms( $post['ID'] );
            $post['terms'] = $terms;

            // Get the post Author information
            $author = get_user_by( 'id', $post['post_author'] );
            $author = json_decode( json_encode( $author ), true );
            $author['meta'] = get_user_meta( $post['post_author'] );

            // Unset unsafe fields if not admin
            if ( !current_user_can( 'edit_posts' ) ){

              // Remove author roles
              unset( $author['roles'] );
              unset( $author['caps'] );
              unset( $author['cap_key'] );
              unset( $author['allcaps'] );
              unset( $author['data']['user_login'] );
              unset( $author['data']['user_pass'] );
              unset( $author['data']['user_email'] );
              unset( $author['data']['user_status'] );
              unset( $author['data']['user_url'] );
              unset( $author['data']['user_registered'] );
              unset( $author['data']['user_activation_key'] );

              // Remove all author meta besides names
              foreach( $author['meta'] as $key => $value ){
                if( $key != 'first_name' && $key != 'last_name' && $key != 'nickname' ){
                  unset( $author['meta'][$key] );
                }
              }
            }

            // Add author to post array
            $post['author'] = $author;

            $return['posts'][] = $post;
          }
        }else{

          // We don't have any posts; return an error and empty posts value
          $return['status']['code'] = '400';
          $return['status']['message'] = 'No posts found';
          $return['posts'] = array();
        }
      }
    }else{

      // User didn't provide a valid post type parameter; return error and exit
      $return['status']['code'] = '410';
      $return['status']['message'] = 'You must provide a valid post type or ID';
      wp_send_json( $return );
      exit;
    }

    
        
        // Do any actions for this stage
    do_action( 'postsapi_after_get' );

    // Echo our JSON and exit
    wp_send_json( $return );
    exit;
  }

  /**
  * Update posts with $_POST parameters
  *
  * @return JSON status message
  */
  function postsapi_put(){
    
    // Security
    if( !$this->postsapi_check_security( $_POST['nonce'], true ) ){
      
      // Security failed; exit
      exit;
    }

    // Get the post ID from $_GET
    $post_id = $_GET['id'];

    if( null == get_post( $post_id ) ){

      //Post not found; return rror and exit
      $return['status']['code'] = '420';
      $return['status']['message'] = 'Post ID not found';

      wp_send_json( $return );
      exit;
    }
    
    // Do any actions for this stage
    do_action( 'postsapi_before_put' );

    $fields = $this->post_api_verify_fields( $_POST['fields'], $post_id, TRUE );
    
    // If 'force' is false and some fields aren't found, return error and exit
    if( count( $fields['error'] ) > 0 && $_POST['force'] != 'true'){

      $return['status']['code'] = '430';
      $return['status']['message'] = 'Following provided fields not found: ';
      foreach( $fields['error'] as $field ){
        $return['status']['message'] .= $field . ' ';
      }

      wp_send_json( $return );
      exit;

    }

    // Either all fields exist, or 'force' is set to true; start updating fields, starting with Custom Fields
    foreach($fields['custom_cols'] as $key=>$value){

      // Update the custom fields
      update_post_meta( $post_id, $key, $value);
    }

    // Create post array to use in wp_update_post()
    $post = array(
      'ID' => $post_id
    );

    // Now update normal wp_post fields
    foreach( $fields['post_cols'] as $key=>$value ){

      // Add the key to the post array
      $post[$key] = $value;

      // Update the fields
      wp_update_post( $post );
    }
    
    // Do any actions for this stage
    do_action( 'postsapi_after_put' );

    // Send back response and exit
    $return['status']['code'] = '200';
    $return['status']['message'] = $fields;

    wp_send_json($return);
    exit;
  }

  /**
  * Delete post based on passed URL parameter
  *
  * @return JSON status message
  */
  public function postsapi_delete(){

    // Security
    if( !$this->postsapi_check_security( $_POST['nonce'], true ) ){
      
      // Security failed; exit
      exit;
    }

    // Get the post ID from $_GET
    $post_id = $_GET['id'];

    if( null == get_post( $post_id ) ){

      //Post not found; return rror and exit
      $return['status']['code'] = '420';
      $return['status']['message'] = 'Post ID not found';

      wp_send_json( $return );
      exit;
    }
    
    // Do any actions for this stage
    do_action( 'postsapi_before_delete' );

    // Check force variable to see if user wants to skip trash
    if( $_POST['force'] == 'true'){
      $force_delete = true;
    }else{
      $force_delete = false;
    }

    // Delete the post
    $delete = wp_delete_post( $post_id, $force_delete );

    // If the deletion fails, return error
    if( false == $delete ){
      $return['status']['code'] = '450';
      $return['status']['message'] = 'Post delete failed';
    }else{
      $return['status']['code'] = '200';
      $return['status']['message'] = 'ok';
    }
    
    // Do any actions for this stage
    do_action( 'postsapi_after_delete' );

    wp_send_json( $return );
    exit;

  }

  /**
  * Create post with $_POST parameters
  *
  * @return JSON status message
  */
  function postsapi_post(){
    
    // Security
    if( !$this->postsapi_check_security( $_POST['nonce'], true ) ){
      
      // Security failed; exit
      exit;
    }

    // Set post type to $_GET['id'] parameter
    $post_type = $_GET['id'];

    if( !post_type_exists( $post_type ) ){

      // Post type doesn't exist; return error and exit
      $return['status']['code'] = '460';
      $return['status']['message'] = 'Post type does not exist';

      wp_send_json( $return );
      exit;
    }
    
    // Do any actions for this stage
    do_action( 'postsapi_before_post' );

    $fields = $this->post_api_verify_fields( $_POST['fields'], $post_id, FALSE );
    
    // Create post and start updating Custom Fields
    foreach( $fields['post_cols'] as $key=>$value ){

      // Add the key to the post array
      $post_args[$key] = $value;

    }

    $post_args['post_type'] = $post_type;

    // Create the post; get the new ID
    $new_post = wp_insert_post( $post_args, true );

    if( !is_int( $new_post ) ){

      // Couldn't create post; return error and exit;
      $return['status']['code'] = '470';
      $return['status']['message'] = $new_post;

      wp_send_json( $return );
      exit;

    }

    // Post creation succeeded; update Custom Fields
    foreach($fields['custom_cols'] as $key=>$value){

      // Update the custom fields
      update_post_meta( $new_post, $key, $value);
    }

    $post = get_post( $new_post );
    $custom = get_post_custom( $new_post );

    // Quick trick to convert the WP_Post object to an array
    $post = json_decode( json_encode( $post ), true );

    // Set the post custom value and add to $post
    $post['custom'] = $custom;
    
    // Do any actions for this stage
    do_action( 'postsapi_after_post' );

    // Send back response and exit
    $return['status']['code'] = '200';
    $return['status']['message'] = $post;

    wp_send_json($return);
    exit;
  }

  /**
  * Run get all post terms based on Post ID
  *
  * @param Post ID
  *
  * @return array
  */
  function postsapi_get_post_terms( $post_id ){

    // Set up the terms array
    $post_terms = array();

    // Get the post type for the supplied post
    $post_type = get_post_type( $post_id );

    // Get all taxonomies associated with that post type
    $taxonomies = get_object_taxonomies( $post_type );

    // Loop through each taxonomy and get the terms for this post (if any)
    foreach( $taxonomies as $taxonomy ){

      // Get the post terms for this taxonomy
      $terms = wp_get_post_terms( $post_id, $taxonomy );
      foreach( $terms as $term ){
          if( $term->parent != 0 ){
              $term->parent = get_term( $term->parent, $taxonomy );
          }else{
              $term->parent = null;
          }
      }
      $post_terms[$taxonomy] = $terms ;
    }

    return $post_terms;

  }

  /**
  * Run nonce security check
  *
  * @param nonce value sent via $_POST request
  *
  * @return boolean
  */
  function postsapi_check_security( $nonce = NULL, $require_logged = true ){

    if( NULL == $nonce || ( $require_logged == true && !is_user_logged_in() ) ){

      //No nonce sent; error and return
      $return['status']['code'] = '401';
      $return['status']['message'] = 'Permission denied';
      wp_send_json( $return );
      return false;
    }else{

      // Check the nonce
      if( !$this->postsapi_validate_nonce( $nonce ) ){

        // Nonce validation failed; error and return
        $return['status']['code'] = '401';
        $return['status']['message'] = 'Permission denied';
        wp_send_json( $return );
        return false;
      }
    }

    // Nonce passed
    return true;

  }

  /**
  * Check nonce for validity
  *
  * @param nonce value sent via $_POST request
  *
  * @return boolean
  */
  function postsapi_validate_nonce( $nonce ){
    return wp_verify_nonce( $nonce, 'postsapi_query' );
  }

  /**
  * Check nonce for validity
  *
  * @param array $fields array of fields to check
  * @param int $post_id ID of post to check against
  * @param boolean $verify whether or not to verify custom fields
  *
  * @return array
  */
  function post_api_verify_fields( $fields, $post_id, $verify = FALSE ){

    global $wpdb;

    // List of wp_post columns to check against
    $post_cols = array( "ID", "post_author", "post_date", "post_date_gmt", "post_content", "post_title", "post_excerpt", "post_status", "comment_status", "ping_status", "post_password", "post_name", "to_ping", "pinged", "post_modified", "post_modified_gmt", "post_content_filtered", "post_parent", "guid", "menu_order", "post_type", "post_mime_type", "comment_count" );
    
    // Loop through each field and make sure that it exists
    foreach( $fields as $key=>$value ){
      if( in_array( $key, $post_cols ) ){

        // Key is part of the wp_posts table, add it to the return array
        $return['post_cols'][$key] = $value;

      }else{

        if( TRUE == $verify ){

          // Check if that field key exists
          $col = $wpdb->get_results( $wpdb->prepare( "SELECT meta_value FROM wp_postmeta WHERE meta_key = '%s' AND post_id = %d", $key, $post_id ));
          
          if( count( $col ) > 0 ){
            
            // Column found; add it to the return array
            $return['custom_cols'][$key] = $value;
          }else{

            // Column not found; add it to error array
            $return['error'][] = $key;
          }
        }else{

          // No need to verify; just add to the custom_cols array
          $return['custom_cols'][$key] = $value;
        }
        
      }
    }

    return $return;

  }

  /**
  * Create singleton for this class
  *
  * @return object instance of self
  */
  public static function get_instance(){
    if( null === self::$instance ){
      self::$instance = new Postsapi_Ajax();
    }
    return self::$instance;
  }
}

?>
