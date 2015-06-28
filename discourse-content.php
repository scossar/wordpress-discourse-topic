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
          var url = $('#discourse-url').val(); // URL input by the user - this should be validated!!!
          var base_url = get_base_url(url); // http protocol + '//' + host
          var topic_path = get_topic_path(url); // Removes topic slug from pathname - needed for topic/posts api call
          var topic_posts_base_url = base_url + topic_path + 'posts.json?';

          // Data object for the WordPress ajax call. The 'action' property is used to create the WordPress action
          // 'wp_ajax_'{action name} that can be used to call a php function on the server. Here is is calling the
          // get_json() method which is used to retrieve the json data.
          var data = {
            'action': 'get_json',
            'url': url + '.json'
          };

          // Use the initial request to gather data about the topic.
          $.getJSON(ajaxurl, data, function (response) {
            var chunk_size = response['chunk_size'];
            var stream = response['post_stream']['stream']; // The array of post_ids in the topic.
            var $target = $('.topic-posts'); // This is where we are going to output the topic posts.

            // Clear the target in case the form is submitted more than once.
            $target.html('');

            add_meta_box_post_content(response, stream, $target, chunk_size);

          });
          e.preventDefault();

          function add_meta_box_post_content(response, post_stream, target, chunk_size) {
            // On the initial request posts are in the post_stream object. On subsequent requests, posts are in the 'posts' object.
            var posts = (response.hasOwnProperty('post_stream')) ? response['post_stream']['posts'] : response['posts'];
            var output = '';
            var current_request_ids;

            // Append each post to the output string and remove it from the post_stream array.
            posts.forEach(function(post) {
              var post_id = post['post_id'];
              output += '<div class="post-select">' +
                        '<label for="post-' + post_id + '">Include this post?</label> ' +
                        '<input class="post-select" type="checkbox" name="post-' + post_id + '" value="' + post_id + '"/>' +
                        '<div class="topic-post">' +
                        '<div class="post-meta">' +
                        'Posted by <span class="username">' + post['username'] +
                        '<span> on <span class="post-date">' + parse_date(post['created_at']) + '</span>' +
                        '</div>' + // .post-meta
                         post['cooked'] + // post content
                        '</div>' + // .topic-post
                        '</div>'; // .post-select

              // Remove the post from the post_stream array.
              post_stream.shift();
            });

            target.append(output);

            // If there are still posts in post_stream, use them to construct a url. Then make the
            // ajax call and recursively call the add_meta_box_content() function.
            if (post_stream.length > 0) {
              // Get the next chunk of posts.
              if (post_stream.length > chunk_size) {
                current_request_ids = post_stream.slice(0, chunk_size);
              } else {
                current_request_ids = post_stream;
              }

              // Construct the url
              current_request_ids.forEach(function(id, index) {
                topic_posts_base_url += 'post_ids%5B%5D=' + id;
                if (index < current_request_ids.length - 1) {
                  topic_posts_base_url += '&';
                }
              });

              data = {
                'action': 'get_json',
                'url': topic_posts_base_url
              };
              $.getJSON(ajaxurl, data, function(response) {
                add_meta_box_post_content(response, post_stream, target, chunk_size);
              });
            }

            // Make nice dates from the date string -- this could be improved
            function parse_date(date_string) {
              var d = new Date(date_string);
              return d.toLocaleDateString();
            }

          } // End of add_meta_box_post_content()

        });

        function get_base_url(url) {
          var tmp = document.createElement('a'),
              host,
              protocol;

          tmp.href = url;
          host = tmp.hostname;
          protocol = tmp.protocol;

          return protocol + '//' + host;
        }

        function get_topic_path(url) {
          var tmp = document.createElement('a'),
              path_name,
              path_parts;

          tmp.href = url;
          path_name = tmp.pathname;
          path_parts = path_name.split('/');
          return '/' + path_parts[1] + '/' + path_parts[3] + '/';
        }
      });
    </script>
  <?php
  }

  function get_json() {
    $url = $_GET['url'];
//    $topic_json_url = $url . '.json';
    $topic_json = file_get_contents($url);
    echo $topic_json;
    wp_die();
  }
}

Testeleven_Discourse_Content::get_instance();

