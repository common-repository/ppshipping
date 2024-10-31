<?php
/*
Plugin Name: ppshipping
Plugin URI: http://www.alwynmalan.co.za
Description: A WooCommerce shipping plugin calculating shipping costs through the PP API
Version: 2.6.4
Author: Alwyn Malan
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	/**
	 * Load the ppshipping method class
	 */
	function ppshipping_shipping_method_init() {

		if ( ! class_exists( 'PP_WC_Shipping_Method' ) ) {

			/**
			 * Parcel Perfect Shipping Method Class
			 */
			class PP_WC_Shipping_Method extends WC_Shipping_Method {

                /**
                 * Private Variable used for instances of class outside of Class
                 */
				protected static $instance = NULL;

                /**
                 * PP Shipping Class Constructor
                 */
				public function __construct( $instance_id = 0 ) {
					$this->id                 = 'ppshipping'; // Id for your shipping method. Should be uunique.
					$this->instance_id        = absint( $instance_id );
					$this->method_title       = __( 'Parcel Perfect' );  // Title shown in admin
					$this->method_description = __( 'Parcel Perfect Shipping Method' ); // Description shown in admin

					$this->supports = array(
						'settings',
						'shipping-zones',
						'instance-settings',
						'instance-settings-modal',
					);

					$this->title = "Parcel Perfect"; // This can be added as an setting but for this example its forced.

					$this->init();
				}

                /**
                 * Method to instantiate class to access option values
                 */
                public static function get_instance()
                {
                    if ( NULL === self::$instance )
                        self::$instance = new self;

                    return self::$instance;
                }

				/**
				 * Init your settings
				 *
				 * @access public
				 * @return void
				 */
				public function init() {

					// Load the settings API
					$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
					$this->init_settings(); // This is part of the settings API. Loads settings you previously init.

					$this->enabled = $this->get_option( 'enabled' );

					if ( $this->get_option( 'account_id' ) == '' ) {
					    $this->enabled = 'no';
                    }

					// Save settings in admin if you have any defined
					add_action( 'woocommerce_update_options_shipping_' . $this->id, array(
						$this,
						'process_admin_options'
					) );
				}

				/**
				 * Initialise Gateway Settings Form Fields
				 *
				 * @access public
				 * @return void
				 */
				public function init_form_fields() {
					$settings = get_option('woocommerce_ppshipping_settings',array());
					
					if (!empty($settings['shop_place_code'])) {
						$place = explode(' - ',$settings['shop_place_code']);
						
						if (empty($place[0])) {
							$place[0] = '';
						}
						
						if (empty($place[1])) {
							$place[1] = '';
						}
					}
					
					$this->form_fields = array(
						'enabled'     => array(
							'title'       => __( 'Enable PP', 'ppshipping' ),
							'description' => __( 'Enables PP as a shipping option on the checkout', 'ppshipping' ),
							'type'        => 'checkbox',
							'label'       => __( 'Enabled', 'ppshipping' )
						),
						'account_license'  => array(
							'title'       => __( 'License Number', 'ppshipping' ),
							'description' => __( 'Your Account License Number', 'ppshipping' ),
							'type'        => 'text',
						),
						'account_auth'  => array(
							'title'       => __( 'Authorisation Number', 'ppshipping' ),
							'description' => __( 'Your Account Authorisation Number', 'ppshipping' ),
							'type'        => 'text',
						),
                        'pp_qc_account_url'  => array(
                            'title'       => __( 'Perfect Parcel ecomService API Url', 'ppshipping' ),
                            'description' => __( 'URL for Perfect Parcel ecomService API calls.  Includes shipping cost quotations and registration of collections and waybills.  Ideally used when shippings costs need to be calculated.', 'ppshipping' ),
                            'type'        => 'text',
                        ),
						'account_id'  => array(
							'title'       => __( 'Perfect Parcel ecomService Username', 'ppshipping' ),
							'description' => __( 'Your Perfect Parcel ecomService Username', 'ppshipping' ),
							'type'        => 'text',
						),
                        'account_pass'  => array(
                            'title'       => __( 'Perfect Parcel ecomService Password', 'ppshipping' ),
                            'description' => __( 'Your Perfect Parcel ecomService Password', 'ppshipping' ),
                            'type'        => 'text',
                        ),
						'pp_w_account_url'  => array(
                            'title'       => __( 'Perfect Parcel integrationService API Url', 'ppshipping' ),
                            'description' => __( 'URL for Perfect Parcel integrationService API calls.  Registers collection and waybills.  Ideally used when flat rate shipping applies.', 'ppshipping' ),
                            'type'        => 'text',
                        ),
						'account_w_id'  => array(
							'title'       => __( 'Perfect Parcel integrationService Username', 'ppshipping' ),
							'description' => __( 'Your Perfect Parcel integrationService Username', 'ppshipping' ),
							'type'        => 'text',
						),
                        'account_w_pass'  => array(
                            'title'       => __( 'Perfect Parcel integrationService Password', 'ppshipping' ),
                            'description' => __( 'Your Perfect Parcel integrationService Password', 'ppshipping' ),
                            'type'        => 'text',
                        ),
						'account_ref'  => array(
                            'title'       => __( 'Waybill and Reference Abbreviation', 'ppshipping' ),
                            'description' => __( 'Your Perfect Parcel Abbreviation Code (3 letters)', 'ppshipping' ),
                            'type'        => 'text',
                        ),
						'shop_place_code'  => array(
                            'title'       => __( 'Shop Origin Place Code', 'ppshipping' ),
                            'description' => __( 'Shop Origin Place Code', 'ppshipping' ),
							'class'		  => 'origin_shop_code',
                            'type'        => 'text',
						),
						'options'     => array(
							'title'       => __( 'Delivery Options', 'ppshipping' ),
							'description' => __( 'Enables the applicable delivery options', 'ppshipping' ),
							'default'     => array( 'NDX', 'ECO' ),
							'type'        => 'multiselect',
							'options'     => $this->ppshipping_get_shipping_options()
						),
						'email_enabled'     => array(
							'title'       => __( 'Enable PP Email', 'ppshipping' ),
							'description' => __( 'Enables E-mail to specified address when order is complete.  Sends waybill information.', 'ppshipping' ),
							'type'        => 'checkbox',
							'label'       => __( 'Email Enabled', 'ppshipping' )
						),
                        'account_email'  => array(
                            'title'       => __( 'E-mail', 'ppshipping' ),
                            'description' => __( 'E-mail address to send waybill information to when orders are set to "complete".', 'ppshipping' ),
                            'type'        => 'text',
                        ),
					);

				}

				/**
				 * Display the options
				 *
				 * @access public
				 * @return void
				 */
				public function admin_options() {
					?>
                    <h2><?php _e( 'DO PP Shipping', 'woocommerce' ); ?></h2>
                    <table class="form-table">
						<?php $this->generate_settings_html(); ?>
                    </table> <?php
				}

				/**
				 * calculate_shipping function.
				 *
				 * @access public
				 *
				 * @param mixed $package
				 *
				 * @return void
				 */
				public function calculate_shipping( $package = array() ) {
					if ( $this->settings['enabled'] !== 'yes' ||
                         empty($package['destination']['address_1']) ||
					     empty($package['destination']['suburb']) ||
					     empty($package['destination']['postcode'])
                    ) {
						return;
					}
					
					global $wpdb, $woocommerce;
					
					$settings = get_option('woocommerce_ppshipping_settings',array());
					$dest_place_code = $package['destination']['suburb_code'];
					
					$place = explode(' - ',$settings['shop_place_code']);
					
                    $shipping_reference = sanitize_text_field($settings['account_ref']).'-'.date('Ymd').'-'.date('His');
					
					$Quote = array(
						'origpers'			=>	get_bloginfo('name'),
						'origperadd1'		=>	get_option( 'woocommerce_store_address' ),
						'origperadd2'		=>	get_option( 'woocommerce_store_address_2' ),
						'origtown'			=>	get_option( 'woocommerce_store_address_2' ),
						'origplace'			=>	$place[1],
						'origperpcode'		=>	get_option( 'woocommerce_store_postcode' ),
						'origpercontact'	=>	get_bloginfo('name'),
						'origperemail'		=>	get_option( 'admin_email' ),
						'destplace'			=>	(string)$dest_place_code,
						'reference'			=>	$shipping_reference,
						'package'			=>	$package
					);

					$items = $woocommerce->cart->get_cart();

					$pp_cart = ppshipping_get_cart($items);
					
					$headers = array(
						"Content-Type" =>  "application/json",
						"request-host" =>  site_url(),
						"access-license" => sanitize_text_field($settings['account_license']),
						"authentication-token" => sanitize_text_field($settings['account_auth'])
					);
					
                    $request_place_body = array(
						'PP_Url'			=> sanitize_text_field($settings['pp_qc_account_url']),
						'PP_User'			=> sanitize_text_field($settings['account_id']),
						'PP_Password'		=> sanitize_text_field($settings['account_pass']),
						'RequestBody'		=> array(
												'Quote' =>	$Quote,
												'Cart'	=>	$pp_cart
											)
					);
					
					$result = ppshipping_make_http_request('parcelperfect/quote',$headers,$request_place_body);
					
					if (empty($result) || (!empty($result->errorcode) && $result->errorcode != 0)) {
						return;
					}

					$shipping_options = $this->ppshipping_get_shipping_options();

                    /**
                     * Loop through services allowed and add rate to checkout page
                     */
                    foreach ($result->results[0]->rates as $rate) {
                        if (isset($shipping_options[$rate->service])) {
							
							$datetime = new DateTime();
							$date = $datetime->createFromFormat('Y-m-d', $rate->duedate);
							
							$date->modify('+1 day');
							$start = $date->format('Y-m-d');
							
							$date->modify('+1 day');
							$end = $date->format('Y-m-d');
							
							$delivery = $this->ppshipping_get_days($start).' - '.$this->ppshipping_get_days($end).' days<br />';
	
                            $this->add_rate( array(
								'id'       => 's-'.$rate->service.'-q-'.$result->results[0]->quoteno,
								'label'    => 'Parcel Perfect ('.$rate->service.') '.$delivery,
								'cost'     => $rate->total,
								'calc_tax' => 'per_order'
							));
                        }
                    }
				}

				/**
				 * The list of shipping options
				 *
				 * @return array
				 */
				private function ppshipping_get_shipping_options() {
				    return array(
						'IPP' => __( 'International PP', 'ppshipping' ),
						'ONX' => __( 'Overnight Air', 'ppshipping' ),
						'ECO' => __( 'Economy Road', 'ppshipping' ),
						'BUD' => __( 'Budget/Economy', 'ppshipping' ),
						'EXR' => __( 'Express Road', 'ppshipping' ),
						'INC' => __( 'Incity Express', 'ppshipping' ),
						'SDX' => __( 'Same day Express', 'ppshipping' ),
						'DWN' => __( 'Domestic Wine', 'ppshipping'),
						'RDF' => __( 'Economy Domestic Road Freight', 'ppshipping')
					);
				}
				
				private function ppshipping_get_days($date) {
					$start = strtotime(date('Y-m-d'));
					$end = strtotime($date);

					$days_between = ceil(abs($end - $start) / 86400);

					return $days_between;	
				}
			}
		}
	}

	add_action( 'woocommerce_shipping_init', 'ppshipping_shipping_method_init' );

	/**
	 * Add the PP shipping method
	 *
	 * @param $methods
	 *
	 * @return mixed
	 */
	function ppshipping_add_shipping_method( $methods ) {
		$methods['ppshipping'] = 'PP_WC_Shipping_Method';

		return $methods;
	}

	add_filter( 'woocommerce_shipping_methods', 'ppshipping_add_shipping_method' );


	function ppshipping_admin_notice() {
		global $pagenow;

		if ( woocommerce_settings_get_option( 'woocommerce_ppshipping_settings[enabled]' ) == 'yes'
        && is_plugin_active( 'ppshipping/ppshipping.php' )) {

			//woocommerce_currency
			if ( $pagenow == 'admin.php' && $_GET['page'] == 'wc-settings'
			     && get_option( 'woocommerce_currency' ) != 'ZAR'
			) {
				echo '<div class="notice notice-warning is-dismissible">
                <p>Parcel Perfect only supports South Africa Rands. Please correct this on the 
                <a href="' . get_admin_url( null, 'admin.php?page=wc-settings&tab=general' ) . '"
                >Product Settings</a></p>
            </div>';
			}

			//only cm and kg supported
			if ( $pagenow == 'admin.php' && $_GET['page'] == 'wc-settings'
			     && ( get_option( 'woocommerce_dimension_unit' ) != 'cm'
			          || get_option( 'woocommerce_weight_unit' ) != 'kg' )
			) {
				echo '<div class="notice notice-warning is-dismissible">
             <p>Parcel Perfect requires product dimensions in cm and weight in kg. Please correct this on the 
             <a href="' . get_admin_url( null, 'admin.php?page=wc-settings&tab=products&section=' ) . '"
             >Product Settings</a></p>
         </div>';
			}
			
			//woocommerce store address
			if ( $pagenow == 'admin.php' && $_GET['page'] == 'wc-settings'
			     && empty(get_option( 'woocommerce_store_address' ))
			) {
				echo '<div class="notice notice-warning is-dismissible">
                <p>Parcel Perfect Shipping require the Woocommerce Store Address Line 1 to be filled out. Please correct this on the 
                <a href="' . get_admin_url( null, 'admin.php?page=wc-settings&tab=general' ) . '"
                >Product Settings</a></p>
            </div>';
			}
			
			if ( $pagenow == 'admin.php' && $_GET['page'] == 'wc-settings'
			     && empty(get_option( 'woocommerce_store_city' ))
			) {
				echo '<div class="notice notice-warning is-dismissible">
                <p>Parcel Perfect Shipping require the Woocommerce Store Address City to be filled out. Please correct this on the 
                <a href="' . get_admin_url( null, 'admin.php?page=wc-settings&tab=general' ) . '"
                >Product Settings</a></p>
            </div>';
			}
			
			if ( $pagenow == 'admin.php' && $_GET['page'] == 'wc-settings'
			     && empty(get_option( 'woocommerce_store_postcode' ))
			) {
				echo '<div class="notice notice-warning is-dismissible">
                <p>Parcel Perfect Shipping require the Woocommerce Store Address Postal Code to be filled out. Please correct this on the 
                <a href="' . get_admin_url( null, 'admin.php?page=wc-settings&tab=general' ) . '"
                >Product Settings</a></p>
            </div>';
			}
		}

	}

	add_action( 'admin_notices', 'ppshipping_admin_notice' );
	
	// Adding Meta container admin shop_order pages
    add_action( 'add_meta_boxes', 'ppshipping_mv_add_pp_suburb_meta_boxes' );
    if ( ! function_exists( 'ppshipping_mv_add_pp_suburb_meta_boxes' ) )
    {
        function ppshipping_mv_add_pp_suburb_meta_boxes()
        {
            add_meta_box( 'mv_pp_suburb_information_fields', __('Suburb Information','woocommerce'), 'ppshipping_mv_add_delivery_suburb_edit_box', 'shop_order', 'side', 'high' );
        }
    }

    /**
     * Adding PP information (Quote #, Ref # and Waybill #) in a block on the admin order page
     */
    if ( ! function_exists( 'ppshipping_mv_add_delivery_suburb_edit_box' ) )
    {
        function ppshipping_mv_add_delivery_suburb_edit_box()
        {
            global $post,$wpdb;
			
			$quote_no = get_post_meta( $post->ID, 'order_pp_quote', true );
			
			$settings = get_option('woocommerce_ppshipping_settings',array());

            echo '<input type="hidden" name="mv_other_meta_field_nonce" value="' . wp_create_nonce() . '">';
			
			echo '<div id="ppshipping_suburb_details">';
			
			echo '<p>Delivery Suburb:</p>
					 <input id="pp_edit_suburb" type="text" name="pp_edit_suburb" value="'.esc_attr(get_post_meta( $post->ID, 'pp_shipping_suburb_name', true )).' - '.esc_attr(get_post_meta( $post->ID, 'pp_shipping_suburb', true )).' " readonly style="width: 100% !important;" class="pp_edit_suburb" />
					 
					 <input id="pp_edit_suburb_confirm" type="hidden" name="pp_edit_suburb_confirm" value="'.esc_attr(get_post_meta( $post->ID, 'pp_shipping_suburb_name', true )).' - '.esc_attr(get_post_meta( $post->ID, 'pp_shipping_suburb', true )).' " style="width: 100% !important;" />';
			
			echo '<a id="ppshipping_update_order_suburb" href="#" class="button" target="_blank"  data-order="'.esc_html($post->ID).'" style="float:left;">Update Suburb</a></div>';
			
			echo '<div id="ppshipping_edit_loader" style="display: none;"><div class="loader-icon"></div></div>
				  <div style="clear: both;"></div>';
		}
    }
	
	// Adding Meta container admin shop_order pages
    add_action( 'add_meta_boxes', 'ppshipping_mv_add_pp_order_meta_boxes' );
    if ( ! function_exists( 'ppshipping_mv_add_pp_order_meta_boxes' ) )
    {
        function ppshipping_mv_add_pp_order_meta_boxes()
        {
            add_meta_box( 'mv_pp_tracking_information_fields', __('Tracking Information','woocommerce'), 'ppshipping_mv_add_other_fields_for_packaging', 'shop_order', 'side', 'high' );
        }
    }

    /**
     * Adding PP information (Quote #, Ref # and Waybill #) in a block on the admin order page
     */
    if ( ! function_exists( 'ppshipping_mv_add_other_fields_for_packaging' ) )
    {
        function ppshipping_mv_add_other_fields_for_packaging()
        {
            global $post,$wpdb;
			
			$result = $wpdb->get_row("select * from ".$wpdb->prefix."pp_order_waybills WHERE `order` = ".$post->ID);
			
			if (empty($result)) {
				$ref = '';
				$waybill = '';
			}
			else {
				$ref = esc_attr($result->reference);
				$waybill = esc_attr($result->waybill);
			}
			
			$quote_no = get_post_meta( $post->ID, 'order_pp_quote', true );
			
			$settings = get_option('woocommerce_ppshipping_settings',array());

            echo '<input type="hidden" name="mv_other_meta_field_nonce" value="' . wp_create_nonce() . '">';
			
			echo '<div id="ppshipping_details">';
			
			if (!empty($quote_no)) {
				echo '<p>Parcel Perfect Quote No:</p>
					  <input type="text" name="quoteno" value="'.esc_attr(get_post_meta( $post->ID, 'order_pp_quote', true )).' " readonly style="width: 100% !important;" />';
			}
			
			echo '<p>Parcel Perfect Reference:</p>
            <input type="text" name="refno" value="'.$ref.' " readonly style="width: 100% !important;" />
            <p>Parcel Perfect Waybill No:</p>
            <input type="text" name="quoteno" value="'.$waybill.'" readonly style="width: 100% !important;" />';

            echo '<p>';
			
			if (empty($result)) {
				echo '<a id="ppshipping_generate_waybill" href="#" class="button" target="_blank" data-quote="'.esc_html($quote_no).'" data-order="'.esc_html($post->ID).'" style="float:left;">Generate Waybill</a>';
			}

            if (!empty($result->waybill)) {
                echo '<a href="'.get_template_directory_uri().'/OrderWaybills/'.esc_html($result->waybill).'_Waybill.pdf" class="button" target="_blank" style="float:left;">Waybill</a>';
				echo '<p><a href="'.get_template_directory_uri().'/OrderLabels/'.esc_html($result->waybill).'_Label.pdf" class="button" target="_blank" style="float:right;">Label</a></p>';
				echo '<p style="text-align: center; clear: both;"><a href="#" class="ppshipping_autoprint" data-waybill="'.esc_html($result->waybill).'">Print Waybill & Label</a><p>';
				echo '<IFRAME id="ppshipping_waybill_iframe" width="1" height="1" src= scrolling="no" frameborder="0"></IFRAME>';
				echo '<IFRAME id="ppshipping_label_iframe" width="1" height="1" src= scrolling="no" frameborder="0"></IFRAME>';
            }

            echo '</p>
				  </div>
				  <div id="ppshipping_waybill_loader" style="display: none;"><div class="loader-icon"></div></div>
                  <div style="clear: both;"></div>';

        }
    }	
	
	// Saving the hidden field value in the order metadata
	add_action( 'woocommerce_checkout_update_order_meta', 'ppshipping_save_custom_fields_to_meta' );
	function ppshipping_save_custom_fields_to_meta( $order_id ) {
		if ( ! empty( $_POST['pp_suburb'] ) ) {
			update_post_meta( $order_id, 'pp_shipping_suburb_name', sanitize_text_field( $_POST['pp_suburb'] ) );
		}
		
		if ( ! empty( $_POST['pp_suburb_code'] ) ) {
			update_post_meta( $order_id, 'pp_shipping_suburb', sanitize_text_field( $_POST['pp_suburb_code'] ) );
		}
	}
	
	/**
     * When proceeding to payment gateway and the order is created we add various notes to the order
     */
	add_action('woocommerce_checkout_order_processed', 'ppshipping_before_checkout_create_order', 10, 3);
	function ppshipping_before_checkout_create_order( $order_id, $data, $order ) {
        $settings = get_option('woocommerce_ppshipping_settings',array());
		if ( $settings['enabled'] !== 'yes') {
			return;
		}
		
		global $wpdb,$woocommerce;
		
		$WC = WC();
		
		$dest_place_name = $_POST['pp_suburb'];
		$dest_place_code = $_POST['pp_suburb_code'];
		
		if ( ! empty( $dest_place_name ) ) {
			update_post_meta( $order_id, 'pp_shipping_suburb_name', sanitize_text_field( $dest_place_name ) );
			
			// Add the note
			$order->add_order_note( 'Order Suburb name saved as: '.$dest_place_name );
			$order->save();
		}
		
		if ( ! empty( $dest_place_code ) ) {
			update_post_meta( $order_id, 'pp_shipping_suburb', sanitize_text_field( $dest_place_code ) );
			
			// Add the note
			$order->add_order_note( 'Order Suburb code saved as: '.$dest_place_code );
			$order->save();
		}
		
		if( $order->has_shipping_method('ppshipping') ) { 
		
			$Service = explode('-',$data['shipping_method'][0]);
			$order->update_meta_data( 'pp_shipping_service', $Service[1] );
			$order->save();
		
			$settings = get_option('woocommerce_ppshipping_settings',array());
			$orig_place = substr($settings['shop_place_code'], -4);
			
			$shipping_reference = sanitize_text_field($settings['account_ref']).'-R-'.date('Ymd').'-'.date('His');
			
			$Quote = array(
				'origpers'			=>	get_bloginfo('name'),
				'origperadd1'		=>	get_option( 'woocommerce_store_address' ),
				'origperadd2'		=>	get_option( 'woocommerce_store_address_2' ),
				'origtown'			=>	get_option( 'woocommerce_store_address_2' ),
				'origplace'			=>	$orig_place,
				'origperpcode'		=>	get_option( 'woocommerce_store_postcode' ),
				'origpercontact'	=>	get_bloginfo('name'),
				'origperemail'		=>	get_option( 'admin_email' ),
				'destplace'			=>	(string)$dest_place_code,
				'reference'			=>	$shipping_reference,
				'package'			=>	array(
											'destination'	=>	$data
										)
			);

			$items = $woocommerce->cart->get_cart();

			$pp_cart = ppshipping_get_cart($items);
			
			$headers = array(
				"Content-Type" =>  "application/json",
				"request-host" =>  site_url(),
				"access-license" => sanitize_text_field($settings['account_license']),
				"authentication-token" => sanitize_text_field($settings['account_auth'])
			);
			
			$request_place_body = array(
				'PP_Url'			=> sanitize_text_field($settings['pp_qc_account_url']),
				'PP_User'			=> sanitize_text_field($settings['account_id']),
				'PP_Password'		=> sanitize_text_field($settings['account_pass']),
				'RequestBody'		=> array(
										'Quote' =>	$Quote,
										'Cart'	=>	$pp_cart
									)
			);
			
			$result = ppshipping_make_http_request('parcelperfect/quote',$headers,$request_place_body);

			/**
			 * Set quote number based on SOAP result
			 */
			$order->update_meta_data( 'order_pp_quote', $result->results[0]->quoteno );
			$order->save();
			
			$order->update_meta_data( 'order_pp_ref', $shipping_reference );
			$order->save();

			// Add the note
			$order->add_order_note( 'Perfect Parcel Quote #'.$result->results[0]->quoteno. ' accepted and added to order.' );
			$order->save();

			// Add the note
			$order->add_order_note( 'Perfect Parcel Reference #'.$shipping_reference. ' added to order.' );
			$order->save();
		}
    }
	
	/**
     * Function when order status changes to 'Complete' to update service and create waybill from quote
     */
    add_action('woocommerce_payment_complete','ppshipping_process_completed_order');
    function ppshipping_process_completed_order($order_id, $force_process = false) {
		$settings = get_option('woocommerce_ppshipping_settings',array());
		if ( $settings['enabled'] !== 'yes') {
			return;
		}
		
        $quote_no = get_post_meta( $order_id, 'order_pp_quote', true );
        $ref_no = get_post_meta( $order_id, 'order_pp_ref', true );
        $suburb_name = get_post_meta( $order_id, 'pp_shipping_suburb_name', true );
        $suburb = get_post_meta( $order_id, 'pp_shipping_suburb', true );
        $service = get_post_meta( $order_id, 'pp_shipping_service', true );
		$waybillno = sanitize_text_field($settings['account_ref']).$order_id;

        $order = wc_get_order( $order_id );

        $headers = array(
			"Content-Type" =>  "application/json",
			"request-host" =>  site_url(),
			"access-license" => sanitize_text_field($settings['account_license']),
			"authentication-token" => sanitize_text_field($settings['account_auth'])
		);
		
		if( $force_process || $order->has_shipping_method('flat_rate') || $order->has_shipping_method('advanced_free_shipping') ) { 			
			$order_obj = wc_get_order( $order_id );
			//
			// set the address fields
			$user_id = $order_obj->get_user_id();
			$address_fields = array(
				'country',
				'address_1',
				'address_2',
				'address_3',
				'address_4',
				'city',
				'state',
				'postcode',
				'phone'
			);
			$address = array();
			if(is_array($address_fields)){
				foreach($address_fields as $field){
					$address['billing_'.$field] = get_user_meta( $user_id, 'billing_'.$field, true );
					$address['shipping_'.$field] = get_user_meta( $user_id, 'shipping_'.$field, true );
				}
			}
			
			$waybill_number = sanitize_text_field($settings['account_ref']).'-W-'.$order_id;
			$ref_number = sanitize_text_field($settings['account_ref']).'-R-'.$order_id;
			$orig_place = substr($settings['shop_place_code'], -4);
			
			if ($settings['collection'] == 'no') {
				$isCollection = 0;
			}
			else {
				$isCollection = 1;
			}
			
			$Quote = array(
				'waybill'			=>	$waybill_number,
				'service'			=>	$settings['options'][0],
				'origpers'			=>	get_bloginfo('name'),
				'origperadd1'		=>	get_option( 'woocommerce_store_address' ),
				'origperadd2'		=>	get_option( 'woocommerce_store_address_2' ),
				'origtown'			=>	get_option( 'woocommerce_store_address_2' ),
				'origplace'			=>	$orig_place,
				'origperpcode'		=>	get_option( 'woocommerce_store_postcode' ),
				'origpercontact'	=>	get_bloginfo('name'),
				'origperemail'		=>	get_option( 'admin_email' ),
				'destpers'			=>	$order_obj->get_shipping_first_name(). ' ' .$order_obj->get_shipping_last_name(),
				'destperadd1'		=>	$order_obj->get_shipping_address_1(),
				'destperadd2'		=>	$order_obj->get_shipping_address_2(),
				'destplace'			=>	(string)$suburb,
				'destperpcode'		=>	$order_obj->get_shipping_postcode(),
				'destperphone'		=>	$order_obj->get_billing_phone(),
				'destpercell'		=>	$order_obj->get_billing_phone(),
				'destperemail'		=>	$order_obj->get_billing_email(),
				'isCollection'		=>	$isCollection,
				'reference'			=>	$ref_number
			);

			$items = $order_obj->get_items();

			$pp_cart = ppshipping_get_cart($items);
			
			$body = array(
				'PP_Url'	=> sanitize_text_field($settings['pp_w_account_url']),
				'PP_User'	=> sanitize_text_field($settings['account_w_id']),
				'PP_Password'	=> sanitize_text_field($settings['account_w_pass']),
				'RequestBody'	=> array(
									'Quote' 	=> $Quote,
									'Cart'		=> $pp_cart
									)
			);
			
			if (!empty($suburb)) {
				$result = ppshipping_make_http_request('parcelperfect/submit_waybill',$headers,$body);
			}
			
			if($result->errorcode == 0){
				$PDF_Waybill_Decoded = base64_decode( $result->results[0]->waybillBase64 );
				$PDF_Waybill_Decoded = base64_decode( $PDF_Waybill_Decoded );
				$PDF_Label_Decoded = base64_decode( $result->results[0]->labelsBase64 );
				$PDF_Label_Decoded = base64_decode( $PDF_Label_Decoded );

				$plugin_path = get_template_directory().'/';
				$plugin_url = get_template_directory_uri().'/';

				if (!file_exists($plugin_path.'OrderWaybills')) {
					mkdir($plugin_path.'OrderWaybills', 0777, true);
				}

				if (!file_exists($plugin_path.'OrderLabels')) {
					mkdir($plugin_path.'OrderLabels', 0777, true);
				}

				$waybill_filename = $waybill_number.'_Waybill';
				$label_filename = $waybill_number.'_Label';

				$waybill_file = $plugin_path . 'OrderWaybills/'.$waybill_filename.'.pdf';
				$label_file = $plugin_path . 'OrderLabels/'.$label_filename.'.pdf';

				file_put_contents($waybill_file, $PDF_Waybill_Decoded) or print_r(error_get_last());
				file_put_contents($label_file, $PDF_Label_Decoded) or print_r(error_get_last());
				
				$order_obj->add_order_note($waybill_number.'_Waybill.pdf successfully created.');
				$order_obj->save();
				
				$order_obj->add_order_note($waybill_number.'_Label.pdf successfully created.');
				$order_obj->save();
				
				global $wpdb;
				
				$Args = array(
					'order'			=>	$order_id,
					'waybill'		=>	$waybill_number,
					'reference'		=>	$ref_number,
					'service'		=>	$settings['options'][0],
					'created_date'	=>	date('Y-m-d'),
				);
				
				$wpdb->insert($wpdb->prefix.'pp_order_waybills',$Args);
				
				if ($settings['email_enabled'] == 'yes' && $settings['account_email'] != '') {
					/**
					 * Send E-mail to PTE with waybill to process
					 */
					global $wp_version;
					if( $wp_version < '5.5') {
						require_once(ABSPATH . WPINC . '/class-phpmailer.php');
						require_once(ABSPATH . WPINC . '/class-smtp.php');
						$mail = new PHPMailer( true );
					}
					else {
						require_once(ABSPATH . WPINC . '/PHPMailer/PHPMailer.php');
						require_once(ABSPATH . WPINC . '/PHPMailer/SMTP.php');
						require_once(ABSPATH . WPINC . '/PHPMailer/Exception.php');
						$mail = new PHPMailer\PHPMailer\PHPMailer( true );
					}

					$mail = new PHPMailer\PHPMailer\PHPMailer(true);                             // Passing `true` enables exceptions
					try {
						//Recipients
						$mail->setFrom( get_bloginfo( 'admin_email' ), get_bloginfo( 'name' ));
						$mail->addAddress( $settings['account_email'], get_bloginfo( 'name' ));     // Add a recipient
						$mail->addReplyTo( get_bloginfo( 'admin_email' ), get_bloginfo( 'name' ));

						//Content
						$mail->isHTML(true);                                  // Set email format to HTML
						$mail->Subject = 'Waybill #'.esc_html($waybill_number).' Ready for Processing';
						$mail->Body    = '
									  <p>Good Day,</p>
									  <p>Order #'.esc_html($order_id).' was successfully completed.</p>
									  <p><strong>Please see the order details below to process:</strong></p>
									  <p>Client Name: '.esc_html(get_post_meta( $order_id, '_billing_first_name', true )).' '.esc_html(get_post_meta( $order_id, '_billing_last_name', true )).'<br />
										 Client E-mail: '.esc_html(get_post_meta( $order_id, '_billing_email', true )).'<br />
										 Waybill #'.esc_html($waybill_number).'<br />
										 Reference #'.esc_html($ref_number).'</p>
									  <p>Kind Regards,<br />
										 The '.get_bloginfo( 'name' ).' Team</p>';
						$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

						$mail->send();
						$order_obj->add_order_note('Processing E-mail successfully sent to '.$settings['account_email']);

						// Save the data
						$order_obj->save();
					} catch (Exception $e) {
						$order_obj->add_order_note('Processing E-mail could not be sent. Mailer Error: ', $mail->ErrorInfo);

						// Save the data
						$order_obj->save();
					}
				}
			}
		}
		
		if( $order->has_shipping_method('ppshipping') ) {
			// Iterating through order shipping items
			foreach( $order->get_items( 'shipping' ) as $item_id => $shipping_item_obj ){
				$shipping_method_title     = $shipping_item_obj->get_method_title();
			}  
			
			$request_place_body = array(
				'PP_Url'	=> sanitize_text_field($settings['pp_qc_account_url']),
				'PP_User'	=> sanitize_text_field($settings['account_id']),
				'PP_Password'	=> sanitize_text_field($settings['account_pass']),
				'RequestBody'	=> array(
						'quoteno' =>$quote_no,
						'service' =>$service
					)
			);
			
			if (!empty($suburb)) {
				$result = ppshipping_make_http_request('parcelperfect/update_service',$headers,$request_place_body);

				$order->add_order_note( $result );
				$order->save();

				// Check for and output any errors with submission

				if($result->errorcode == 0){
					// Add the note
					$order->add_order_note('Perfect Parcel Service ('.$service.') registered.');

					// Save the data
					$order->save();
					
					$request_place_body = array(
						'PP_Url'	=> sanitize_text_field($settings['pp_qc_account_url']),
						'PP_User'	=> sanitize_text_field($settings['account_id']),
						'PP_Password'	=> sanitize_text_field($settings['account_pass']),
						'RequestBody'	=> array(
								'quoteno' =>$quote_no,
								'waybillno' =>$waybillno
							)
					);
					
					$result = ppshipping_make_http_request('parcelperfect/quote_collection',$headers,$request_place_body);

					$order->add_order_note( $result );
					$order->save();
					
					// Check for and output any errors with submission

					if($result->errorcode == 0){
						// Add the note
						$order->add_order_note('Waybill #'.$result->results[0]->waybillno.' registered.');
						$order->update_meta_data( 'order_pp_wayb', $result->results[0]->waybillno );

						// Save the data
						$order->save();

						$PDF_Waybill_Decoded = base64_decode( $result->results[0]->waybillBase64 );
						$PDF_Label_Decoded = base64_decode( $result->results[0]->labelsBase64 );

						$plugin_path = get_template_directory().'/';
						$plugin_url = get_template_directory_uri().'/';

						if (!file_exists($plugin_path.'OrderWaybills')) {
							mkdir($plugin_path.'OrderWaybills', 0777, true);
						}

						if (!file_exists($plugin_path.'OrderLabels')) {
							mkdir($plugin_path.'OrderLabels', 0777, true);
						}

						$waybill_filename = $waybillno.'_Waybill';
						$label_filename = $waybillno.'_Label';

						$waybill_file = $plugin_path . 'OrderWaybills/'.$waybill_filename.'.pdf';
						$label_file = $plugin_path . 'OrderLabels/'.$label_filename.'.pdf';

						file_put_contents($waybill_file, $PDF_Waybill_Decoded) or print_r(error_get_last());
						file_put_contents($label_file, $PDF_Label_Decoded) or print_r(error_get_last());

						$order->update_meta_data( 'order_pp_waybill_url', $plugin_url . 'OrderWaybills/'.$waybill_filename.'.pdf' );
						$order->save();
						$order->update_meta_data( 'order_pp_label_url', $plugin_url . 'OrderLabels/'.$label_filename.'.pdf' );
						$order->save();
						
						$Args = array(
							'order'			=>	$order_id,
							'waybill'		=>	$waybillno,
							'reference'		=>	$ref_no,
							'service'		=>	$service,
							'created_date'	=>	date('Y-m-d'),
						);
						
						global $wpdb;
						
						$wpdb->insert($wpdb->prefix.'pp_order_waybills',$Args);
						
						if ($settings['email_enabled'] == 'yes' && $settings['account_email'] != '') {
							/**
							 * Send E-mail to PTE with waybill to process
							 */
							global $wp_version;
							if( $wp_version < '5.5') {
								require_once(ABSPATH . WPINC . '/class-phpmailer.php');
								require_once(ABSPATH . WPINC . '/class-smtp.php');
								$mail = new PHPMailer( true );
							}
							else {
								require_once(ABSPATH . WPINC . '/PHPMailer/PHPMailer.php');
								require_once(ABSPATH . WPINC . '/PHPMailer/SMTP.php');
								require_once(ABSPATH . WPINC . '/PHPMailer/Exception.php');
								$mail = new PHPMailer\PHPMailer\PHPMailer( true );
							}

							try {
								//Recipients
								$mail->setFrom( get_bloginfo( 'admin_email' ), get_bloginfo( 'name' ));
								$mail->addAddress( $settings['account_email'], get_bloginfo( 'name' ));     // Add a recipient
								$mail->addReplyTo( get_bloginfo( 'admin_email' ), get_bloginfo( 'name' ));

								//Content
								$mail->isHTML(true);                                  // Set email format to HTML
								$mail->Subject = 'Waybill #'.$waybillno.' Ready for Processing';
								$mail->Body    = '
											  <p>Good Day,</p>
											  <p>Order #'.$order_id.' was successfully completed.</p>
											  <p><strong>Please see the order details below to process:</strong></p>
											  <p>Client Name: '.get_post_meta( $order_id, '_billing_first_name', true ).' '.get_post_meta( $order_id, '_billing_last_name', true ).'<br />
												 Client E-mail: '.get_post_meta( $order_id, '_billing_email', true ).'<br />
												 Waybill #'.$waybillno.'<br />
												 Reference #'.$ref_no.'</p>
											  <p>Kind Regards,<br />
												 The '.get_bloginfo( 'name' ).' Team</p>';
								$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

								$mail->send();
								$order->add_order_note('Processing E-mail successfully sent to '.$settings['account_email']);

								// Save the data
								$order->save();
							} catch (Exception $e) {
								$order->add_order_note('Processing E-mail could not be sent. Mailer Error: ', $mail->ErrorInfo);

								// Save the data
								$order->save();
							}
						}
					}
					else {
						// Add the note
						$order->add_order_note($result->errormessage);

						// Save the data
						$order->save();
					}					
				}
				else{
					// Add the note
					$order->add_order_note($result->errormessage);

					// Save the data
					$order->save();
				}
			}
		}
    }
	
	add_action( 'woocommerce_admin_order_data_after_shipping_address', 'ppshipping_edit_woocommerce_checkout_page', 10, 1 );

	function ppshipping_edit_woocommerce_checkout_page($order){
		global $post_id;
		$order = new WC_Order( $post_id );
		echo '<p><strong>'.__('Suburb').':</strong> ' . get_post_meta($order->get_id(), 'pp_shipping_suburb', true ) . '</p>';
	}

	/**
	 * Connect to the after shipping render block
	 */
	function ppshipping_woocommerce_cart_totals_after_shipping() {
		global $woocommerce;
		$cart = $woocommerce->cart->get_cart();
		print_r( $cart );
	}
	//add_action( 'woocommerce_cart_totals_after_shipping', 'ppshipping_woocommerce_cart_totals_after_shipping' );

	if ( ! function_exists( 'ppshipping_array_insert' ) ) {
		/**
		 * @param array $array
		 * @param int|string $position
		 * @param mixed $insert
		 */
		function ppshipping_array_insert( &$array, $position, $insert ) {
			if ( is_int( $position ) ) {
				array_splice( $array, $position, 0, $insert );
			} else {
				$pos   = array_search( $position, array_keys( $array ) );
				$array = array_merge(
					array_slice( $array, 0, $pos ),
					$insert,
					array_slice( $array, $pos )
				);
			}
		}
	}
	
	add_action( 'woocommerce_before_order_notes', 'ppshipping_add_custom_checkout_field' );
	function ppshipping_add_custom_checkout_field( $checkout ) { 
		global $wpdb;
		
		$settings = get_option('woocommerce_ppshipping_settings',array());
		
		if ($settings['enabled'] == 'yes') {
			woocommerce_form_field( 'pp_suburb', array(        
				'type'          => 'text',
				'label'     => __('Suburb', 'woocommerce'),
				'priority' => 60,
				'required'  => true,
				'placeholder' => _x('Delivery Suburb', 'placeholder', 'woocommerce'),
				'class' => array ('address-field', 'update_totals_on_change' ),
				'clear'     => true      
			), $checkout->get_value( 'pp_suburb' ) ); 
		}
		
		// Output the hidden field
		echo '<div id="pp_shipping_suburb_code_container">
				<input type="hidden" class="input-hidden" name="pp_suburb_code" id="pp_suburb_code" value="">
			</div>';
	}
	
	add_filter( 'woocommerce_checkout_get_value' , 'clear_checkout_fields' , 10, 2 );
	function clear_checkout_fields( $value, $input ){
		if( $input == 'pp_suburb' ) {
			$value = '';
		}
		
		return $value;
	}
	
	// custom checkout field validation
	add_action( 'woocommerce_checkout_process', 'ppshipping_checkout_field_validation' );
	function ppshipping_checkout_field_validation() {
		if (!empty($_POST['ship_to_different_address']) && !empty($_POST['shipping_country']) && $_POST['shipping_country'] == 'ZA') {
			if ( isset( $_POST['pp_suburb'] ) && empty( $_POST['pp_suburb'] ) ) {
				wc_add_notice( __( '<strong>Suburb</strong> is a required field.', 'woocommerce' ), 'error' );
			}
			else {
				if ( isset( $_POST['pp_suburb_code'] ) && empty( $_POST['pp_suburb_code'] ) ) {
					wc_add_notice( __( '<strong>Suburb error:</strong> please reselect your suburb.', 'woocommerce' ), 'error' );
				}
			}
		}
		else {
			if (!empty($_POST['billing_country']) && $_POST['billing_country'] == 'ZA') {
				if ( isset( $_POST['pp_suburb'] ) && empty( $_POST['pp_suburb'] ) ) {
					wc_add_notice( __( '<strong>Suburb</strong> is a required field.', 'woocommerce' ), 'error' );
				}
				else {
					if ( isset( $_POST['pp_suburb_code'] ) && empty( $_POST['pp_suburb_code'] ) ) {
						wc_add_notice( __( '<strong>Suburb error:</strong> please reselect your suburb.', 'woocommerce' ), 'error' );
					}
				}
			}
		}
	}
	
	add_filter( 'woocommerce_cart_shipping_packages', 'ppshipping_woocommerce_cart_shipping_packages' );
    function ppshipping_woocommerce_cart_shipping_packages($packages) {
		
		if (isset($_POST['pp_suburb'])) {
			$shipping_suburb = $_POST['pp_suburb'];
		}
		else {
			if (isset($_POST['post_data']) && !empty($_POST['post_data'])) {
				$post_data = ppshipping_get_all_post_data($_POST['post_data']);
				
				$shipping_suburb = $post_data['pp_suburb'];
			}
			else {
				$shipping_suburb = '';
			}
		}
		
		if (isset($_POST['pp_suburb_code'])) {
			$pp_suburb_code = $_POST['pp_suburb_code'];
		}
		else {
			if (isset($_POST['post_data']) && !empty($_POST['post_data'])) {
				$post_data = ppshipping_get_all_post_data($_POST['post_data']);
				
				$pp_suburb_code = $post_data['pp_suburb_code'];
			}
			else {
				$pp_suburb_code = '';
			}
		}

        // Reset the packages
        $packages = array();

        // Bulky items
        $bulky_items = array();
        $regular_items = array();

        // Sort bulky from regular
        foreach ( WC()->cart->get_cart() as $item ) {
            if ( $item['data']->needs_shipping() ) {
                if ( $item['data']->get_shipping_class() == 'bulky' ) {
                    $bulky_items[] = $item;
                } else {
                    $regular_items[] = $item;
                }
            }
        }

        // Put inside packages
        if ( $bulky_items ) {
            $packages[] = array(
                'contents' => $bulky_items,
                'contents_cost' => array_sum( wp_list_pluck( $bulky_items, 'line_total' ) ),
                'applied_coupons' => WC()->cart->applied_coupons,
                'destination' => array(
                    'first_name' => WC()->customer->get_shipping_first_name(),
                    'last_name' => WC()->customer->get_shipping_last_name(),
					'phone' => WC()->customer->get_billing_phone(),
                    'email' => WC()->customer->get_email(),
                    'country' => WC()->customer->get_shipping_country(),
                    'state' => WC()->customer->get_shipping_state(),
                    'postcode' => WC()->customer->get_shipping_postcode(),
                    'address_1' => WC()->customer->get_shipping_address(),
                    'address_2' => WC()->customer->get_shipping_address_2(),
					'suburb' => urldecode($shipping_suburb),
					'suburb_code' => urldecode($pp_suburb_code),
                    'city' => WC()->customer->get_shipping_city(),
                )
            );
        }
        if ( $regular_items ) {
            $packages[] = array(
                'contents' => $regular_items,
                'contents_cost' => array_sum( wp_list_pluck( $regular_items, 'line_total' ) ),
                'applied_coupons' => WC()->cart->applied_coupons,
                'destination' => array(
					'first_name' => WC()->customer->get_shipping_first_name(),
                    'last_name' => WC()->customer->get_shipping_last_name(),
                    'phone' => WC()->customer->get_billing_phone(),
                    'email' => WC()->customer->get_email(),
                    'country' => WC()->customer->get_shipping_country(),
                    'state' => WC()->customer->get_shipping_state(),
                    'postcode' => WC()->customer->get_shipping_postcode(),
                    'address_1' => WC()->customer->get_shipping_address(),
                    'address_2' => WC()->customer->get_shipping_address_2(),
					'suburb' =>urldecode($shipping_suburb),
					'suburb_code' => urldecode($pp_suburb_code),
                    'city' => WC()->customer->get_shipping_city(),
                )
            );
        }
		
        return $packages;
    }
	
	function ppshipping_get_all_post_data($post_data) {
		$result_array = array();
		
		$items = explode('&',$post_data);
		
		foreach ($items as $i) {
			$key_value = explode('=',$i);
			
			$key = $key_value[0];
			$value = $key_value[1];
			
			$result_array[$key] = str_replace('+',' ',$value);
		}
		
		return $result_array;
	}
	
	function ppshipping_make_http_request($function, $headers, $body) {
		$url = "http://www.alwynmalan.co.za/wp-json/api/".$function;
		
		$args = array(
			'method' 	  => 'POST',
			'body'        => json_encode($body),
			'timeout'     => '100',
			'redirection' => '5',
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => $headers,
			'data_format' => 'body'
		);
		
		$response = wp_remote_post( $url, $args );
		$body = wp_remote_retrieve_body($response);

		return json_decode($body);
	}
	
	function ppshipping_install_tables() {
        global $wpdb;
		
		//Creating table for linking orders and service to create waybill and label

        $table_name      = $wpdb->prefix . 'pp_order_waybills';
        $Charset_Collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `order` varchar(255) NOT NULL,
                            `waybill` varchar(150) NOT NULL,
                            `reference` varchar(150) NOT NULL,
                            `service` varchar(50) NOT NULL,
                            `created_date` varchar(255) NOT NULL,
                            PRIMARY KEY  (`id`),
                            UNIQUE KEY `id` (`id`)
                            )$Charset_Collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
		
		//Creating table for shipping packaging options

        $table_name      = $wpdb->prefix . 'pp_shipping_packages';
        $Charset_Collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `created_date_time` varchar(255) NOT NULL,
                            `updated_date_time` varchar(255) NOT NULL,
                            `shipping_class` varchar(255) NOT NULL,
                            `shipping_class_name` varchar(455) NOT NULL,
                            `label` varchar(1500) NOT NULL,
                            `no_items` varchar(255) NOT NULL,
                            `height` varchar(500) NOT NULL,
                            `width` varchar(500) NOT NULL,
                            `length` varchar(500) NOT NULL,
                            `weight` varchar(500) NOT NULL,
                            PRIMARY KEY  (`id`),
                            UNIQUE KEY `id` (`id`)
                            )$Charset_Collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
		
		$column = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",
			DB_NAME, $table_name, 'ignore'
		) );
		
		if ( empty( $column ) ) {
			$wpdb->query( "ALTER TABLE `$table_name` ADD `ignore` BOOLEAN NOT NULL DEFAULT FALSE" );
		}
    }

    register_activation_hook(__FILE__,'ppshipping_install_tables');


    /**
     * Proper way to enqueue scripts and styles.
     */
    function ppshipping_scripts() {
        wp_register_script( 'ppshipping', plugin_dir_url( __FILE__ ).'inc/pp_shipping.js', array( 'jquery' ) );
		
		$script_array = array(
			'ajax'					=>	admin_url( 'admin-ajax.php' ),
			'admin_packages'		=>	get_admin_url().'admin.php?page=ppshipping_admin',
			'theme_path'			=>  get_template_directory_uri()
		);
		
		wp_localize_script( 'ppshipping', 'pp_path', $script_array );
		wp_enqueue_script( 'ppshipping' );	
		
		wp_register_style( 'ppshipping_style', plugin_dir_url( __FILE__ ).'inc/pp_shipping.css' );		
		wp_enqueue_style( 'ppshipping_style' );
    }
    
	add_action( 'admin_enqueue_scripts', 'ppshipping_scripts' );
    add_action( 'wp_enqueue_scripts', 'ppshipping_scripts' );
	
	add_action("wp_ajax_ppshipping_generate_waybill", "ppshipping_generate_waybill");
	add_action("wp_ajax_nopriv_ppshipping_generate_waybill", "ppshipping_generate_waybill");
	
	function ppshipping_generate_waybill() {
		$order_id = $_REQUEST['order'];
		ppshipping_process_completed_order($order_id,true);
		wp_die();
	}
	
	add_action("wp_ajax_ppshipping_get_places", "ppshipping_get_places");
	add_action("wp_ajax_nopriv_ppshipping_get_places", "ppshipping_get_places");
	
	function ppshipping_get_places() {
		$settings = get_option('woocommerce_ppshipping_settings',array());

		$headers = array(
			"Content-Type" => "application/json",
			"request-host" => site_url(),
			"access-license" => sanitize_text_field($settings['account_license']),
			"authentication-token" => sanitize_text_field($settings['account_auth'])
		);
		
		if ($_REQUEST['searchType'] == 'name') {
			$RequestBody = array(
					'name' => sanitize_text_field($_REQUEST['searchTerm'])
				);
			
			$endpoint = 'name';
		}
		else {
			$RequestBody = array(
					'postcode' => sanitize_text_field($_REQUEST['searchTerm'])
				);
				
			$endpoint = 'code';
		}
		
		$request_place_body = array(
			'PP_Url'	=> sanitize_text_field($settings['pp_qc_account_url']),
			'PP_User'	=> sanitize_text_field($settings['account_id']),
			'PP_Password'	=> sanitize_text_field($settings['account_pass']),
			'RequestBody'	=> $RequestBody
		);
		
		$result = ppshipping_make_http_request('parcelperfect/place_by_'.$endpoint,$headers,$request_place_body);
		print_r($result);
		wp_die();
	}
	
	add_action("wp_ajax_ppshipping_save_shipping_package", "ppshipping_save_shipping_package");
	add_action("wp_ajax_nopriv_ppshipping_save_shipping_package", "ppshipping_save_shipping_package");
	
	function ppshipping_save_shipping_package() {
		global $wpdb;
		
		$args = array(
			'created_date_time'		=>	date('Y-m-d H:i:s'),
			'updated_date_time'		=>	date('Y-m-d H:i:s'),
			'shipping_class'		=>	$_REQUEST['shipping_class'],
			'shipping_class_name'	=>	$_REQUEST['shipping_class_name'],
			'label'					=>	implode(';',$_REQUEST['label']),
			'no_items'				=>	$_REQUEST['no_items'],
			'height'				=>	implode(';',$_REQUEST['height']),
			'width'					=>	implode(';',$_REQUEST['width']),
			'length'				=>	implode(';',$_REQUEST['length']),
			'weight'				=>	implode(';',$_REQUEST['weight']),
			'ignore'				=>	$_REQUEST['shipping_class_waybill']
		);
		
		$wpdb->insert($wpdb->prefix.'pp_shipping_packages',$args);
		
		wp_die();
	}
	
	add_action("wp_ajax_ppshipping_update_shipping_package", "ppshipping_update_shipping_package");
	add_action("wp_ajax_nopriv_ppshipping_update_shipping_package", "ppshipping_update_shipping_package");
	
	function ppshipping_update_shipping_package() {
		global $wpdb;
		
		$args = array(
			'updated_date_time'		=>	date('Y-m-d H:i:s'),
			'shipping_class'		=>	$_REQUEST['shipping_class'],
			'shipping_class_name'	=>	$_REQUEST['shipping_class_name'],
			'label'					=>	implode(';',$_REQUEST['label']),
			'no_items'				=>	$_REQUEST['no_items'],
			'height'				=>	implode(';',$_REQUEST['height']),
			'width'					=>	implode(';',$_REQUEST['width']),
			'length'				=>	implode(';',$_REQUEST['length']),
			'weight'				=>	implode(';',$_REQUEST['weight']),
			'ignore'				=>	$_REQUEST['shipping_class_waybill']
		);
		
		$wpdb->update($wpdb->prefix.'pp_shipping_packages',$args,array('id'=>$_REQUEST['package_id']));
		
		wp_die();
	}
	
	add_action("wp_ajax_ppshipping_delete_shipping_package", "ppshipping_delete_shipping_package");
	add_action("wp_ajax_nopriv_ppshipping_delete_shipping_package", "ppshipping_delete_shipping_package");
	
	function ppshipping_delete_shipping_package() {
		global $wpdb;
		
		$wpdb->delete($wpdb->prefix.'pp_shipping_packages',array('id'=>$_REQUEST['package_id']));
		
		wp_die();
	}
	
	add_action("wp_ajax_ppshipping_get_dim_breakdown", "ppshipping_get_dim_breakdown");
	add_action("wp_ajax_nopriv_ppshipping_get_dim_breakdown", "ppshipping_get_dim_breakdown");
	
	function ppshipping_get_dim_breakdown() {
		global $wpdb;
		
		$amount = $_REQUEST['amount'];
		$i = 1;
		
		$Content = '<table id="ppshipping_package_breakdown" cellpadding="0" cellspacing="0">';
		
		for ($i; $i <= $amount; $i++) {
			$Content .= '<tr>
							<td><strong>Package with '.$i.' items:</strong></td>
							<td>Label:</td>
							<td><input type="text" name="ppshipping_breakdown_label[]" value="" /></td>
							<td>Width (cm):</td>
							<td><input type="number" name="ppshipping_breakdown_width[]" value="" min="1" max="1000" /></td>
							<td>Length (cm):</td>
							<td><input type="number" name="ppshipping_breakdown_length[]" value="" min="1" max="1000" /></td>
							<td>Height (cm):</td>
							<td><input type="number" name="ppshipping_breakdown_height[]" value="" min="1" max="1000" /></td>
							<td>Weight (kg):</td>
							<td><input type="number" name="ppshipping_breakdown_weight[]" value="" min="1" max="50" /></td>
						</tr>';
		}
		
		$Content .= '</table>';
		
		echo $Content;
		
		wp_die();
	}
	
	add_action("wp_ajax_ppshipping_update_order_suburb", "ppshipping_update_order_suburb");
	add_action("wp_ajax_nopriv_ppshipping_update_order_suburb", "ppshipping_update_order_suburb");
	
	function ppshipping_update_order_suburb() {
		$order = $_REQUEST['order'];
		
		$suburb = explode(' - ',$_REQUEST['suburb']);
		
		update_post_meta($order,'pp_shipping_suburb_name',trim($suburb[0]));
		update_post_meta($order,'pp_shipping_suburb',trim($suburb[1]));
		
		echo '1';
		
		wp_die();
	}
	
	function ppshipping_shipping_packages_menu() {
		global $wpdb, $wp_version, $_registered_pages;
		
		add_menu_page( 'Parcel Perfect', 'Parcel Perfect', 'manage_options', 'ppshipping_admin', 'ppshipping_shipping_package_manage', 'dashicons-admin-tools', 99 );
		add_submenu_page( null, 'Add Shipping Package', 'Add Shipping Package', 'manage_options', 'ppshipping_packages_add', 'ppshipping_shipping_package_add' );  
		add_submenu_page( null, 'Edit Shipping Package', 'Edit Shipping Package', 'manage_options', 'ppshipping_packages_edit', 'ppshipping_shipping_package_edit' );
		add_submenu_page( null, 'Delete Shipping Package', 'Delete Shipping Package', 'manage_options', 'ppshipping_packages_delete', 'ppshipping_shipping_package_delete' );
		
		require plugin_dir_path( __FILE__ ) . '/admin/page_layout.php';
	}
	
	add_action( 'admin_menu', 'ppshipping_shipping_packages_menu' );
	
	function ppshipping_add_popup() {
		if ( (is_page( 'checkout' ) || is_checkout())) :

			echo'<div class="popupbg" style="display: none;">
					<div class="popupcontent">
						<a class="closeviewoptions" href="#" class="remove" aria-label="Return to Checkout">x</a>
						<div style="clear: both;"></div>
						<div class="popupcontentcontainer">
							<div id="ppshipping_loader"><div class="loader-icon"></div></div>
							<div class="popupinfo">
								<form id="ppshipphing_find_suburb_form" name="ppshipping_find_suburb_form" onsubmit="return ppshipping_find_suburb();">
									<h4>Search Suburb</h4>
									<p>Please enter your suburb name OR postal code.</p>
									<p class="ppshipping_code_notice"><span>!</span> Please note the 4 digit code after the suburb name is NOT the postal code but refers to the courier area code.</p>
									<input id="ppshipping_suburb_finder" type="text" name="ppshipping_suburb_finder" placeholder="Suburb name / postal code" />
									<input type="submit" name="ppshipping_finder_submit" value="Search" class="button alt" style="margin-top: 20px;" />
								</form>
								<div style="clear: both;"></div>
							</div>
							<div class="popupresult" style="display: none;">
								<h4>Suburb results</h4>
								<div class="suburb_list">
								
								</div>
								<a href="#" class="refresh_suburb_search">Go back</a>
							</div>
						</div>
					</div>
				</div>';
		endif;
	}
	add_action('wp_head', 'ppshipping_add_popup');
	
	function ppshipping_add_admin_popup() {
		if ((!empty($_GET['page']) && $_GET['page'] == 'wc-settings') || (!empty($_GET['post']) && get_post_type($_GET['post']) == "shop_order")) :
		
			echo'<div class="popupbg" style="display: none;">
					<div class="popupcontent">
						<a class="closeviewoptions" href="#" class="remove" aria-label="Return to Checkout">x</a>
						<div style="clear: both;"></div>
						<div class="popupcontentcontainer">
							<div id="ppshipping_loader"><div class="loader-icon"></div></div>
							<div class="popupinfo">
								<form id="ppshipphing_find_suburb_form" name="ppshipping_find_suburb_form" onsubmit="return ppshipping_find_suburb();">
									<h4 style="font-size: 18px; line-height: 1.1em; margin-bottom: 4px;">Find Suburb</h4>
									<p>Please enter your suburb name OR postal code.</p>
									<p class="ppshipping_code_notice"><span>!</span> Please note the 4 digit code after the suburb name is NOT the postal code but refers to the courier area code.</p>
									<input id="ppshipping_suburb_finder" type="text" name="ppshipping_suburb_finder" placeholder="Suburb name / postal code" style="width: 100%; margin: 5px 0px;" />
									<input type="submit" name="ppshipping_finder_submit" value="Find Suburb" class="button alt" style="margin-top: 20px;" />
								</form>
								<div style="clear: both;"></div>
							</div>
							<div class="popupresult" style="display: none;">
								<h4>Find Suburb</h4>
								<div class="suburb_list">

								</div>
								<a href="#" class="refresh_suburb_search">Go back</a>
							</div>
						</div>
					</div>
				</div>';
		endif;
	}
	add_action('admin_head', 'ppshipping_add_admin_popup');
	
	function ppshipping_email_add_suburb( $order, $sent_to_admin, $plain_text, $email ) { 
		$suburb_name = get_post_meta( $order->ID, 'pp_shipping_suburb_name', true );
		
		echo '<p>The selected delivery suburb is: '.ucwords(strtolower($suburb_name)).'</p>';
	}
	
	add_action( 'woocommerce_email_after_order_table', 'ppshipping_email_add_suburb', 10, 4 );
	
	function ppshipping_get_shipping_classes() {
		global $wpdb;
		$packages = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'pp_shipping_packages');
		$shipping_packages = array();

		if (!empty($packages)) {
			foreach ($packages as $p) {
				$p_array = array(
					'shipping_class'		=>	$p->shipping_class,
					'shipping_class_name'	=>	$p->shipping_class_name,
					'no_items'				=>	$p->no_items,
					'ignore'				=>	$p->ignore,
					'dimensions'			=>	array()
				);
				
				$label = explode(';',$p->label);
				$length = explode(';',$p->length);
				$height = explode(';',$p->height);
				$width = explode(';',$p->width);
				$weight = explode(';',$p->weight);
						
				$loop = (int)$p->no_items;
				$i = 0;
				
				for ($i;$i < $loop; $i++) {
					$package_dimensions = array(
						'label'		=>	$label[$i],
						'length'	=>	$length[$i],
						'height'	=>	$height[$i],
						'width'		=>	$width[$i],
						'weight'	=>	$weight[$i]
					);
					
					$p_array['dimensions'][$i+1] = $package_dimensions;
				}
				
				$shipping_packages[$p->shipping_class] = $p_array;
			}
		}
		
		return $shipping_packages;
	}
	
	function ppshipping_get_cart($items) {
		$shipping_classes = ppshipping_get_shipping_classes();
		$default_no_class = 'default-no-shipping-class';
		$cart_breakdown = array();
		$pp_cart = array();
		$Item_no = 1;

		foreach ($items as $item) {
			$product = wc_get_product($item['product_id']);
			$sc = $product->get_shipping_class();

			if (empty($item['bundled_by'])) {
				if (!empty($sc)) {
					if (!$shipping_classes[$sc]['ignore']) {
						if (!empty($shipping_classes[$sc])) {
							if (!empty($cart_breakdown[$sc])) {
								$cart_breakdown[$sc] = $cart_breakdown[$sc] + $item['quantity'];
							}
							else {
								$cart_breakdown[$sc] = $item['quantity'];
							}
						}
						else {
							if (!empty($cart_breakdown[$default_no_class])) {
								$cart_breakdown[$default_no_class] = $cart_breakdown[$default_no_class] + $item['quantity'];
							}
							else {
								$cart_breakdown[$default_no_class] = $item['quantity'];
							}
						}
					}
				}
				else {
					if (!$shipping_classes[$default_no_class]['ignore']) {
						if (!empty($shipping_classes[$default_no_class])) {
							if (!empty($cart_breakdown[$default_no_class])) {
								$cart_breakdown[$default_no_class] = $cart_breakdown[$default_no_class] + $item['quantity'];
							}
							else {
								$cart_breakdown[$default_no_class] = $item['quantity'];
							}
						}
						else {
							$Item_weight = $product->get_weight();

							if (empty($Item_weight) || $Item_weight == 0) {
								$Item_weight = 1;
							}

							$Item_length = $product->get_length();

							if (empty($Item_length) || $Item_length == 0) {
								$Item_length = 1;
							}

							$Item_width = $product->get_width();

							if (empty($Item_width) || $Item_width == 0) {
								$Item_width = 1;
							}

							$Item_height = $product->get_height();

							if (empty($Item_height) || $Item_height == 0) {
								$Item_height = 1;
							}

							$ActMass = $item['quantity'] * $Item_weight;

							$add_item = array(
								'item'			=>	$Item_no,
								'description'	=>	get_the_title($item['product_id']),
								'pieces'		=>	$item['quantity'],
								'dim1'			=>	$Item_length,
								'dim2'			=>	$Item_width,
								'dim3'			=>	$Item_height,
								'actmass'		=>	$ActMass
							);

							$Item_no++;
							$pp_cart[] = $add_item;
						}
					}
				}
			}
		}

		foreach ($cart_breakdown as $key=>$quantity) {
			$max_items = $shipping_classes[$key]['no_items'];

			if ($quantity > $max_items) {
				$max_packages = $quantity / $max_items;
				$max_packages = floor($max_packages);

				$ActMass = $max_packages * $shipping_classes[$key]['dimensions'][$max_items]['weight'];

				$add_item = array(
					'item'			=>	$Item_no,
					'description'	=>	$shipping_classes[$key]['dimensions'][$max_items]['label'],
					'pieces'		=>	$max_packages,
					'dim1'			=>	$shipping_classes[$key]['dimensions'][$max_items]['length'],
					'dim2'			=>	$shipping_classes[$key]['dimensions'][$max_items]['height'],
					'dim3'			=>	$shipping_classes[$key]['dimensions'][$max_items]['width'],
					'actmass'		=>	$ActMass
				);

				$pp_cart[] = $add_item;
				$Item_no++;

				$left_over = $quantity - ( $max_packages * $max_items );

				if ($left_over > 0) {
					$add_item = array(
						'item'			=>	$Item_no,
						'description'	=>	$shipping_classes[$key]['dimensions'][$left_over]['label'],
						'pieces'		=>	1,
						'dim1'			=>	$shipping_classes[$key]['dimensions'][$left_over]['length'],
						'dim2'			=>	$shipping_classes[$key]['dimensions'][$left_over]['height'],
						'dim3'			=>	$shipping_classes[$key]['dimensions'][$left_over]['width'],
						'actmass'		=>	$shipping_classes[$key]['dimensions'][$left_over]['weight']
					);

					$pp_cart[] = $add_item;
					$Item_no++;
				}
			}
			else {
				$add_item = array(
					'item'			=>	$Item_no,
					'description'	=>	$shipping_classes[$key]['dimensions'][$quantity]['label'],
					'pieces'		=>	1,
					'dim1'			=>	$shipping_classes[$key]['dimensions'][$quantity]['length'],
					'dim2'			=>	$shipping_classes[$key]['dimensions'][$quantity]['height'],
					'dim3'			=>	$shipping_classes[$key]['dimensions'][$quantity]['width'],
					'actmass'		=>	$shipping_classes[$key]['dimensions'][$quantity]['weight']
				);

				$pp_cart[] = $add_item;
				$Item_no++;
			}
		}
		
		return $pp_cart;
	}
}

