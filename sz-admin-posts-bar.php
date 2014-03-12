<?php
/**
 * Plugin Name: Admin Posts Bar
 * Description: A plugin which show posts info on admin bar
 * Version:     1.0.0
 * Author:      Guido Scialfa
 * Author URI:  http://www.guidoscialfa.com
 * License:     GPL2
 *
 *    Copyright (C) 2013  Guido Scialfa <dev@guidoscialfa.com>
 *
 *    This program is free software; you can redistribute it and/or
 *    modify it under the terms of the GNU General Public License
 *    as published by the Free Software Foundation; either version 2
 *    of the License, or (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program; if not, write to the Free Software
 *    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

// Prevent Direct Access
if( ! defined( 'ABSPATH' ) ) exit;

class SZ_Admin_Posts_Bar {

	/**
	 * Class Instance
	 *
	 * @since  1.0
	 * @access private
	 *
	 * @var object SZ_Admin_Posts_Bar
	 */
	private static $sz_admin_posts_bar = false;

	/**
	 * Plugin Path
	 *
	 * @since  1.0
	 * @access private
	 *
	 * @var string
	 */
	private $plugin_path;

	/**
	 * Css path
	 *
	 * @since  1.0
	 * @access private
	 *
	 * @var string
	 */
	private $css_url;

	/**
	 * Enqueue Scripts
	 *
	 * @since  1.0
	 *
	 * @return void
	 */
	public function sz_enqueue_scripts() {
		global $pagenow;

		if( ( is_admin() && ( $pagenow == 'post.php' ) ) || is_singular() ) {
			wp_enqueue_style( 'sz-admin-posts-bar' );
		}
	}

	/**
	 * Plugin Init
	 *
	 * @since   1.0
	 *
	 * @return void
	 */
	public function sz_init() {
		if( !is_admin_bar_showing() && !current_user_can('edit-posts') ) {
			return;
		}

		wp_register_style( 'sz-admin-posts-bar', $this->css_url . '/style.css', null, '1.0', 'screen' );

		add_action( 'admin_enqueue_scripts', array( $this, 'sz_enqueue_scripts' ) );
		add_action( 'wp_enqueue_scripts',    array( $this, 'sz_enqueue_scripts' ) );
	}

	/**
	 * Add admin bar Nodes and groups
	 *
	 * @since   1.0
	 *
	 * @return void
	 */
	public function sz_admin_bar_menu() {
		global $post, $pagenow, $wp_admin_bar;

		if( ( is_admin() && ( $pagenow == 'post.php' ) ) || is_singular() ) {
			// Get post author
			$post_author = get_the_author_meta( 'user_nicename', $post->post_author );

			// Get post link based on context
			if( is_admin() ) {
				$post_link['permalink'] = get_permalink( $post->ID );
				$post_link['title']     = __( 'Show post page' );
			} else {
				$post_link['permalink'] = admin_url( 'post.php?post=' . $post->ID . '&amp;action=edit' );
				$post_link['title']     = __( 'Edit post' );
			}

			// Add top level menu
			$wp_admin_bar->add_node( array(
				'id'     => 'sz-admin-posts-bar',
				'title'  => __( 'Current Post' ) . ' : ' . $post->ID,
				'parent' => null,
				'href'   => $post_link['permalink'],
				'meta'   => array(
					'class'  => 'sz-admin-posts-bar',
					'title'  => $post_link['title'],
					'target' => '_blank',
				)
			) );

			// Add Group
			$wp_admin_bar->add_group( array(
				'id'     => 'sz-post-info-group',
				'parent' => 'sz-admin-posts-bar',
			) );

			// Post Title
			$wp_admin_bar->add_node( array(
				'id'     => 'sz-post-title',
				'parent' => 'sz-post-info-group',
				'title'  => __( 'Title:' ) . ' ' . '<span class="gray">' . $post->post_title . '</span>',
			) );

			// Post Author
			$wp_admin_bar->add_node( array(
				'id'     => 'sz-post-author',
				'parent' => 'sz-post-info-group',
				'title'  => __( 'Author:') . ' ' . '<span class="gray">' . ucfirst( $post_author ) . '</span>',
			) );

			// Post Status
			$wp_admin_bar->add_node( array(
				'id'     => 'sz-post-status',
				'parent' => 'sz-post-info-group',
				'title'  => __( 'Status:' ) . ' ' . '<span class="gray">' . ucfirst( $post->post_status ) . '</span>',
			) );

			// Comment Status
			$wp_admin_bar->add_node( array(
				'id'     => 'sz-post-comment-status',
				'parent' => 'sz-post-info-group',
				'title'  => __( 'Comment Status:' ) . ' ' . '<span class="gray">' . ucfirst( $post->comment_status ) . '</span>',
			) );

			// Post modified
			$wp_admin_bar->add_node( array(
				'id'     => 'sz-post-modified',
				'parent' => 'sz-post-info-group',
				'title'  => __( 'Modified:' ) . ' ' . '<span class="gray">' . ucfirst( $post->post_modified ) . '</span>',
			) );

			// Post Parent Title
			if( $post->post_parent != 0 ) {
				$wp_admin_bar->add_node( array(
					'id'     => 'sz-post-parent-title',
					'parent' => 'sz-post-info-group',
					'title'  => __( 'Parent Title:' ) . ' ' . '<span class="gray">' . get_the_title( $post->post_parent ) . '</span>',
				) );
			}

			// Post Parent ID
			$wp_admin_bar->add_node( array(
				'id'     => 'sz-post-parent-id',
				'parent' => 'sz-post-info-group',
				'title'  => __( 'Parent ID:' ) . ' ' . '<span class="gray">' . $post->post_parent . '</span>',
			) );

			// Menu Order
			$wp_admin_bar->add_node( array(
				'id'     => 'sz-post-order',
				'parent' => 'sz-post-info-group',
				'title'  => __( 'Order:' ) . ' ' . '<span class="gray">' . $post->menu_order . '</span>',
			) );

			// Post Type
			$wp_admin_bar->add_node( array(
				'id'     => 'sz-post-type',
				'parent' => 'sz-post-info-group',
				'title'  => __( 'Post Type:' ) . ' ' . '<span class="gray">' . ucfirst( $post->post_type ) . '</span>',
			) );

			// Post ID
			$wp_admin_bar->add_node( array(
				'id'     => 'sz-post-id',
				'parent' => 'sz-post-info-group',
				'title'  => __( 'ID:' ) . ' ' . '<span class="blue">' . $post->ID . '</span>',
			) );
		}
	}

	/**
	 * Class Singleton
	 */
	public static function get_instance() {
		if( ! self::$sz_admin_posts_bar ) {
			self::$sz_admin_posts_bar = new self;
		}

		return self::$sz_admin_posts_bar;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->plugin_path = untrailingslashit( plugin_dir_path( dirname( __FILE__ ) ) );
		$this->css_url     = plugins_url( 'css', __FILE__ );

		// Plugin Init
		add_action( 'init', array( $this, 'sz_init' ) );

		// Admin Bar Action
		add_action( 'admin_bar_menu', array( $this, 'sz_admin_bar_menu' ), 999 );
	}
}

// Ok, get the instance
SZ_Admin_Posts_Bar::get_instance();