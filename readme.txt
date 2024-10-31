Parcel Perfect Shipping Plugin
Contributors: MESS10
Requires at least: 5.5
Tested up to: 5.8
Requires PHP: 7.0
Stable tag: 2.6.4
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

This is the Parcel Perfect plugin for WordPress.

Tested against WordPress 5.8 and WooCommerce 5.5.2, yet it should work with versions 4.x and 3.x respectively.

Download and install the plugin through the normal WordPress plugin installation process.

A license is required for the plugin to function.  Please visit <http://www.alwynmalan.co.za> if you would like to purchase a license.

Once installed, activate this plugin.
Please contact us at

== Requirements: == 

 * Parcel Perfect API uses cm and kg for product dimensions and weight.  Ideally set Woocommerce to use the same.
 * Aouth African Rand is the required currency.
 * A valid license is required along with valid Parcel Perfect API credentials.
 
== How it works: == 

 * Adds a suburb field to the checkout page.  This suburb field uses a search box to find the delivery suburb in order to get the place code to accurate register collections.
 * If payment was made after order placement and payment was successful a waybill and label(s) will be generated which can be downloaded in the admin section when viewing an order.
 * If you would like to get shipping costs from Parcel Perfect add Parcel Perfect as an option in Woocommerce->Settings->Shipping Zones.
 * Instances of EFT or Cheque payments etc. where payment is not handled via a Payment Portal will allow you to view the order in the admin section and generate a waybill and label(s) by clicking the "Generate Waybill" button.
 
== Installation: == 

 * Download and activate plugin
 * Go to Woocommerce->Settings->Shipping->Parcel Perfect
 * Fill out the details on the settings page.
 * <strong>Enabled:</strong> Enable/disable the plugin
 * <strong>License Number:</strong> Enter the license number provided.  If you don't have a license number please visit <http://www.alwynmalan.co.za>
 * <strong>Authorization Number:</strong> Enter the authorization number provided.  If you don't have a authorization number please visit <http://www.alwynmalan.co.za>
 * <strong>Perfect Parcel ecomService API Url:</strong> Enter the url for your Parcel Perfect ecomService.  Url should end with version number i.e. http://.../v19
 * <strong>Perfect Parcel ecomService Username:</strong> Your Parcel Perfect ecomService username <em>(provided by Parcel Perfect)</em>
 * <strong>Perfect Parcel ecomService Password:</strong> Your Parcel Perfect ecomService password <em>(provided by Parcel Perfect)</em>
 * <strong>Perfect Parcel integrationService API Url:</strong> Enter the url for your Parcel Perfect integrationService.  Url should end with version number i.e. http://.../v19
 * <strong>Perfect Parcel integrationService Username:</strong> Your Parcel Perfect integrationService username <em>(provided by Parcel Perfect)</em>
 * <strong>Perfect Parcel integrationService Password:</strong> Your Parcel Perfect integrationService password (<em>provided by Parcel Perfect)</em>
 * <strong>Waybill and Reference Abbreviation:</strong> Enter the abbreviation used for waybill numbers.  For example, if ABC is entered, your waybills will be ABC-W-{order number}
 * <strong>Shop Origin Place Code:</strong> Please select the suburb from which the packages will be sent.  Typically the suburb the business resides in
 * <strong>Delivery Options:</strong> Please select the delivery service to be used for deliveries
 * <strong>Enable PP Email:</strong> Enable/disable an additional e-mail to be sent to the specified address on any successful collection registrations
 * <strong>E-mail:</strong> E-mail address for the notification e-mail mentioned above
 
== Package Customization: == 
   
 * Go to the Parcel Perfect tab on the left hand side of the Wordpress admin dashboard 
 * This section is used to package items together.  In other words if you would like to box 6 items for delivery you can specify it here.   
 * Rules can be set up for each shipping class that have been set up.    
 * Click 'Add package' and select the shipping class you would like to create a rule for. Select 'Default - no shipping class' to set up a rule for all products not part of a shipping class.
 * Select the maximum amount of items per box.   
 * For each line between 1 and maximum amount of items per box, please specify the label for that amount of items as well as the dimensions.  For example, the box size might be different when 3 items were boxed compared to when 5 were boxed.  Each variation of item amount can be set here.
