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
    add_action('admin_enqueue_scripts', array($this, 'admin_styles'));
    add_action('wp_enqueue_scripts', array($this, 'discourse_topic_styles'));
    add_action('add_meta_boxes', array($this, 'add_discourse_meta_box'));
    add_action('admin_footer', array($this, 'get_discourse_topic'));
    add_action('wp_ajax_get_json', array($this, 'get_json'));
  }

  // Add admin stylesheet
  public function admin_styles() {
    wp_enqueue_style('admin-styles', plugins_url('admin_styles.css', __FILE__));
  }

  // Add public styles
  public function discourse_topic_styles() {
    wp_enqueue_style('discourse-styles', plugins_url('discourse_topic_styles.css', __FILE__));
  }

  // Add 'Fetch Discourse Topic' meta box
  function add_discourse_meta_box() {
    add_meta_box(
      'discourse-fetch',
      'Load Discourse Topic',
       array($this, 'create_get_topic_form'),
       null,
      'normal',
      'high'
    );
  }

  // add_meta_box callback to create the form
  function create_get_topic_form() {?>
    <form method="get">
      <label for="discourse-url">URL:</label>
      <input type="url" id="discourse-url" name="discourse-url"/>
      <input type="submit" name="get-topic" id="get-topic" value="Fetch Discourse Topic"/>
    </form>
    <div class="topic-posts"></div>
  <?php
  }

  // Writes a javascript function to the admin page footer. The function makes an ajax request for the
  // Discourse topic and initiates an action on the server to fetch the topic from the Discourse forum.
  function get_discourse_topic() {
    ?>
    <script>
      jQuery(function($) {
        $('#get-topic').click(function (e) {
          var discourse_url = $('#discourse-url').val();
          var next_post = 21;
          var last_post;
          var data = {
            'action': 'get_json',
            'url': discourse_url
          };
          $.getJSON(ajaxurl, data, function (response) {
            var first_post = 1;
            var topic_post_count = response['posts_count'];
            var chunk_post_count = response['post_stream']['posts'].length;
            var last_post_number = response['post_stream']['posts'][chunk_post_count - 1]['post_number'];
            var $topic_posts = $('.topic-posts');
            var current_chunk_posts = response['post_stream']['posts'];
            var meta_box_post_content = '';
//            var last_post;
//            var next_post = 0;

            // Append the first chunk of posts to .topic-posts
            $topic_posts.html('');

            add_meta_box_post_content(response, $topic_posts, topic_post_count);

          });
          e.preventDefault();

          function parse_date(date_string) {
            var d = new Date(date_string);
            return d.toLocaleDateString();
          }

          function add_meta_box_post_content(response, target, post_count) {
            var chunk_posts = response['post_stream']['posts'];
            var num_posts_in_chunk = chunk_posts.length;
            var last_post = chunk_posts[num_posts_in_chunk - 1]['post_number'];
            var output = '';

            chunk_posts.forEach(function(chunk_post) {
              var post_number = chunk_post['post_number'];
              if (next_post > post_number) {
                output += '<div class="topic-select"><label for="topic-' + post_number +
                '">Include this post?'+ post_number + '</label> ' + '<input class="post-select" type="checkbox" name="topic-' +
                post_number + '" value="' + post_number + '"/>' + '<div class="topic-post">' +
                '<div class="post-meta">Posted by <span class="username">' + chunk_post['username'] +
                '<span> on <span class="post-date">' + parse_date(chunk_post['created_at']) + '</span></div>' +
                chunk_post['cooked'] + '</div></div>';
              }
              });
            target.append(output);

            if (last_post <= post_count) {
              next_post = last_post;
              data = {
                'action': 'get_json',
                'url': discourse_url + '/' +  next_post
              };
              $.getJSON(ajaxurl, data, function(response) {
                add_meta_box_post_content(response, target, post_count);
              });
            }
          }

        });
      });
    </script>
  <?php
  }

  function get_json() {
    $url = $_GET['url'];
    $topic_json_url = $url . '.json';
    $topic_json = file_get_contents($topic_json_url);
    echo $topic_json;
    wp_die();
  }
}

Testeleven_Discourse_Content::get_instance();