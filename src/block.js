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
        postId: {
            type: 'number',
        },
        postTitle: {
            type: 'string',
        },
        postUrl: {
            type: 'string',
        },
    },
    edit: function (props) {
        const { attributes, setAttributes } = props;
        const [posts, setPosts] = useState([]);
        const [searchTerm, setSearchTerm] = useState('');
        const [loaded, setLoaded] = useState(false);

        // Fetch posts based on the search term
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

        // Fetch recent posts on initial render
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
            // Ensure postId is a valid number
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

        // Ensure block content updates when posts change due to search term
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
