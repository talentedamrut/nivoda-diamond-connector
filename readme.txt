=== Nivoda Diamond Connector ===
Contributors: talentedamrut
Tags: diamonds, nivoda, woocommerce, jewelry, ecommerce
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional WordPress plugin integrating Nivoda diamond API with WooCommerce for advanced diamond search and e-commerce.

== Description ==

Nivoda Diamond Connector seamlessly integrates the Nivoda diamond API with your WordPress WooCommerce store, providing a complete solution for selling diamonds online.

= Key Features =

* **Complete Nivoda API Integration** - Full GraphQL API wrapper with authentication and error handling
* **Advanced Search Interface** - Interactive filters for shape, carat, color, clarity, cut, and price
* **WooCommerce Integration** - Direct add-to-cart functionality with automatic product creation
* **Performance Optimized** - WordPress transients caching layer with configurable duration
* **Admin Tools** - Settings page, cache management, diamond sync, and API diagnostics
* **Custom Post Type** - Optional local diamond storage with taxonomies
* **Responsive Design** - Mobile-friendly interface with modern UI components

= Requirements =

* WordPress 5.8+
* PHP 7.4+
* WooCommerce 5.0+
* Nivoda API account

= Usage =

After installation and configuration:

1. Add `[nivoda_search]` shortcode to display the diamond search interface
2. Add `[nivoda_diamond id="DIAMOND_ID"]` to show a specific diamond
3. Configure markup pricing in settings
4. Manage cache and sync diamonds via admin tools

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/nivoda-diamond-connector/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to Nivoda Diamonds → Settings
4. Enter your Nivoda API key
5. Configure markup percentage and caching options
6. Test the API connection

== Frequently Asked Questions ==

= Do I need a Nivoda account? =

Yes, you need an active Nivoda account with API access to use this plugin.

= Is WooCommerce required? =

Yes, WooCommerce is required for the shopping cart and checkout functionality.

= How does caching work? =

The plugin uses WordPress transients to cache API responses. You can configure the cache duration in settings and clear cache anytime from the Tools page.

= Can I customize the appearance? =

Yes, you can override the plugin's CSS in your theme or copy template files to your theme folder for complete customization.

= How do I get support? =

Visit the plugin support forum or contact us at support@yourwebsite.com

== Screenshots ==

1. Diamond search interface with advanced filters
2. Diamond detail page with specifications
3. Admin settings page
4. Admin tools and diagnostics
5. WooCommerce cart integration

== Changelog ==

= 1.0.0 =
* Initial release
* Nivoda GraphQL API integration
* Advanced diamond search with filters
* WooCommerce integration
* Caching layer with transients
* Admin tools and diagnostics
* Custom Post Type support
* Responsive UI with sliders and carousel

== Upgrade Notice ==

= 1.0.0 =
Initial release of Nivoda Diamond Connector.
