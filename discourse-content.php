<?php
/**
 * Plugin Name: Discourse Content
 * Plugin Author: scossar
 */

class Testeleven_Discourse_Content {
  protected static $instance = null;

  public static function get_instance() {
    self::$instance === null && self::$instance = new self;

    return self::$instance;
  }

  protected function __construct() {
    // Hook methods into actions
    add_action('admin_enqueue_scripts', array($this, 'admin_styles'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_javascript'));
    add_action('wp_enqueue_scripts', array($this, 'discourse_topic_styles'));
    add_action('add_meta_boxes', array($this, 'add_discourse_meta_box'));
    add_action('wp_ajax_get_json', array($this, 'get_json'));
    add_action('wp_ajax_create_post', array($this, 'create_post'));
  }

  // Include admin stylesheet
  public function admin_styles() {
    wp_enqueue_style('admin-styles', plugins_url('admin_styles.css', __FILE__));
  }

  // Include public styles
  public function discourse_topic_styles() {
    wp_enqueue_style('discourse-styles', plugins_url('discourse_topic_styles.css', __FILE__));
  }

  // Include plugin javascript
  public function enqueue_javascript($hook) { // $hook contains the name of the current admin page.
    if ($hook != 'post.php' && $hook != 'post-new.php') {
      return;
    }
    wp_enqueue_script('discourse-content', plugins_url('discourse-content.js', __FILE__));
    // We can pass values to 'discourse-content.js here and access them as properties
    // of 'ajax_object' ex. ajax_object.example_value // 'this is a test'
    wp_localize_script('discourse-content', 'ajax_object',
      array('example_value' => 'this is a test'));
  }

  // Add 'Fetch Discourse Topic' meta box
  function add_discourse_meta_box() {
    add_meta_box(
      'discourse-fetch',
      'Load Discourse Topic',
      array($this, 'discourse_meta_box_callback'),
      null,
      'normal',
      'high'
    );
  }

  // add_meta_box callback to create the form
  function discourse_meta_box_callback() {?>
    <div id="discourse-message"></div>
    <div class="loading"></div>
    <label for="discourse-url">URL:</label>
    <input type="text" id="discourse-url" name="discourse-url"/>
    <button id="get-topic">Fetch Discourse Topic</button>
    <div class="topic-posts"></div>
  <?php
  }

  function get_json() {
    $url = $_GET['url'];
    $topic_json = file_get_contents($url);
    echo $topic_json;
    wp_die();
  }

  // Handle the ajax request from discourse-content.js
  function create_post() {
    $post_data = array(
      'post_content' => $_POST['content'],
      'post_name' => $_POST['slug'],
      'post_title' => $_POST['title'],
      'post_status' => $_POST['post_status'],
      'post_type' => $_POST['post_type']
//      'post_category' => $_POST['post_category']
    );
    $new_post_ID = wp_insert_post($post_data);

    // Send a response back to the javascript handler
//    $response = array(
//      'status' => '200',
//      'message' => 'OK',
//      'new_post_ID' => $new_post_ID
//    );

//    echo $response;
    wp_die();
  }
}

Testeleven_Discourse_Content::get_instance();

