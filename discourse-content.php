<?php
/**
 * Plugin Name: Discourse Topic
 */

// CORS must be enabled on your Discourse forum for this plugin to work.


// Add admin stylesheet
function testeleven_admin_styles() {
  wp_enqueue_style('admin-style', plugins_url('admin_styles.css', __FILE__));
}
add_action('admin_enqueue_scripts', 'testeleven_admin_styles');

// Add the 'Fetch Discourse Topic' meta-box to the 'discourse_topic' admin panel

function testeleven_add_discourse_meta_box() {
  add_meta_box(
    'discourse-fetch',
    'Get Discourse Topic',
    'testeleven_create_fetch_form',
    null,
    'normal',
    'high'
  );
}
add_action('add_meta_boxes', 'testeleven_add_discourse_meta_box');

function testeleven_create_fetch_form() { ?>
  <label for="discourse-base-url">Base URL</label>
  <input type="text" id="discourse-base-url" value=""/> <!-- this should probably be set in plugin settings -->
  <label for="discourse-topic-id">Topic ID</label>
  <input type="text" id="discourse-topic-id"/>
  <a href="#" class="button discourse-fetch-topic" id="discourse-fetch-topic">Fetch Discourse Topic</a>
  <div class="topic-posts"></div>
<?php
}

// The javascript in this function will be written to the footer of the admin page. It should be set so that it is only
// added to the page if we are dealing with the 'discourse_topic' custom post type.
function testeleven_fetch_discourse_topic() { ?>
  <script>
    jQuery(document).ready(function ($) {
      $('#discourse-fetch-topic').click(function (e) { // Add a nonce to the #discourse-fetch-topic link and validate it here.
        var base_url = $('#discourse-base-url').val(),
          topic_id = $('#discourse-topic-id').val(),
          topic_url = base_url + '/t/' + topic_id + '.json';

        $.getJSON(topic_url, function (data) {
          var topic_posts = data['post_stream']['posts'];
          var all_posts_in_topic = '';

          topic_posts.forEach(function (topic_post) {
            all_posts_in_topic += '<div class="topic-select"><label for="topic-' + topic_post['post_number'] +
              '">Include this post?</label> ' + '<input class="post-select" type="checkbox" name="topic-' +
              topic_post['post_number'] + '" value="'+ topic_post['post_number'] + '"/>' +
              '<div class="topic-post">' + topic_post['cooked'] + '</div></div>';
          });

          $('.topic-posts').append(all_posts_in_topic +
            '<div class="add-topics"><a href="#" class="button" id="add-discourse-topic">Add Topic Posts</a>');

        });
        e.preventDefault();
      });

      $('#discourse-fetch').on('click', '#add-discourse-topic', function(e) { // Add a nonce to the link and validate it here.
        var output = '';
        $.each($('.topic-select'), function() {
          if ($(this).find('.post-select').attr('checked')) {
            output += $(this).find('.topic-post').html();
          }
        });
        $('#content').html(output);
        e.preventDefault();
      });
    });
  </script>

<?php
}

add_action('admin_footer', 'testeleven_fetch_discourse_topic');
