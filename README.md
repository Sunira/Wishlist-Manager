# Personal Wishlist Manager

A focused WordPress plugin for running a clean, personal wishlist with fast frontend browsing and lightweight admin management.

## Highlights

- Clean admin workflow: list, add, edit, view, delete, and bulk delete items
- Frontend display via shortcode and Gutenberg block
- Responsive wishlist grid with mobile-focused filter behavior
- Filter by search, category, price range, and sort order
- Browser Quick Add bookmarklet with **editable review form** before save
- CSV/JSON export tools
- Custom CSS settings for frontend tweaks

## What You Can Store Per Item

- Title
- Product URL
- Image URL
- Price
- Category
- Description / reason

## Quick Start

1. Upload `personal-wishlist-manager` to `/wp-content/plugins/`
2. Activate the plugin in WordPress
3. Go to `Wishlist > Add New` and create an item
4. Add `[personal_wishlist]` to a page
5. Open `Wishlist > Settings` to tune layout, filters, and advanced options

## Frontend Usage

### Basic shortcode

```text
[personal_wishlist]
```

### Example with options

```text
[personal_wishlist columns="4" show_filters="true" sort="price_asc"]
```

### Common shortcode parameters

- `columns`: `1` to `4` (default `3`)
- `category`: single category filter
- `categories`: comma-separated categories
- `sort`: `alphabetical`, `price_asc`, `price_desc`, `date_desc` (default `date_desc`)
- `limit`: max number of items
- `user_id`: target user list
- `show_filters`: `true` / `false`
- `show_search`: `true` / `false`
- `show_category`: `true` / `false`
- `show_price`: `true` / `false`
- `show_sort`: `true` / `false`

## Browser Quick Add (Bookmarklet)

Use `Wishlist > Settings > Advanced > Browser Quick Add` to copy the bookmarklet.

When clicked on a product page:

1. Page data is captured (title, URL, image, price when available)
2. A prefilled form opens
3. You can edit fields
4. Item is saved only after you submit

## Mobile UX Notes

- Filter panel is optimized for smaller screens
- Price range fields are mobile-sized and currency formatted
- Card typography scales up for better readability

## Data + Privacy

- Data is stored in your local WordPress database
- No external tracking or third-party data sharing by default

## Development

Main plugin entry:

- `personal-wishlist-manager.php`

Core areas:

- `includes/` (database, admin/frontend logic, AJAX, helpers)
- `templates/` (frontend rendering)
- `admin/` (WP admin views/styles/scripts)
- `public/` (frontend styles/scripts/images)
