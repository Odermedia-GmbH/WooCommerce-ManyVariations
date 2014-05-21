<?php
/*
Plugin Name: Odermedia Many Variations Fix
Plugin URI: http://www.odermedia.de
Description:
Author: Stefan Warnat
Version: 1.02
Author URI: http://www.odermedia.de
*/
define("VARIATIONS_PER_PAGE", 10);

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
		?>
		<script type="text/javascript">
			var variationPage = <?php echo $page ?>;
			var VariationsPerPage = <?php echo VARIATIONS_PER_PAGE ?>;
			var countVariations = <?php echo $variationCount; ?>;
			function init() {
                var html = '';
				if(variationPage > 1) {
					html += "<button onclick='loadPrevVariationPage();return false;' class='button' id='prev_variations_page_link'>&laquo; <?php echo __('Go to the previous page'); ?></button>&nbsp;&nbsp;&nbsp;&nbsp;";
				}

                html += ("<span style='line-height:26px;padding:0 10px;'><?php __('Page').' '.__('Variations','woocommerce'); ?>" + variationPage + " / " + Math.floor(countVariations / VariationsPerPage + 0.5) + " </span>");

				if(jQuery(".woocommerce_variations .woocommerce_variation").length == <?php echo VARIATIONS_PER_PAGE ?>) {
                    html += ("<button onclick='loadNextVariationPage();return false;' class='button' id='next_variations_page_link'>&raquo; <?php echo __('Go to the next page'); ?></button>");
				}

                if(html.length > 0) {
                    jQuery('.woocommerce_variations').append('<div style="background-color:#eee;padding:2px;width:500px;margin:auto;text-align:center;">' + html + '</div>');
                }
			}
			init();
			
			function loadPrevVariationPage() {
				if(variationPage > 1) {
					variationPage = variationPage - 1;
					loadVariationPage();
				}
			} 
			function loadNextVariationPage() {
				variationPage = variationPage + 1;
				loadVariationPage();
			} 
			
			function fixMenuOrder() {
				var start = (variationPage - 1) * <?php echo VARIATIONS_PER_PAGE ?>;
				
				jQuery(".variation_menu_order").each(function(index, value) {
					jQuery(value).val(start);
					start++;
				});
			}
			function loadVariationPage() {
				jQuery('#variable_product_options').block({ message: "Variationen werden gespeichert und geladen ..." }); 
				
				var queryString2 = jQuery("#variable_product_options input, #variable_product_options select").fieldSerialize();

				queryString2 += "&action=save_variation&productId=<?php echo get_the_ID(); ?>&variation_page=" + variationPage;
				queryString1 = "action=page_variation&productId=<?php echo get_the_ID(); ?>&variation_page=" + variationPage;
				
				jQuery.post(ajaxurl, queryString1, function(response) {
					
					jQuery("#variable_product_options").replaceWith(response);
					
					// Save last variations
					jQuery.post(ajaxurl, queryString2);
					
					// Open/close
					jQuery('.wc-metaboxes-wrapper').on('click', '.wc-metabox h3', function(event){
						// If the user clicks on some form input inside the h3, like a select list (for variations), the box should not be toggled
					
						if (jQuery(event.target).filter(':input, option').length) return;

						jQuery(this).next('.wc-metabox-content').toggle();
					})
					.on('click', '.expand_all', function(event){
						jQuery(this).closest('.wc-metaboxes-wrapper').find('.wc-metabox > table').show();
						return false;
					})
					.on('click', '.close_all', function(event){
						jQuery(this).closest('.wc-metaboxes-wrapper').find('.wc-metabox > table').hide();
						return false;
					});

					jQuery('.wc-metabox.closed').each(function(){
						jQuery(this).find('.wc-metabox-content').hide();
					});

					init();
					fixMenuOrder();
				});
			}
			
		</script>
		<?
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
