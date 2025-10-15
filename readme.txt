=== KiotViet Sync ===
Contributors: mykiot
Donate link: https://www.kiotviet.vn/
Tags: importer, sync, kiotviet, synchronized
Requires at least: 4.9
Tested up to: 6.5.5
Stable tag: trunk
Requires PHP: 7.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Plugin supports data synchronization between KiotViet and Wordpress (Woocommerce). Synchronous information including products, orders and categories.

== Description ==
We support you to synchronize data from KiotViet to Wordpress website via KiotViet Sync plugin.
Make it easier for you to reach online customers.
Create a WordPress Shop website more easily.
Synchronize inventory data, prices, photos, orders without complicated operations or boring manual tasks.
This plugin will help you
* Connecting with KiotViet is easy
* Synchronize products, orders, goods automatically
* Update inventory, photos, automatic status from KiotViet
* Completely free
Thanks for using our product.
[Contact support](https://www.kiotviet.vn/lien-he/ "Contact support")
Support On Facebook: [KiotViet Sync - Support Developers](https://www.facebook.com/groups/3109767249041169/ "KiotViet Sync - Support Developers")

== Installation ==
1. Upload "kiotviet-sync.zip" to the "/wp-content/plugins/" directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Use the plugin via the "KiotViet Sync" menu.

You can also:

1. Navigate to "Plugins" > "Add New".
2. Browse and search for plugins "KiotViet Sync"
3. Click on "Install Now" button
4. Click "Activate" to activate the plugin.

== Frequently Asked Questions ==
= What is KiotViet? =
KiotViet is a POS, supporting users to check inventory, sales and many other utilities.

= Does the woocommerce plugin is required? =
Yes. Woocommerce is required for KiotViet Sync plugin.

= Will it be free forever? =
Yes

== Screenshots ==
1. screenshot-1.png
2. screenshot-2.png
3. screenshot-3.png

== Changelog ==
= 1.8.4 =
* 05/03/2025
* CVSS: SQLi N XSSi
= 1.8.3 =
* 26/11/2024
* Auto sync orders vs Product sync
= 1.8.2 =
* 18/11/2024
* Disable image sync
= 1.8.1 =
* 02/10/2024
* Increase chunk size
= 1.8.0 =
* 13/09/2024
* Add guides
* Fix bugs: synchronize images and galleries
* Remove the description of the variation
= 1.7.9 =
* 23/05/2024
* Update method to retrieve order IDs
= 1.7.8 =
* 21/05/2024
* Bug fixes
= 1.7.7 =
* 20/05/2024
* Bug fixes
= 1.7.6 =
* 10/05/2024
* Get billing city N get billing country
= 1.7.5 =
* 06/05/2024
* Decouple stock sync from branch synch
= 1.7.4 =
* 02/05/2024
* Reorder sync
= 1.7.3 =
* 21/03/2024
* Fix Count Product
= 1.7.2 =
* 02/02/2024
* Fix IndexedDB
= 1.7.1 =
* 09/01/2024
* Fix IndexedDB
= 1.7.0 =
* 09/01/2024
* Update sync product
= 1.6.88 =
* 07/12/2023
* Fix: Sync category, sync product
= 1.6.87 =
* 25/11/2023
* Improve: Login
= 1.6.86 =
* 24/11/2023
* Improve: Sync category
= 1.6.84 =
* 23/08/2023
* Fix: sync product and category
= 1.6.83 =
* 19/08/2023
* Fix: cURL error 60: SSL certificate problem
= 1.6.82 =
* 03/08/2023
* Fix: sync order to KiotViet
= 1.6.81 =
* 27/07/2023
* Fix: get product info by sku
= 1.6.80 =
* 20/06/2023
* Fix webhook update product 
= 1.6.78 =
* 01/06/2023
* Sync only the categories where the product is synced.
= 1.6.77 =
* 01/06/2023
* Fix: plugin could not be activated
* Fix: product sync error
= 1.6.44 =
* 16/02/2023
* Fix webhook not update description 4
= 1.6.43 =
* 16/02/2023
* Fix webhook not update description 3
= 1.6.42 =
* 16/02/2023
* Fix webhook not update description 2
= 1.6.41 =
* 16/02/2023
* Fix webhook not update description
= 1.6.40 =
* 16/02/2023
* Fix UI migrate product 3
= 1.6.29 =
* 27/06/2022
* Fix UI migrate product 2
= 1.6.27 =
* 27/06/2022
* Fix UI migrate product
* Fix get pricebook
= 1.5.0 =
* 11/07/2022
* Fix product attribute deleted
* Fix product to trash after update
= 1.4.9 =
* 27/06/2022
* Update order products
= 1.4.8 =
* 15/06/2022
* Update error message sync order
= 1.4.7 =
* 25/05/2022
* Add Webhook's Status
* Add Webhook's cUrl Testing
= 1.4.6 =
* 13/04/2022
* Update WP function
* Fix timezone sync history
* Change path of log folder
* Fix clear stock cache after update
= 1.4.5 =
* Fix webhook action
= 1.4.4 =
* Fix sync product inventory
* Add button cancel connect in loading branch screen
= 1.4.3 =
* Add option only sync new products
* Update checker WP Cron
* Add payment method to description in order
= 1.4.2 =
* Add function auto sync orders
* Fix order sync
= 1.4.1 =
* Add new sync attribute option
* Update logic check product type
* Update force delete product from webhook
* Add new function to get surcharge from KiotViet
= 1.4.0 =
* Checking php requirements
* Fix issue get image from KiotViet
* Update new style
* Update log location to kiotviet_log in wp-content
* Update checker (curl, $_SERVER[“HTTP_HOST”])
* Update logic for sync product, variation product, images, product unit, category, discount
* Update workflow to sync branch first
* Add new page to check webhook registed
* Add clear cache button
* Update readme
= 1.3.0 =
* Checking php requirements
* Fix issue get image from KiotViet
= 1.2.0 =
* Sync product unit
* Remove info sync product as short description, tag …
* Update hash image name Kiotviet
* Update sync fast with product variable
* Change data type from text to longtext
* Update hook delete product website
* Update hook delete category website
* Fix pricebook and stock with product variable
* Change prefix order code Kiotviet
* Fix pricebook expires date
* Update webhook product simple to variant and opposite
* Update webhook stock product parent
* Fix manager customer by branch
= 1.1.0 =
* Update options for sync data
* Sync data by SKU
* Update logic for stock
* Add Sync button by manually
= 1.0.0 =
* Sync products, categories, orders, images
* Auto update via webhook
