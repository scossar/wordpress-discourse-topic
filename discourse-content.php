<?php
/**
 * Plugin Name: Discourse Topic
 */

// Custom Post Type

function testeleven_discourse_topic() {
  $labels = array(
    'name' => 'Discourse Topics',
    'singular_name' => 'Discourse Topic',
    'add_new_item' => 'Add New Discourse Topic',
  );
  $args = array(
    'labels' => $labels,
    'public' => true,
    'has_archive' => true,
    'taxonomies' => array('category'),
    'supports' => array('title', 'editor', 'thumbnail', 'revisions')
  );
  register_post_type('discourse_topic', $args);
}

add_action('init', 'testeleven_discourse_topic');

// Add a meta box to the admin panel

function testeleven_add_fetch_button() {
  add_meta_box(
    'discourse-fetch',
    'Fetch Discourse Topic',
    'testeleven_create_fetch_button',
    'discourse_topic',
    'advanced',
    'high'
  );
}

// Add admin stylesheet
function testeleven_admin_styles() {
  wp_enqueue_style('admin-style', plugins_url('admin_styles.css', __FILE__));
}
add_action('admin_enqueue_scripts', 'testeleven_admin_styles');



add_action('add_meta_boxes', 'testeleven_add_fetch_button');

function testeleven_create_fetch_button() { ?>
  <label for="discourse-base-url">Base URL</label>
  <input type="text" id="discourse-base-url" value="http://testeleven.com"/>
  <label for="discourse-topic-id">Topic ID</label>
  <input type="text" id="discourse-topic-id"/>
  <a href="#" class="button discourse-fetch-topic" id="discourse-fetch-topic">Fetch Discourse Topic</a>
  <div class="topic-posts"></div>
<?php
}



function testeleven_fetch_discourse_topic() { ?>
  <script>
    jQuery(document).ready(function ($) {
      $('#discourse-fetch-topic').click(function (e) {
        var base_url = $('#discourse-base-url').val(),
          topic_id = $('#discourse-topic-id').val(),
          topic_url = base_url + '/t/' + topic_id + '.json';

        $.getJSON(topic_url, function (data) {
          var topic_posts = data['post_stream']['posts'];
          var post_excerpts = '';
          var topic_posts_cooked = [];

          topic_posts.forEach(function (topic_post) {
            console.log(topic_post);
            topic_posts_cooked.push(topic_post['cooked']);


            post_excerpts += '<div class="topic-select"><label for="topic-' + topic_post['post_number'] + '">Include this post?</label> ' +
              '<input class="post-select" type="checkbox" name="topic-' + topic_post['post_number'] + '" value="'+ topic_post['post_number'] + '"/>' +
              '<div class="topic-excerpt">' + topic_post['cooked'] + '</div>' +
              '</div>';
          });

          $('.topic-posts').append(post_excerpts + '<div class="add-topics"><button id="add-discourse-topic">Add Topic</button></div>');

        });
        e.stopPropagation();
      });

      $('#discourse-fetch').on('click', '#add-discourse-topic', function(e) {
        var output = '';
        $('.topic-select').each(function() {
          if ($(this).find('input:checked')) {
            output += $(this).find('.topic-excerpt').html();
          }
        });
        $('#content').val(output);
        e.preventDefault();
      });
    });
  </script>

<?php
}

add_action('admin_footer', 'testeleven_fetch_discourse_topic');
