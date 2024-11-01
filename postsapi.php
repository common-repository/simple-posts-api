<?php
/*
Plugin Name: Posts API
Plugin URI: #
Description: A Plugin to provide a simple RESTful API to retrieve and manipulate Post data
Version: 1.0
Author: Aaron Speer, Westwerk
Author URI: http://westwerk.com
*/

$dir = dirname( __FILE__ );
@include_once "$dir/inc/admin.php";
@include_once "$dir/inc/ajax.php";
@include_once "$dir/inc/scripts.php";

function postsapi_init() {
  $postsapi_instance = Postsapi_Admin::get_instance();
}


function postsapi_activation() {
  // Activation hooks
}

function postsapi_deactivation() {
  // Deactivation hooks
}

// Add initialization and activation hooks
add_action( 'plugins_loaded', 'postsapi_init' );
register_activation_hook( "$dir/postsapi.php", 'postsapi_activation' );
register_deactivation_hook( "$dir/postsapi.php", 'postsapi_deactivation' );