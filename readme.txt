=== Personal Wishlist Manager ===
Contributors: yourusername
Tags: wishlist, products, shopping, catalog, gift list
Requires at least: 6.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A comprehensive personal wishlist manager with admin dashboard and beautiful frontend display.

== Description ==

Personal Wishlist Manager is a powerful WordPress plugin that allows you to create and manage a personal wishlist with a user-friendly admin interface and stunning frontend display.

= Features =

* **Easy-to-use Admin Dashboard** - Manage your wishlist items with a familiar WordPress interface
* **Beautiful Frontend Display** - Showcase your wishlist with responsive grid layouts
* **Advanced Filtering** - Filter items by category, tags, price range, and search
* **Customizable Display** - Choose from 1-4 column layouts
* **Category & Tag Management** - Organize items with categories and tags
* **Rich Item Details** - Include title, image, price, category, tags, and notes
* **AJAX Filtering** - Filter items without page reload (optional)
* **Responsive Design** - Looks great on all devices
* **Flexible Shortcode** - Display wishlist anywhere with customizable parameters
* **Import/Export** - Export to CSV or JSON format
* **Custom Styling** - Add your own CSS for complete control
* **SEO Friendly** - Clean, semantic HTML markup
* **Translation Ready** - Fully internationalized and ready for translation

= Shortcode Usage =

Display your wishlist on any page or post using the `[personal_wishlist]` shortcode.

**Basic usage:**
`[personal_wishlist]`

**With custom parameters:**
`[personal_wishlist columns="4" show_filters="true" sort="price_asc"]`

**Filter by category:**
`[personal_wishlist category="Electronics" columns="3"]`

= Available Shortcode Parameters =

* `columns` - Number of columns (1-4, default: 3)
* `category` - Filter to specific category
* `show_filters` - Show/hide filter panel (true/false, default: true)
* `sort` - Sort order (alphabetical, price_asc, price_desc, date_desc, default: alphabetical)
* `limit` - Maximum items to display (default: unlimited)
* `user_id` - Show specific user's wishlist (default: all items)

= Perfect For =

* Personal shopping lists
* Gift registries
* Product collections
* Wish lists
* Shopping inspiration boards
* Curated product collections

== Installation ==

1. Upload the `personal-wishlist-manager` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'Wishlist' in the admin menu to add your first item
4. Use the `[personal_wishlist]` shortcode on any page to display your wishlist
5. Configure settings under 'Wishlist > Settings'

== Frequently Asked Questions ==

= How do I display the wishlist on my site? =

Use the shortcode `[personal_wishlist]` on any page or post. You can customize the display using shortcode parameters.

= Can I filter items by category? =

Yes! You can use the `category` parameter in the shortcode, or enable the filter panel for visitors to filter items themselves.

= Can I customize the appearance? =

Yes! The plugin includes built-in styling, but you can add custom CSS in the Settings page or through your theme's stylesheet.

= Does it work with my theme? =

Yes! The plugin is designed to work with any properly-coded WordPress theme. It uses theme-agnostic styling that adapts to your site.

= Can I export my wishlist data? =

Yes! Go to Wishlist > Settings > Import/Export to export your data in CSV or JSON format.

= Is it mobile-friendly? =

Absolutely! The plugin is fully responsive and looks great on all devices.

= Can multiple users have their own wishlists? =

The current version is designed for a single admin's wishlist. Multi-user support may be added in a future version.

= How do I add my first item? =

Go to Wishlist > Add New in your WordPress admin, fill in the item details, and click Publish.

= Can I change the currency symbol? =

Yes! Go to Wishlist > Settings > Display to change the currency symbol and position.

= Does it slow down my site? =

No! The plugin is optimized for performance and only loads assets on pages where the shortcode is used.

== Screenshots ==

1. Admin items list with search and bulk actions
2. Add/Edit item form with media library integration
3. Categories management page
4. Tags cloud and list view
5. Settings page with multiple tabs
6. Frontend wishlist grid display
7. Filter panel with category, tag, and price filters
8. Responsive mobile view
9. Individual wishlist card with details
10. Empty state when no items match filters

== Changelog ==

= 1.0.0 =
* Initial release
* Admin dashboard with full CRUD functionality
* Frontend display with shortcode
* Category and tag management
* Advanced filtering system
* Responsive grid layouts
* Import/Export functionality
* Custom CSS support
* AJAX filtering option
* Translation ready

== Upgrade Notice ==

= 1.0.0 =
Initial release of Personal Wishlist Manager.

== Privacy Policy ==

Personal Wishlist Manager does not collect, store, or share any personal data. All wishlist data is stored locally in your WordPress database.

== Support ==

For support, feature requests, or bug reports, please visit the plugin support forum or contact the developer.

== Credits ==

Developed with love for the WordPress community.
