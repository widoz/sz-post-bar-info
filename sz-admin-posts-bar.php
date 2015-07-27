<?php
/**
 * Plugin Name: Admin Posts Bar
 * Description: A plugin which show posts info on admin bar
 * Version:     1.0.1
 * Author:      Guido Scialfa
 * Author URI:  http://www.guidoscialfa.com
 * License:     GPL2
 *
 *    Copyright (C) 2013  Guido Scialfa
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
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

		if ( ( is_admin() && ( $pagenow == 'post.php' ) ) || is_singular() ) {
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
		if ( ! is_admin_bar_showing() && ! current_user_can( 'edit-posts' ) ) {
			return;
		}

		wp_register_style( 'sz-admin-posts-bar', $this->css_url . '/style.css', null, '1.0', 'screen' );

		add_action( 'admin_enqueue_scripts', array( $this, 'sz_enqueue_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'sz_enqueue_scripts' ) );
	}

	/**
	 * Create the Node args
	 *
	 * @since 1.0.2
	 *
	 * @param array $field The current node field
	 * @param int $index The current array element index
	 * @param array $node The current node
	 *
	 * @return array The node argument
	 */
	function _create_node_args( &$field, $index = null, $node = null ) {

		global $post;

		$parent_node = $node['args']['parent'];
		$node_id     = $node['prefix'] . str_replace( '_', '-', strtolower( $field ) );

		if ( 'parent' === $field ) {
			$the_post = get_post( $node['post_parent'] );
			$value    = $the_post->ID;
		} else {
			$the_post = $post;
			$value    = ucwords( $the_post->$field );
		}

		if ( $post ) {
			$field = array(
				'id'     => $node_id,
				'parent' => $parent_node,
				'title'  => sprintf(
					'<span class="sz-item">' . __( '%s' ) . '</span> %s',
					ucwords( str_replace( array( '-', '_' ), ' ', $field ) ) . ':',
					$value
				),
			);
		}

	}

	/**
	 * Render Nodes and Groups
	 *
	 * @since 1.0.2
	 *
	 * @param array $nodes The nodes to render. Default empty
	 *
	 * @return void
	 */
	private function _render_bar_nodes( $nodes = array() ) {

		global $wp_admin_bar;

		if ( empty( $nodes ) ) {
			$nodes = $this->nodes;
		}

		// Render the nodes
		if ( $nodes ) {
			foreach ( $nodes as $what => $node ) {
				// Node Or Group?
				switch ( $what ) {
					case 'group' :
						$wp_admin_bar->add_group( $node['args'] );

						// Add Nodes
						array_walk( $node['fields'], array( $this, '_create_node_args' ), $node );
						array_walk( $node['fields'], array( $wp_admin_bar, 'add_node' ) );
						break;

					default :
						$args = isset( $node['args'] ) ? $node['args'] : $node;
						$wp_admin_bar->add_node( $args );
						break;
				}
			}
		}
	}

	/**
	 * Define admin bar Nodes and Groups
	 *
	 * @since   1.0
	 *
	 * @return void
	 */
	public function sz_admin_bar_menu() {
		global $post, $pagenow;

		if ( ( ( is_admin() && ( $pagenow == 'post.php' ) ) || is_singular() ) && is_admin_bar_showing() ) {
			// Get post author
			$post_author = get_the_author_meta( 'user_nicename', $post->post_author );

			// Get post link based on context
			if ( is_admin() ) {
				$post_link['permalink'] = get_permalink( $post->ID );
				$post_link['title']     = __( 'Show post page' );
			} else {
				$post_link['permalink'] = admin_url( 'post.php?post=' . $post->ID . '&amp;action=edit' );
				$post_link['title']     = __( 'Edit post' );
			}

			// Group and Nodes
			$this->nodes = array(
				'node'  => array(
					'args' => array(
						'id'     => 'sz-admin-posts-bar',
						'title'  => __( 'Current Post' ) . ' : ' . $post->ID,
						'parent' => null,
						'href'   => $post_link['permalink'],
						'meta'   => array(
							'class'  => 'sz-admin-posts-bar',
							'title'  => $post_link['title'],
							'target' => '_blank',
						)
					),
				),
				'group' => array(
					'prefix'      => 'sz-post-',
					'post_parent' => $post->post_parent,
					'args'        => array(
						'id'     => 'sz-post-info-group',
						'parent' => 'sz-admin-posts-bar',
					),
					'fields'      => array(
						'post_title',
						'parent',
						'post_author',
						'post_status',
						'comment_status',
						'post_modified',
						'menu_order',
						'post_type',
						'ID',
					),
				),
			);

			/**
			 * Filter the node array
			 *
			 * @since 1.0.2
			 *
			 * @param array $node The array that contain nodes and groups
			 */
			$this->nodes = apply_filters( 'sz_post_bar_info_nodes', $this->nodes );

			$this->_render_bar_nodes();
		}
	}

	/**
	 * Class Singleton
	 */
	public static function get_instance() {
		if ( ! self::$sz_admin_posts_bar ) {
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
		$this->nodes       = '';

		// Plugin Init
		add_action( 'init', array( $this, 'sz_init' ) );

		// Admin Bar Action
		add_action( 'admin_bar_menu', array( $this, 'sz_admin_bar_menu' ), 999 );
	}
}

// Ok, get the instance
SZ_Admin_Posts_Bar::get_instance();