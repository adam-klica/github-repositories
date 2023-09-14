<?php
/**
 * Plugin Name: GitHub Repositories
 * Description: A plugin to fetch public GitHub repositories and display them as posts, including README files.
 * Version: 1.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 */

// Prevent direct access to the file
defined('ABSPATH') or die('Direct access not allowed');

register_activation_hook(__FILE__, 'gh_repositories_activation');
register_deactivation_hook(__FILE__, 'gh_repositories_deactivation');

function gh_repositories_activation() {
    if(! wp_next_scheduled('gh_fetch_repositories_event')) {
        wp_schedule_event(time(), 'hourly', 'gh_fetch_repositories_event');
    }
}

function gh_repositories_deactivation() {
    wp_clear_scheduled_hook('gh_fetch_repositories_event');
}

function fetch_github_repositories() {
    $response = wp_remote_get('https://api.github.com/repositories');
    $repositories = json_decode(wp_remote_retrieve_body($response), true);

    if(is_array($repositories)) {
        foreach($repositories as $repository) {
            // Fetch README file using repository url and create post 

            $readme_response = wp_remote_get($repository['url'] . '/readme');
            $readme_data = json_decode(wp_remote_retrieve_body($readme_response), true);
            
            $readme_content = '';
            if(!empty($readme_data['content'])) {
                $readme_content = base64_decode($readme_data['content']);
            }

            $post_data = array(
                'post_title'    => $repository['name'],
                'post_content'  => $readme_content,
                'post_status'   => 'publish',
                'post_type'     => 'post',
            );

            wp_insert_post($post_data);
        }
    }
}

add_action('gh_fetch_repositories_event', 'fetch_github_repositories');
?>
