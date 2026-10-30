<?php
/**
 * Plugin Name: Deployment Check
 * Description: Displays a small marker for the Git push, pull, and deploy check.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_footer', static function () {
    echo '<p style="text-align:center">Deployed from GitHub with docker-wp.</p>';
});
