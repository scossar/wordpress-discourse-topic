jQuery(document).ready(function($) {
  var $discourse_fetch = $('#discourse-fetch'),
      discourse_category;

  $('#get-topic').click(function (e) {

    var $discourse_url = $('#discourse-url'),
        url = $discourse_url.val(),
        absolute_url,
        base_url,
        initial_request_url,
        post_request_url;

    if (! url) {
      warning('You must supply the URL to a Discourse forum topic.');
    } else if (! is_url(url) || (! get_topic_id(url))) {

      warning('The supplied URL is not a Discourse topic URL. ' +
      'Please copy and paste a valid URL from a Discourse forum topic into the \'URL\' input.');

    } else {
      // It's looking good, set the url values and try making a request.
      absolute_url = get_absolute_url_base(url); // Used for fixing relative links
      base_url = get_base_url(url);
      initial_request_url = base_url + '.json';
      post_request_url = base_url + '/' + 'posts.json';
      $('.loading').addClass('discourse-loading');

      // Data object for the WordPress ajax call. The 'action' property is used to create the WordPress action
      // 'wp_ajax_'{action name} that can be used to call a php function on the server. Here it is calling the
      // get_json() method which is used to retrieve the json data.
      var data = {
        'action': 'get_json',
        'url': initial_request_url
      };

      // Use the initial request to gather data about the topic.
      $.getJSON(ajaxurl, data, function (response) {

        console.log(response);

        var chunk_size,
          stream,
          $target = $('.topic-posts'); // This is where we are going to output the topic posts.

        // If there is no chunk_size property on the response object then we have
        // some sort of error. Remove the loading spinner and try again.
        try {
          chunk_size = response['chunk_size'];
        } catch (err) {
          $('.loading').removeClass('discourse-loading');
          warning('There was an error returned from the server. ' +
          'It is possible that you have incorrectly entered the forum URL. The server ' +
          'may also be undergoing maintenance. If the problem persists, please contact ' +
          'the forum administrator.');
        }

        // The array of post_ids that make up the topic. The first chunk_size of
        // them will be used in the initial request. They are removed from the array
        // here.  On each call to add_meta_box_post_content() an attempt is made to
        // populate the next_request_ids array by calling splice(0, chunk_size) on the
        // post_stream array. If this is successful ( tested by calling next_request_ids.length)
        // add_meta_box_post_content() is called recursively.
        stream = response['post_stream']['stream'].slice(chunk_size);

        // Set the title in the editor
        set_title(response);

        // Clear the target in case the form is submitted more than once.
        $target.html('');

        add_meta_box_post_content(response, stream, $target, chunk_size);
      }).fail(function(response) {
        $('.loading').removeClass('discourse-loading');
        warning('There was no response returned from the server. ' +
        'It is possible that you have incorrectly entered the forum URL. The server ' +
        'may also be undergoing maintenance. If the problem persists, please contact ' +
        'the forum administrator.');
      });
    }

    // Add posts from forum to admin meta_box.
    function add_meta_box_post_content(response, post_stream, target, chunk_size) {
      var posts = response['post_stream']['posts'],
          next_request_ids = post_stream.splice(0, chunk_size),
          next_request_params = '?',
          next_request_url;

      target.append(render(posts));

      if (next_request_ids.length) {
        next_request_ids.forEach(function(id, index) {
          next_request_params += 'post_ids[]=' + id;
          if (index < next_request_ids.length - 1) {
            next_request_params += '&';
          }
        });
        next_request_url = post_request_url + encodeURI(next_request_params);

        data = {
          'action': 'get_json',
          'url': next_request_url
        };

        $.getJSON(ajaxurl, data, function(response) {
          add_meta_box_post_content(response, post_stream, target, chunk_size);
        });
      } else { // The last request is finished. Remove .discourse-loading from the body tag
        $('.loading').removeClass('discourse-loading');
      }

      function render(posts) {
        var output = [];

        output.push(
          '<div class="discourse-topic-controls clearfix">' +
            //'<label for="post-select-toggle" class="post-select-label">Unselect all Posts</label>' +
          '<button class="unselect-all-posts">Unselect all Posts</button>' +
          '<button class="select-all-posts">Select all Posts</button>' +
            //'<input type="checkbox" class="post-select-toggle unselect" name="post-select-toggle" />' +
          '<button class="load-posts">Load Posts in Editor</button>' +
          '</div>'
        );

        posts.forEach(function(post) {
          var post_id = post['id'],
              avatar_link = fix_avatar_link(post['avatar_template'], absolute_url);

          output.push(
            '<div class="post-select">' +
            '<label for="post-' + post_id + '">Include this post?</label> ' +
            '<input class="post-select-box" type="checkbox" name="post-' + post_id + '" ' + '" checked="checked"/>' +
            '<div class="topic-post">' +
            '<div class="discourse-post">' +
            '<div class="post-meta">' +
            '<div class="avatar"><img src="' + avatar_link + '" alt="missing avatar" width="45"/></div>' +
            'Posted by <span class="username">' + post['username'] +
            '</span> on <span class="post-date">' + parse_date(post['created_at']) + '</span>' +
            '</div>' + // .post-meta
            post['cooked'] + // post content
            '</div>' + // .discourse-post
            '</div>' + // .topic-post
            '</div>' // .post-select
          );
        });

        output.join('');
        return output;
      } // End of render()

    } // End of add_meta_box_post_content() */

    e.preventDefault(); // Don't reload the page.
  });

  // Load posts in the editor
  $discourse_fetch.on('click', '.load-posts', function(e) {
    var num_pages,
        selected_topic_posts = [],
        $title = $('#title'),
        categories = get_categories(),
        page_num,
        topic = [],
        content,
        num_posts_selected;

    console.log(categories);
    // Load all the content in an array
    $.each($('.post-select'), function() {
      if ($(this).find('.post-select-box').prop('checked')) {
        selected_topic_posts.push($(this).find('.topic-post').html());
      }
    });

    num_posts_selected = selected_topic_posts.length;

    if (num_posts_selected <= 20) {

      $('#content').html(selected_topic_posts.join(''));

    } else { // There are more than 20 posts. Paginate at 20 posts/page.
      num_pages = Math.ceil(num_posts_selected / 20);

      notice('<div class="warn">You have selected ' +
        num_posts_selected + ' posts in this topic. For improved readability, ' +
        'those posts will be published over ' + num_pages + ' pages. Click the ' +
        'preview button to see the first page of posts.' +
        '<div class="controls"><button class="publish-topic">' + 'Create Topic Pages?' +
        '</button></div>', 'notice');

      // Load the first 20 posts in the editor.
      $('#content').html(selected_topic_posts.splice(0, 20).join(''));

      $discourse_fetch.on('click', '.publish-topic', function(e) {

        // The first page has been created. Now create the remaining pages.
        for (page_num = 1; page_num < num_pages; page_num++) {
          content = selected_topic_posts.splice(0, 20).join('');

          topic = {
            'title': $title.val() + ' (page ' + (page_num + 1) + ')',
            'slug': slug($title.val() + ' ' + page_num),
            'order': page_num,
            'author_id': 1,
            'content': content,
            'post_status': 'publish',
            'post_type': 'post',
            'category': [6, 7],
            'action': 'create_post'
          };

          $.post(ajaxurl, topic, function(response) {
            console.log('we got a response', response);
          });
        }

        e.preventDefault();
      });
    }
    e.preventDefault();
  }); // End of load_posts

  //
  $discourse_fetch.on('click', '.unselect-all-posts', function(e) {
    $('.post-select-box').attr('checked', false);
    e.preventDefault();
  });

  $discourse_fetch.on('click', '.select-all-posts', function(e) {
    $('.post-select-box').attr('checked', true);
    e.preventDefault();
  });

  // If there has been an error, clear the error message when the user inputs a new URL.
  clear_message_on_click($('#discourse-url'));

  // Utility functions

  // Clear the message box and remove its classes when $target is clicked.
  function clear_message_on_click($target) {
    $target.click(function() {
      $('#discourse-message').html('').removeClass();
    });
  }

  function set_title(response) {
    var $title = $('#title');
    if ($title.val() === '') {
      $('#title-prompt-text').css('display', 'none');
      $title.val(response['title']);
    }
  }

  function get_url_path(url) {
    var tmp = document.createElement('a');
    tmp.href = url;
    return tmp.pathname;
  }

  function is_url(txt) {
    var url_pattern = /^(?:(?:https?|ftp):\/\/)(?:\S+(?::\S*)?@)?(?:(?!10(?:\.\d{1,3}){3})(?!127(?:\.\d{1,3}){3})(?!169\.254(?:\.\d{1,3}){2})(?!192\.168(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\u00a1-\uffff0-9]+-?)*[a-z\u00a1-\uffff0-9]+)(?:\.(?:[a-z\u00a1-\uffff0-9]+-?)*[a-z\u00a1-\uffff0-9]+)*(?:\.(?:[a-z\u00a1-\uffff]{2,})))(?::\d{2,5})?(?:\/[^\s]*)?$/i;
    return url_pattern.test(txt);
  }

  function get_topic_id(url) {
    var path = get_url_path(url),
        topic_path_pattern = /^\/t\/[\d\w\-]+\/(\d+)\/?(\d+)?$/,
        match;
    match = topic_path_pattern.exec(path);
    if (match) {
      return match[1];
    } else {
      return null;
    }
  }

  function get_base_url(url) {
    var tmp = document.createElement('a'),
      host,
      protocol,
      topic_id;

    tmp.href = url;
    host = tmp.hostname;
    protocol = tmp.protocol;
    topic_id = get_topic_id(url);

    return protocol + '//' + host + '/t/' + topic_id;
  }

  function get_absolute_url_base(url) {
    var tmp = document.createElement('a'),
      host,
      protocol;

    tmp.href = url;
    host = tmp.hostname;
    protocol = tmp.protocol;

    return protocol + '//' + host;
  }

  function slug(str) {
    return str
      .toLowerCase()
      .replace(/ /g,'-')
      .replace(/[^\w-]+/g,'');
  }

  // Make nice dates from the date string -- this could be improved
  function parse_date(date_string) {
    var d = new Date(date_string);
    return d.toLocaleDateString();
  }

  function warning(message) {
    var $message_box = $('#discourse-message');
    $message_box.addClass('warning');
    $message_box.html(message);
  }

  function notice(message, css_class) {
    var $message_box = $('#discourse-message');
    $message_box.addClass(css_class);
    $message_box.html(message);
  }

  function fix_avatar_link(avatar_link, abs_url_base) {
    var fixed = avatar_link.replace(/\{size\}/, 45);
    if (fixed.match(/^\//)) {
      fixed = abs_url_base + fixed;
    }
    return fixed;
  }

  function get_categories() {
    var categories = '';
    $.each($('#categorychecklist').find('input:checkbox'), function() {
      if ($(this).prop('checked')) {
        categories += $(this).val() + ' ';
      }
    });
    return categories;
  }

});


