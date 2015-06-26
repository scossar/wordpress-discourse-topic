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
          var discourse_url = $('#discourse-url').val(),
              baseurl = get_base_url(discourse_url),
              path = get_pathname(discourse_url),
              topic_id = nth_part(path, '/', 2),
              json_url,
              data;

          if (true) {
            json_url = baseurl + '//' + path + '.json';
            var data = {
              'action': 'get_json',
              'url': json_url
            };
            $.getJSON(ajaxurl, data, function (response) {
              var error_message = '';

              if (response) {
                var topic_posts = response['post_stream']['posts'];
                var all_posts_in_topic = '';
                var $topic_posts = $('.topic-posts');

                $topic_posts.html('');

                topic_posts.forEach(function (topic_post) {
                  all_posts_in_topic += '<div class="topic-select"><label for="topic-' + topic_post['post_number'] +
                  '">Include this post?</label> ' + '<input class="post-select" type="checkbox" name="topic-' +
                  topic_post['post_number'] + '" value="' + topic_post['post_number'] + '" checked/>' +
                  '<div class="topic-post">' +
                  '<div class="post-meta">Posted by <span class="username">' + topic_post['username'] +
                  '<span> on <span class="post-date">' + parse_date(topic_post['created_at']) + '</span></div>' +
                  topic_post['cooked'] + '</div></div>';
                });

                $topic_posts.append(all_posts_in_topic);
              } else {
                // Add some better error handling here...
                error_message = 'There was no response from the server. Please try again with a different url.';
                $('#discourse-url').addClass('discourse-error').val(error_message);
              }
            });
          } else {
            // throw some kind of error
          }

          e.preventDefault();

          function parse_date(date_string) {
            var d = new Date(date_string);
            return d.toLocaleDateString();
          }

          function get_base_url(url) {
            var protocol,
                hostname,
                tmp = document.createElement('a');
            tmp.href = url;
            protocol = tmp.protocol;
            hostname = tmp.hostname;
            return protocol + '//' + hostname;
          }

          function get_pathname(url) {
            var tmp = document.createElement('a');
            tmp.href = url;
            return tmp.pathname;
          }

          function is_topic_path(path) {
            var re = /^\/t/;
            return re.test(path);
          }

          function nth_part(str, sep, num) {
            var parts = str.split(sep);
            if (parts.length > num) {
              return parts[num];
            } else {
              return "this needs to throw some kind of an error!";
            }
          }
        });

        $('#discourse-fetch').on('change', '.post-select', function(e) {
          var output = '<section class="discourse-topic">'; // If we want to append to the current content of the editor then output should be set to that.
          $.each($('.topic-select'), function() {
            if ($(this).find('.post-select').prop('checked')) {
              output += '<div class="discourse-post">' + $(this).find('.topic-post').html() + '</div>';
            }
          });
          output += '</section>';
          $('#content').html(output);
          e.preventDefault();
        });

        $('#discourse-fetch').on('click', '.discourse-error', function() {
          $(this).removeClass('discourse-error').val('');
        });

      });
    </script>
  <?php
  }

  function get_json() {
    $url = $_GET['url'];
    $topic_json = file_get_contents($url);
    echo $topic_json;
    wp_die();
  }
}

Testeleven_Discourse_Content::get_instance();