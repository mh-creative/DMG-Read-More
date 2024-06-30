<?php
/*
Plugin Name: DMG Read More
Description: A plugin to add a Gutenberg block for linking to recent posts.
Version: 1.0
Author: Michael Hayes
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue the block scripts
function dmg_read_more_enqueue_block_editor_assets()
{
    wp_enqueue_script(
        'dmg-read-more-block',
        plugins_url('build/main.js', __FILE__),
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components'),
        filemtime(plugin_dir_path(__FILE__) . 'build/main.js')
    );
}
add_action('enqueue_block_editor_assets', 'dmg_read_more_enqueue_block_editor_assets');

// Enqueue the block styles
function dmg_read_more_enqueue_block_assets() {
    wp_enqueue_style(
        'dmg-read-more-block',
        plugins_url('css/style.css', __FILE__),
        array(),
        filemtime(plugin_dir_path(__FILE__) . 'css/style.css')
    );
}
add_action('enqueue_block_assets', 'dmg_read_more_enqueue_block_assets');

// Register the WP-CLI command
if (defined('WP_CLI') && WP_CLI) {
    class DMG_Read_More_CLI {
        /**
         * Search for posts containing the Read More block.
         *
         * ## OPTIONS
         *
         * [--date-before=<date-before>]
         * : The end date for the search.
         *
         * [--date-after=<date-after>]
         * : The start date for the search.
         *
         * @when after_wp_load
         */
        public function search($args, $assoc_args) {
            $date_before = isset($assoc_args['date-before']) ? $assoc_args['date-before'] : date('Y-m-d');
            $date_after = isset($assoc_args['date-after']) ? $assoc_args['date-after'] : date('Y-m-d', strtotime('-30 days'));

            $query_args = array(
                'date_query' => array(
                    array(
                        'after' => $date_after,
                        'before' => $date_before,
                        'inclusive' => true,
                    ),
                ),
                'post_type' => array('post', 'page'), // Include both posts and pages
                'posts_per_page' => -1,
                'fields' => 'ids',
            );

            WP_CLI::log("Running query from $date_after to $date_before...");
            $query = new WP_Query($query_args);
            $posts_found = count($query->posts);
            WP_CLI::log("Found $posts_found posts.");

            if ($posts_found > 0) {
                $matching_posts = [];
                foreach ($query->posts as $post_id) {
                    $post = get_post($post_id);
                    if ($this->contains_read_more_block($post->post_content)) {
                        WP_CLI::log("Post ID $post_id contains the Read More block.");
                        $matching_posts[] = $post_id;
                    }
                }

                if (count($matching_posts) > 0) {
                    WP_CLI::log("Matching post IDs: " . implode(', ', $matching_posts));
                } else {
                    WP_CLI::log('No posts found containing the Read More block.');
                }
            } else {
                WP_CLI::log('No posts found within the specified date range.');
            }
        }

        /**
         * Check if the post content contains the Read More block.
         *
         * @param string $content Post content.
         * @return bool True if the content contains the Read More block, false otherwise.
         */
        private function contains_read_more_block($content) {
            $blocks = parse_blocks($content);
            return $this->check_blocks_for_read_more($blocks);
        }

        /**
         * Recursively check blocks for the Read More block.
         *
         * @param array $blocks Array of blocks.
         * @return bool True if the Read More block is found, false otherwise.
         */
        private function check_blocks_for_read_more($blocks) {
            foreach ($blocks as $block) {
                if ($block['blockName'] === 'dmg/read-more') {
                    return true;
                }
                if (!empty($block['innerBlocks'])) {
                    if ($this->check_blocks_for_read_more($block['innerBlocks'])) {
                        return true;
                    }
                }
            }
            return false;
        }
    }

    WP_CLI::add_command('dmg-read-more', 'DMG_Read_More_CLI');
}
