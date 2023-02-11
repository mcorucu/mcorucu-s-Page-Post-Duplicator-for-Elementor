<?php
/*
Plugin Name: mcorucu's Page & Post Duplicator for Elementor
Plugin URI: https://mcorucu.com/page-post-duplicator
Description: A plugin for duplicating Elementor pages, posts, and widgets.
Version: 1.0
Author: mcorucu
Author URI: https://mcorucu.com
License: GPLv2 or later
Text Domain: mcorucu-page-post-duplicator
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class MCoruCu_Page_Post_Duplicator {

	private static $instance = null;

	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_action_duplicate_elementor_post', [ $this, 'duplicate_elementor_post' ] );
		add_filter( 'post_row_actions', [ $this, 'add_duplicate_post_link' ], 10, 2 );
		add_action( 'elementor/widget/duplicate', [ $this, 'duplicate_elementor_widget' ], 10, 2 );
		add_action( 'admin_menu', [ $this, 'add_options_page' ] );
	}

	public function duplicate_elementor_post( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		$new_post_args = [
			'post_title'   => $post->post_title . ' - Copy',
			'post_content' => $post->post_content,
			'post_type'    => $post->post_type,
			'post_status'  => 'draft',
			'post_author'  => get_current_user_id(),
		];

		$new_post_id = wp_insert_post( $new_post_args );
		if ( ! $new_post_id ) {
			return;
		}

		$meta = get_post_meta( $post_id );
		foreach ( $meta as $key => $value ) {
			update_post_meta( $new_post_id, $key, maybe_unserialize( $value[0] ) );
		}

		wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ) );
		exit;
	}

	public function add_duplicate_post_link( $actions, $post ) {
		if ( 'elementor_library' === $post->post_type ) {
			$actions['duplicate'] = '<a href="' . wp_nonce_url(
				admin
