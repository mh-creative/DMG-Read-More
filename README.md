# DMG Read More Plugin

## Description
The **DMG Read More Plugin** is a custom WordPress plugin that provides a Gutenberg block for linking to recent posts. The block allows editors to search for and select a published post, inserting a stylized anchor link into the content.

## Features
- Gutenberg Block: "Read More Link"
  - Allows editors to search and select a post from a list.
  - Displays an anchor link with the selected post's title and URL.
  - The link is styled with the class `dmg-read-more`.

- WP-CLI Command: `dmg-read-more search`
  - Takes optional date-range arguments: `date-before` and `date-after`.
  - Defaults to searching within the last 30 days if no date arguments are provided.
  - Searches for posts within the specified date range containing the aforementioned Gutenberg block.
  - Logs the Post IDs of matching results to STDOUT.
  - Outputs a log message if no posts are found or if any errors are encountered.

## Installation

1. Clone the repository into your WordPress plugins directory e.g with command line / termianl:
 
    ```git clone https://github.com/mh-creative/DMG-Read-More.git wp-content/plugins/dmg-read-more```

2. Navigate to the plugin directory:

    ```cd wp-content/plugins/dmg-read-more```

3. For developers who want to contribute:

- Install dependencies and build the project:

    ```npm install```
    ```npm run build```

4. For users who only want to use the plugin:

The final built files are included, so you can directly activate the plugin through the WordPress admin dashboard without needing to install dependencies or build the project.

## Usage

- Gutenberg Block
  - In the WordPress block editor, add the "Read More Link" block.
  - Use the search input to find and select a post.
  - The block will display an anchor link with the post title and URL.
  
- WP-CLI Command
  - Run the dmg-read-more search command with optional date range arguments:

    ```wp dmg-read-more search --date-after=YYYY-MM-DD --date-before=YYYY-MM-DD```

  - If no dates are provided, the command defaults to the last 30 days.
  - The command will log the Post IDs of posts containing the "Read More" block to STDOUT.

## Development
- Dependencies
  - WordPress
  - Node.js
  - npm

## File Structure
- block.js: Contains the code for the Gutenberg block.
- dmg-read-more.php: Main plugin file.
- build/main.js: Compiled JavaScript for the block editor.

## Scripts
- npm install: Install the project dependencies.
- npm run build: Build the project.

## CSS Styling
- The link is styled with CSS for a blue color ```(#0073aa)``` and a lighter blue on hover ```(#005f8d)```.

## Enqueuing CSS
- CSS is enqueued in the dmg-read-more.php file to style the anchor links.

## Example Code Block - block.js

    import { registerBlockType } from '@wordpress/blocks';
    import { InspectorControls } from '@wordpress/block-editor';
    import { PanelBody, SelectControl, TextControl } from '@wordpress/components';
    import { useState, useEffect } from '@wordpress/element';
    import apiFetch from '@wordpress/api-fetch';

    registerBlockType('dmg/read-more', {
        title: 'Read More Link',
        icon: 'admin-links',
        category: 'common',
        attributes: {
            postId: { type: 'number' },
            postTitle: { type: 'string' },
            postUrl: { type: 'string' },
        },
        edit: function (props) {
            const { attributes, setAttributes } = props;
            const [posts, setPosts] = useState([]);
            const [searchTerm, setSearchTerm] = useState('');
            const [loaded, setLoaded] = useState(false);

            useEffect(() => {
                apiFetch({ path: `/wp/v2/posts?search=${searchTerm}&per_page=100` }).then((posts) => {
                    setPosts(posts);
                    if (!loaded && attributes.postId) {
                        const post = posts.find((post) => post.id === attributes.postId);
                        if (post) {
                            setAttributes({
                                postTitle: post.title.rendered,
                                postUrl: post.link,
                            });
                        }
                        setLoaded(true);
                    }
                });
            }, [searchTerm, loaded]);

            useEffect(() => {
                if (!searchTerm) {
                    apiFetch({ path: '/wp/v2/posts?per_page=100' }).then((posts) => {
                        setPosts(posts);
                        if (!attributes.postId && posts.length > 0) {
                            const defaultPost = posts[0];
                            setAttributes({
                                postId: defaultPost.id,
                                postTitle: defaultPost.title.rendered,
                                postUrl: defaultPost.link,
                            });
                        }
                    });
                }
            }, []);

            const onChangePost = (postId) => {
                const parsedPostId = parseInt(postId, 10);
                if (!isNaN(parsedPostId)) {
                    const post = posts.find((post) => post.id === parsedPostId);
                    if (post) {
                        setAttributes({
                            postId: post.id,
                            postTitle: post.title.rendered,
                            postUrl: post.link,
                        });
                    }
                }
            };

            useEffect(() => {
                if (posts.length > 0 && attributes.postId && !posts.find((post) => post.id === attributes.postId)) {
                    setAttributes({
                        postId: posts[0].id,
                        postTitle: posts[0].title.rendered,
                        postUrl: posts[0].link,
                    });
                }
            }, [posts]);

            return (
                <>
                    <InspectorControls>
                        <PanelBody title="Select a Post">
                            <TextControl
                                label="Search Posts"
                                value={searchTerm}
                                onChange={(term) => setSearchTerm(term)}
                            />
                            <SelectControl
                                label="Select a Post"
                                value={attributes.postId || ''}
                                options={posts.map((post) => ({
                                    label: post.title.rendered,
                                    value: post.id,
                                }))}
                                onChange={(postId) => onChangePost(postId)}
                            />
                        </PanelBody>
                    </InspectorControls>
                    {attributes.postId && attributes.postTitle && attributes.postUrl ? (
                        <p className="dmg-read-more">
                            Read More: <a href={attributes.postUrl}>{attributes.postTitle}</a>
                        </p>
                    ) : (
                        <p className="dmg-read-more">Select a post to display the link.</p>
                    )}
                </>
            );
        },
        save: function (props) {
            const { attributes } = props;
            return (
                <p className="dmg-read-more">
                    Read More: <a href={attributes.postUrl}>{attributes.postTitle}</a>
                </p>
            );
        },
    });

## Example WP-CLI Command - dmg-read-more.php

    <?php
    if (defined('WP_CLI') && WP_CLI) {
        class DMG_Read_More_CLI {
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
                    'post_type' => array('post', 'page'),
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                );

                WP_CLI::log("Running query from $date_after to $date_before...");
                $query = new WP_Query($query_args);
                $posts_found = count($query->posts);
                WP_CLI::log("Found $posts_found posts.");

                if ($posts_found > 0) {
                    $matched_posts = [];
                    foreach ($query->posts as $post_id) {
                        $post = get_post($post_id);
                        if ($this->contains_read_more_block($post->post_content)) {
                            $matched_posts[] = $post_id;
                            WP_CLI::log("Post ID $post_id contains the Read More block.");
                        } else {
                            WP_CLI::log("Post ID $post_id does not contain the Read More block.");
                        }
                    }

                    if (empty($matched_posts)) {
                        WP_CLI::log('No posts found containing the Read More block.');
                    } else {
                        WP_CLI::log('Matching Post IDs: ' . implode(', ', $matched_posts));
                    }
                } else {
                    WP_CLI::log('No posts found.');
                }
            }

            private function contains_read_more_block($content) {
                $blocks = parse_blocks($content);
                foreach ($blocks as $block) {
                    if ($block['blockName'] === 'dmg/read-more') {
                        return true;
                    }
                    if (!empty($block['innerBlocks'])) {
                        foreach ($block['innerBlocks'] as $innerBlock) {
                            if ($innerBlock['blockName'] === 'dmg/read-more') {
                                return true;
                            }
                        }
                    }
                }
                return false;
            }
        }

        WP_CLI::add_command('dmg-read-more', 'DMG_Read_More_CLI');
    }

## Licence
- This `README.md` provides a comprehensive overview of the plugin, including its features, installation instructions, usage, and development details. It clarifies the necessity of installing dependencies and building the project for developers who wish to contribute, while also indicating that the final build files are included for users who only want to use the plugin.

## Author
Michael Hayes