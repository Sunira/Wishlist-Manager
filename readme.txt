=== Personal Wishlist Manager ===
Contributors: sunira
Tags: wishlist, products, shopping, catalog, gift list
Requires at least: 6.0
Tested up to: 6.4
Stable tag: 1.0.5
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A personal wishlist manager with an admin dashboard, Gutenberg block support, shortcode support, and a responsive frontend display.

== Description ==

Personal Wishlist Manager lets you create and manage wishlist items from the WordPress admin area and display them on the frontend with either a shortcode or a Gutenberg block.

= Features =

* **Admin Dashboard** - Manage wishlist items with list, add, edit, view, delete, and bulk delete screens
* **Responsive Frontend Display** - Show items in a card grid with 1-4 columns
* **Advanced Filtering** - Filter items by search term, category, tags, price range, and sort order
* **Shortcode and Block Support** - Display the wishlist with a shortcode or Gutenberg block
* **Rich Item Details** - Store title, image, product link, price, category, tags, and notes
* **Tag Views and Category Overview** - Browse derived categories and tags in the admin
* **AJAX Filtering** - Update frontend results without a full page reload
* **Shortcode Generator** - Build customized shortcode output from the settings screen
* **Export Tools** - Export wishlist data to CSV or JSON
* **Custom Styling** - Add custom CSS from the plugin settings
* **Selective Asset Loading** - Frontend assets load only when the shortcode or block is present
* **Translation Ready** - Uses WordPress internationalization functions throughout

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
* `categories` - Filter to multiple categories
* `sort` - Sort order (alphabetical, price_asc, price_desc, date_desc, default: alphabetical)
* `limit` - Maximum items to display (default: unlimited)
* `user_id` - Show specific user's wishlist (default: all items)
* `show_filters` - Show/hide the entire filter panel (true/false, default: true)
* `show_search` - Show/hide the search field (true/false, default: true)
* `show_category` - Show/hide the category filter (true/false, default: true)
* `show_tags` - Show/hide the tags filter (true/false, default: true)
* `show_price` - Show/hide the price range filter (true/false, default: true)
* `show_sort` - Show/hide the sort dropdown (true/false, default: true)

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

Yes. The plugin is designed to work with standard WordPress themes and includes its own frontend styling.

= Can I export my wishlist data? =

Yes. Go to Wishlist > Settings > Import/Export to export your data in CSV or JSON format.

= Is it mobile-friendly? =

Absolutely! The plugin is fully responsive and looks great on all devices.

= Can multiple users have their own wishlists? =

The current version is designed for a single admin's wishlist. Multi-user support may be added in a future version.

= How do I add my first item? =

Go to Wishlist > Add New in your WordPress admin, fill in the item details, and click Publish.

= Can I change the currency symbol? =

Yes! Go to Wishlist > Settings > Display to change the currency symbol and position.

= Does it slow down my site? =

No. The plugin only loads frontend assets on pages where the shortcode or block is used.

== Screenshots ==

1. Admin items list with search and bulk actions
2. Add/Edit item form with media library integration
3. Categories overview page
4. Tags cloud and list view
5. Settings page with multiple tabs
6. Frontend wishlist grid display
7. Filter panel with category, tag, and price filters
8. Responsive mobile view
9. Individual wishlist card with details
10. Empty state when no items match filters

== Changelog ==

= 1.0.5 =
* Admin dashboard for wishlist item CRUD
* Frontend display via shortcode and Gutenberg block
* AJAX-powered filtering by search, category, tags, price, and sort
* CSV and JSON export tools
* Shortcode generator and custom CSS support
* Responsive frontend card grid

== Upgrade Notice ==

= 1.0.5 =
Current stable release of Personal Wishlist Manager.

== Privacy Policy ==

Personal Wishlist Manager does not collect, store, or share any personal data. All wishlist data is stored locally in your WordPress database.

== Support ==

For support, feature requests, or bug reports, please visit the plugin support forum or contact the developer.

== Credits ==

Developed with love for the WordPress community.
