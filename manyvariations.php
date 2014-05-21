<?php
/*
Plugin Name: Odermedia Many Variations Fix
Plugin URI: http://www.odermedia.de
Description:
Author: Stefan Warnat
Version: 1.02
Author URI: http://www.odermedia.de
*/
define("VARIATIONS_PER_PAGE", 25);

class Odermedia_ManyVariations {
	public function __construct() {
		if(is_admin()) {
			add_action("pre_get_posts", array($this, "hide_product_variations"));
			add_action("woocommerce_product_data_panels", array($this, "add_variation_paging_link"));
			
			add_action( 'wp_ajax_save_variation', array($this, 'action_save_variation' ));
			add_action( 'wp_ajax_page_variation', array($this, 'action_page_variation' ));
			add_action( 'wp_ajax_nopriv_page_variation', array($this, 'action_page_variation' ));
			
			add_action( 'admin_enqueue_scripts', array($this, 'variations_script_method' ));
		}
	}
	
	public function variations_script_method() {
		wp_enqueue_script( 'jquery.form', plugins_url( 'jquery.form.js' , __FILE__ ), array(), '1.0.0', true );
	}

	public function hide_product_variations($Query) {
		if($Query->query_vars["post_type"] == "product_variation") {
			#$Query->query_vars["post_type"] = "empty-result";
			$page = !empty($_REQUEST["variation_page"])?intval($_REQUEST["variation_page"]):1;

			$Query->query_vars["paged"] = $page;
			$Query->query_vars["posts_per_page"] = VARIATIONS_PER_PAGE;
			$Query->query_vars["nopaging"] = 0;
		}
	}
	
	public function add_variation_paging_link() {
        global $wpdb;
        $sql = 'SELECT COUNT(*) as num FROM wp_posts WHERE post_parent = "'.get_the_ID().'" AND post_type = "product_variation"';
        $variationCount = $wpdb->get_var($sql);
		$page = !empty($_REQUEST["variation_page"])?intval($_REQUEST["variation_page"]):1;
		
		require_once("inject.phtml");
	}
	public function action_save_variation() {
		global $post;

		$post_id = (int)$_POST["productId"];
		
		require_once(dirname(__FILE__)."/../woocommerce/includes/admin/post-types/meta-boxes/class-wc-meta-box-product-data.php");
		
		WC_Meta_Box_Product_Data::save_variations( $post_id, $post );

	}
	public function action_page_variation() {
		global $post;

		$post_id = (int)$_POST["productId"];
		$post = get_post($post_id);
		
		require_once(dirname(__FILE__)."/../woocommerce/includes/admin/post-types/meta-boxes/class-wc-meta-box-product-data.php");
		
		WC_Meta_Box_Product_Data::output_variations();
		
		exit();
	}
}

$Odermedia_ManyVariations = new Odermedia_ManyVariations();
