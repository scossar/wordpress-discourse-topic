# wordpress-discourse-topic
Saves a Discourse topic as a WordPress  post

This plugin adds a Discourse topic meta-box to the WordPress admin screen. The meta-box accepts a URL for a Discourse
topic and returns the topic's posts. A topic's posts can be individually selected. If more than 20 posts in a topic
are selected, they will be paginated at 20 posts per page. Post categories are saved with each page. There is a
'discourse_order' meta-data associated with each paged post so that posts can be retrieved in the correct order.

This plugin is still in development. Everything works, but the admin user interface is not as intuitive as it should
be.
