=== Marginalia ===
Contributors: lastsplash
Tags: books, reading, reading log, book reviews, book tracker
Requires at least: 6.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A personal reading log for WordPress. Track books you're reading with reviews, ratings, and reading progress.

== Description ==

Marginalia is a reading log plugin for WordPress that lets you catalog your books, track your reading progress, and share reviews on your site.

= Features =

* **Book Library** — A dedicated "Book" post type with support for titles, reviews, cover images, and excerpts.
* **Reading Status Tracking** — Organize books as "To Read," "Currently Reading," "Read," or "Did Not Finish."
* **Star Ratings** — Rate books on a 0–5 star scale with a dedicated block for display.
* **Book Metadata** — Store author, publisher, publication date, page count, ISBN-10, ISBN-13, and OCLC numbers.
* **Reading Dates** — Record when you started and finished each book.
* **OpenLibrary Integration** — Search for books and import metadata and cover images directly from OpenLibrary.
* **Quick Add** — Add books from the admin toolbar without leaving the page you're on.
* **Duplicate Detection** — Warns you before adding a book that already exists in your library by ISBN or OpenLibrary key.
* **Block Patterns** — Pre-built patterns for displaying currently reading, to read, read, and book grid layouts.
* **Schema.org Markup** — Automatic JSON-LD structured data for books and reviews on single book pages.
* **REST API Support** — Full REST API access to books, reading statuses, and all book metadata.
* **Admin Columns** — Sortable columns for author, rating, and reading dates in the book list.
* **Private Books** — Option to mark books as private so they are not visible to site visitors.

= Block Patterns =

Marginalia includes several block patterns in the "Book Lists" category:

* **Currently Reading Books** — Displays books with the "Currently Reading" status.
* **Books to Read** — Displays books with the "To Read" status.
* **Read Books** — Displays books with the "Read" status, including star ratings and pagination.
* **Book Grid** — A four-column grid of all books with pagination.
* **Single Book Display** — A full book layout with cover, reading status badge, star rating, and review content.

= Template Functions =

The plugin provides helper functions for theme developers:

* `marginalia_get_book_rating( $post_id )` — Returns the star rating for a book.
* `marginalia_get_reading_status( $post_id )` — Returns the reading status term name.
* `marginalia_get_book_author( $post_id )` — Returns the book author.
* `marginalia_display_stars( $rating, $echo )` — Outputs or returns star rating HTML.

== Installation ==

1. Upload the `marginalia-reading-log` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. A new "Books" menu item will appear in the admin sidebar.
4. Start adding books and tracking your reading.

== Frequently Asked Questions ==

= How do I add a book? =

Go to Books > Add New Book in the WordPress admin. You can enter details manually or use the OpenLibrary search to import book information and cover images automatically.

= How do I display books on my site? =

Use the included block patterns. In the block editor, open the block inserter, navigate to Patterns, and look under the "Book Lists" category. You can also build custom layouts using the Book query loop and the Star Rating block.

= Can I import book covers? =

Yes. When using the OpenLibrary search, you can import cover images in small, medium, or large sizes directly into your WordPress media library.

= What happens to my data if I deactivate or uninstall the plugin? =

Your books and all associated data are preserved when the plugin is deactivated or uninstalled. No data is deleted.

= Does the plugin support the block editor? =

Yes. Marginalia includes a star rating block, reading status integration in the block editor sidebar, and several block patterns for displaying your book collection.

== Screenshots ==

1. The book editing screen with metadata fields and OpenLibrary search.
2. Admin book list with sortable columns for author, rating, and dates.
3. Block patterns for displaying books on the front end.
4. Quick add modal for fast book entry from OpenLibrary.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
