/**
 * Marginalia Admin JavaScript
 *
 * @package Marginalia
 */

/* global marginalia, jQuery, wp, ajaxurl */

(function($) {
	'use strict';

	var Marginalia = {
		/**
		 * Currently selected book data for modal.
		 */
		selectedBook: null,

		/**
		 * Initialize the admin functionality.
		 */
		init: function() {
			this.bindEvents();
			this.initQuickAddButton();
		},

		/**
		 * Bind event handlers.
		 */
		bindEvents: function() {
			// Edit page search.
			$('#marginalia-search-btn').on('click', this.searchBooks.bind(this));
			$('#marginalia-search-query').on('keypress', function(e) {
				if (e.which === 13) {
					e.preventDefault();
					this.searchBooks();
				}
			}.bind(this));

			$(document).on('click', '.marginalia-select-book', this.selectBook.bind(this));

			// Modal events.
			$(document).on('click', '.marginalia-quick-add-btn', this.openModal.bind(this));
			$(document).on('click', '.marginalia-modal-close, .marginalia-modal-cancel, .marginalia-modal-overlay', this.closeModal.bind(this));
			$(document).on('click', '#marginalia-modal-search-btn', this.modalSearchBooks.bind(this));
			$(document).on('keypress', '#marginalia-modal-search-query', function(e) {
				if (e.which === 13) {
					e.preventDefault();
					this.modalSearchBooks();
				}
			}.bind(this));
			$(document).on('click', '.marginalia-modal-select-book', this.modalSelectBook.bind(this));
			$(document).on('click', '.marginalia-modal-back', this.modalBackToSearch.bind(this));
			$(document).on('click', '.marginalia-modal-create', this.modalCreateBook.bind(this));

			// Close modal on escape key.
			$(document).on('keydown', function(e) {
				if (e.which === 27) {
					this.closeModal();
				}
			}.bind(this));
		},

		/**
		 * Initialize quick add button on books list page.
		 */
		initQuickAddButton: function() {
			var $template = $('#tmpl-marginalia-quick-add-button');
			if ($template.length) {
				var buttonHtml = $template.html();
				$('.page-title-action').first().after(buttonHtml);
			}
		},

		/**
		 * Open the quick add modal.
		 *
		 * @param {Event} e Click event.
		 */
		openModal: function(e) {
			e.preventDefault();
			this.selectedBook = null;
			this.resetModal();
			$('#marginalia-quick-add-modal').show();
			$('#marginalia-modal-search-query').focus();
			$('body').addClass('marginalia-modal-open');
		},

		/**
		 * Close the quick add modal.
		 */
		closeModal: function() {
			$('#marginalia-quick-add-modal').hide();
			$('body').removeClass('marginalia-modal-open');
			this.resetModal();
		},

		/**
		 * Reset modal to initial state.
		 */
		resetModal: function() {
			$('#marginalia-modal-search-query').val('');
			$('#marginalia-modal-search-results').empty();
			$('#marginalia-modal-search-status').hide().text('');
			$('.marginalia-modal-search').show();
			$('.marginalia-modal-book-details').hide();
			$('.marginalia-modal-back').hide();
			$('.marginalia-modal-create').hide();
			$('#marginalia-modal-reading-status').val('');
			$('#marginalia-modal-date-started').val('');
			$('#marginalia-modal-date-finished').val('');
			$('input[name="marginalia_modal_rating"][value="0"]').prop('checked', true);
			$('#marginalia-modal-private').prop('checked', false);
			this.selectedBook = null;
		},

		/**
		 * Search books from modal.
		 */
		modalSearchBooks: function() {
			var query = $('#marginalia-modal-search-query').val().trim();
			var type = $('#marginalia-modal-search-type').val();

			if (!query) {
				this.showModalStatus(marginalia.strings.error, 'error');
				return;
			}

			this.showModalStatus(marginalia.strings.searching, 'loading');
			$('#marginalia-modal-search-results').empty();

			$.ajax({
				url: marginalia.ajax_url,
				type: 'POST',
				data: {
					action: 'marginalia_search_books',
					nonce: marginalia.nonce,
					query: query,
					type: type
				},
				success: function(response) {
					if (response.success && response.data.length > 0) {
						this.displayModalResults(response.data);
						this.showModalStatus('');
					} else {
						this.showModalStatus(marginalia.strings.no_results, 'error');
					}
				}.bind(this),
				error: function() {
					this.showModalStatus(marginalia.strings.error, 'error');
				}.bind(this)
			});
		},

		/**
		 * Display search results in modal.
		 *
		 * @param {Array} results Search results.
		 */
		displayModalResults: function(results) {
			var $container = $('#marginalia-modal-search-results');
			$container.empty();

			results.forEach(function(book) {
				var coverUrl = book.cover_url || marginalia.placeholder_cover;
				var authors = book.authors ? book.authors.join(', ') : book.author || '';

				var $result = $('<div class="marginalia-search-result">' +
					'<img class="marginalia-search-result-cover" src="' + this.escapeHtml(coverUrl) + '" alt="" />' +
					'<div class="marginalia-search-result-info">' +
						'<p class="marginalia-search-result-title">' + this.escapeHtml(book.title) + '</p>' +
						'<p class="marginalia-search-result-author">' + this.escapeHtml(authors) + '</p>' +
						'<p class="marginalia-search-result-meta">' +
							(book.first_publish_year ? book.first_publish_year + ' • ' : '') +
							(book.publisher ? this.escapeHtml(book.publisher) : '') +
						'</p>' +
						'<button type="button" class="button button-primary marginalia-modal-select-book" data-key="' + this.escapeHtml(book.key) + '">' +
							marginalia.strings.select_book +
						'</button>' +
					'</div>' +
				'</div>');

				$container.append($result);
			}.bind(this));
		},

		/**
		 * Select a book in the modal.
		 *
		 * @param {Event} e Click event.
		 */
		modalSelectBook: function(e) {
			e.preventDefault();

			var $button = $(e.currentTarget);
			var key = $button.data('key');

			$button.prop('disabled', true).text(marginalia.strings.loading_details);

			$.ajax({
				url: marginalia.ajax_url,
				type: 'POST',
				data: {
					action: 'marginalia_get_book_details',
					nonce: marginalia.nonce,
					key: key
				},
				success: function(response) {
					if (response.success) {
						this.selectedBook = response.data;
						this.showModalBookDetails(response.data);
					} else {
						this.showModalStatus(response.data.message || marginalia.strings.error, 'error');
						$button.prop('disabled', false).text(marginalia.strings.select_book);
					}
				}.bind(this),
				error: function() {
					this.showModalStatus(marginalia.strings.error, 'error');
					$button.prop('disabled', false).text(marginalia.strings.select_book);
				}.bind(this)
			});
		},

		/**
		 * Show book details in modal.
		 *
		 * @param {Object} book Book data.
		 */
		showModalBookDetails: function(book) {
			var coverUrl = book.cover_url_large || book.cover_url_medium || marginalia.placeholder_cover;

			$('#marginalia-modal-book-cover').attr('src', coverUrl);
			$('#marginalia-modal-book-title').text(book.title);
			$('#marginalia-modal-book-author').text(book.author || '');

			var meta = [];
			if (book.publisher) {
				meta.push(book.publisher);
			}
			if (book.publication_date) {
				meta.push(book.publication_date);
			}
			if (book.page_count) {
				meta.push(book.page_count + ' pages');
			}
			$('#marginalia-modal-book-meta').text(meta.join(' • '));

			$('.marginalia-modal-search').hide();
			$('.marginalia-modal-book-details').show();
			$('.marginalia-modal-back').show();
			$('.marginalia-modal-create').show();
		},

		/**
		 * Go back to search from book details.
		 */
		modalBackToSearch: function() {
			$('.marginalia-modal-book-details').hide();
			$('.marginalia-modal-search').show();
			$('.marginalia-modal-back').hide();
			$('.marginalia-modal-create').hide();
			this.selectedBook = null;
		},

		/**
		 * Create book from modal.
		 */
		modalCreateBook: function() {
			if (!this.selectedBook) {
				return;
			}

			var $createBtn = $('.marginalia-modal-create');
			$createBtn.prop('disabled', true).text(marginalia.strings.creating_book);

			var data = {
				action: 'marginalia_quick_add_book',
				nonce: marginalia.nonce,
				title: this.selectedBook.title,
				author: this.selectedBook.author,
				isbn_10: this.selectedBook.isbn_10,
				isbn_13: this.selectedBook.isbn_13,
				oclc: this.selectedBook.oclc,
				publisher: this.selectedBook.publisher,
				publication_date: this.selectedBook.publication_date,
				page_count: this.selectedBook.page_count,
				openlibrary_key: this.selectedBook.key,
				cover_url: this.selectedBook.cover_url_large || this.selectedBook.cover_url_medium || '',
				reading_status: $('#marginalia-modal-reading-status').val(),
				date_started: $('#marginalia-modal-date-started').val(),
				date_finished: $('#marginalia-modal-date-finished').val(),
				star_rating: $('input[name="marginalia_modal_rating"]:checked').val(),
				post_private: $('#marginalia-modal-private').is(':checked') ? '1' : '0'
			};

			$.ajax({
				url: marginalia.ajax_url,
				type: 'POST',
				data: data,
				success: function(response) {
					if (response.success) {
						this.closeModal();
						this.showSuccessNotice(response.data.message, response.data.edit_url);
						// Reload the page to show the new book.
						window.location.reload();
					} else {
						var errorMsg = response.data.message || marginalia.strings.error;
						if (response.data.duplicate_id) {
							errorMsg += ' <a href="' + response.data.edit_url + '">' + marginalia.strings.edit_existing + '</a>';
						}
						this.showModalStatus(errorMsg, 'error');
						$createBtn.prop('disabled', false).text(marginalia.strings.create_book);
					}
				}.bind(this),
				error: function() {
					this.showModalStatus(marginalia.strings.error, 'error');
					$createBtn.prop('disabled', false).text(marginalia.strings.create_book);
				}.bind(this)
			});
		},

		/**
		 * Show modal status message.
		 *
		 * @param {string} message Message to display.
		 * @param {string} type    Message type.
		 */
		showModalStatus: function(message, type) {
			var $status = $('#marginalia-modal-search-status');

			$status.removeClass('loading success error');

			if (message) {
				$status.addClass(type || '').html(message).show();
			} else {
				$status.hide();
			}
		},

		/**
		 * Show success notice after creating book.
		 *
		 * @param {string} message  Success message.
		 * @param {string} editUrl  URL to edit the book.
		 */
		showSuccessNotice: function(message, editUrl) {
			var $notice = $('<div class="notice notice-success is-dismissible"><p>' +
				this.escapeHtml(message) +
				' <a href="' + editUrl + '">' + marginalia.strings.edit_book + '</a>' +
				'</p></div>');

			$('.wrap > h1').after($notice);
		},

		/**
		 * Search OpenLibrary for books (edit page).
		 */
		searchBooks: function() {
			var query = $('#marginalia-search-query').val().trim();
			var type = $('#marginalia-search-type').val();

			if (!query) {
				this.showStatus(marginalia.strings.error, 'error');
				return;
			}

			this.showStatus(marginalia.strings.searching, 'loading');
			$('#marginalia-search-results').empty();

			$.ajax({
				url: marginalia.ajax_url,
				type: 'POST',
				data: {
					action: 'marginalia_search_books',
					nonce: marginalia.nonce,
					query: query,
					type: type
				},
				success: function(response) {
					if (response.success && response.data.length > 0) {
						this.displayResults(response.data);
						this.showStatus('');
					} else {
						this.showStatus(marginalia.strings.no_results, 'error');
					}
				}.bind(this),
				error: function() {
					this.showStatus(marginalia.strings.error, 'error');
				}.bind(this)
			});
		},

		/**
		 * Display search results (edit page).
		 *
		 * @param {Array} results Search results.
		 */
		displayResults: function(results) {
			var $container = $('#marginalia-search-results');
			$container.empty();

			results.forEach(function(book) {
				var coverUrl = book.cover_url || marginalia.placeholder_cover;
				var authors = book.authors ? book.authors.join(', ') : book.author || '';

				var $result = $('<div class="marginalia-search-result">' +
					'<img class="marginalia-search-result-cover" src="' + this.escapeHtml(coverUrl) + '" alt="" />' +
					'<div class="marginalia-search-result-info">' +
						'<p class="marginalia-search-result-title">' + this.escapeHtml(book.title) + '</p>' +
						'<p class="marginalia-search-result-author">' + this.escapeHtml(authors) + '</p>' +
						'<p class="marginalia-search-result-meta">' +
							(book.first_publish_year ? book.first_publish_year + ' • ' : '') +
							(book.publisher ? this.escapeHtml(book.publisher) : '') +
						'</p>' +
						'<button type="button" class="button marginalia-select-book" data-key="' + this.escapeHtml(book.key) + '">' +
							marginalia.strings.select_book +
						'</button>' +
					'</div>' +
				'</div>');

				$container.append($result);
			}.bind(this));
		},

		/**
		 * Select a book and populate fields (edit page).
		 *
		 * @param {Event} e Click event.
		 */
		selectBook: function(e) {
			e.preventDefault();

			var $button = $(e.currentTarget);
			var key = $button.data('key');

			// Check if fields have values.
			var hasValues = this.fieldsHaveValues();
			if (hasValues && !confirm(marginalia.strings.confirm_overwrite)) {
				return;
			}

			$button.prop('disabled', true).text(marginalia.strings.loading_details);

			$.ajax({
				url: marginalia.ajax_url,
				type: 'POST',
				data: {
					action: 'marginalia_get_book_details',
					nonce: marginalia.nonce,
					key: key
				},
				success: function(response) {
					if (response.success) {
						this.populateFields(response.data);
						this.showStatus(marginalia.strings.fields_populated, 'success');
					} else {
						this.showStatus(response.data.message || marginalia.strings.error, 'error');
					}
				}.bind(this),
				error: function() {
					this.showStatus(marginalia.strings.error, 'error');
				}.bind(this),
				complete: function() {
					$button.prop('disabled', false).text(marginalia.strings.select_book);
				}
			});
		},

		/**
		 * Check if form fields have values.
		 *
		 * @return {boolean} True if any field has a value.
		 */
		fieldsHaveValues: function() {
			// Check title in block editor.
			if (typeof wp !== 'undefined' && wp.data && wp.data.select) {
				var editorStore = wp.data.select('core/editor');
				if (editorStore && typeof editorStore.getEditedPostAttribute === 'function') {
					var title = editorStore.getEditedPostAttribute('title');
					if (title && title.trim()) {
						return true;
					}
				}
			}

			// Check classic editor title and other fields.
			var fields = [
				'#title',
				'#marginalia-author',
				'#marginalia-isbn-10',
				'#marginalia-isbn-13',
				'#marginalia-publisher',
				'#marginalia-openlibrary-key'
			];

			for (var i = 0; i < fields.length; i++) {
				if ($(fields[i]).val() && $(fields[i]).val().trim()) {
					return true;
				}
			}

			return false;
		},

		/**
		 * Populate form fields with book data.
		 *
		 * @param {Object} data Book data.
		 */
		populateFields: function(data) {
			// Populate title.
			if (data.title) {
				this.setPostTitle(data.title);
			}

			// Populate meta fields.
			if (data.author) {
				$('#marginalia-author').val(data.author);
			}

			if (data.isbn_10) {
				$('#marginalia-isbn-10').val(data.isbn_10);
			}

			if (data.isbn_13) {
				$('#marginalia-isbn-13').val(data.isbn_13);
			}

			if (data.oclc) {
				$('#marginalia-oclc').val(data.oclc);
			}

			if (data.publisher) {
				$('#marginalia-publisher').val(data.publisher);
			}

			if (data.publication_date) {
				$('#marginalia-publication-date').val(data.publication_date);
			}

			if (data.page_count) {
				$('#marginalia-page-count').val(data.page_count);
			}

			if (data.key) {
				$('#marginalia-openlibrary-key').val(data.key);
			}

			// Import cover image if available.
			if (data.cover_url_large) {
				this.importCoverImage(data.cover_url_large, data.title);
			}
		},

		/**
		 * Import cover image to media library.
		 *
		 * @param {string} coverUrl Cover image URL.
		 * @param {string} title    Book title.
		 */
		importCoverImage: function(coverUrl, title) {
			this.showStatus(marginalia.strings.importing_cover, 'loading');

			var postId = $('#post_ID').val() || 0;

			$.ajax({
				url: marginalia.ajax_url,
				type: 'POST',
				data: {
					action: 'marginalia_import_cover',
					nonce: marginalia.nonce,
					cover_url: coverUrl,
					post_id: postId,
					title: title
				},
				success: function(response) {
					if (response.success) {
						this.showStatus(marginalia.strings.cover_imported, 'success');

						// Update featured image in editor.
						if (response.data.attachment_id && typeof wp !== 'undefined' && wp.media) {
							this.setFeaturedImage(response.data.attachment_id);
						}
					} else {
						this.showStatus(response.data.message || marginalia.strings.cover_import_error, 'error');
					}
				}.bind(this),
				error: function() {
					this.showStatus(marginalia.strings.cover_import_error, 'error');
				}.bind(this)
			});
		},

		/**
		 * Set the post title in the editor.
		 *
		 * @param {string} title The title to set.
		 */
		setPostTitle: function(title) {
			// For block editor (Gutenberg).
			if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch && wp.data.select) {
				// Check if we're in the block editor.
				var editorStore = wp.data.select('core/editor');
				if (editorStore && typeof editorStore.getCurrentPostType === 'function') {
					wp.data.dispatch('core/editor').editPost({
						title: title
					});
					return;
				}
			}

			// For classic editor.
			var $title = $('#title');
			if ($title.length) {
				$title.val(title).trigger('input').trigger('blur');
			}
		},

		/**
		 * Set the featured image in the editor.
		 *
		 * @param {number} attachmentId Attachment ID.
		 */
		setFeaturedImage: function(attachmentId) {
			// For block editor.
			if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch) {
				wp.data.dispatch('core/editor').editPost({
					featured_media: attachmentId
				});
			}

			// For classic editor.
			if (typeof WPSetThumbnailID === 'function') {
				WPSetThumbnailID(attachmentId);
			}

			// Refresh the featured image metabox.
			var $metabox = $('#postimagediv');
			if ($metabox.length) {
				$.post(ajaxurl, {
					action: 'get-post-thumbnail-html',
					post_id: $('#post_ID').val(),
					thumbnail_id: attachmentId,
					_wpnonce: $('#_wpnonce').val()
				}, function(response) {
					if (response && response !== '0') {
						$('.inside', $metabox).html(response);
					}
				});
			}
		},

		/**
		 * Show status message (edit page).
		 *
		 * @param {string} message Message to display.
		 * @param {string} type    Message type (loading, success, error).
		 */
		showStatus: function(message, type) {
			var $status = $('#marginalia-search-status');

			$status.removeClass('loading success error');

			if (message) {
				$status.addClass(type || '').text(message).show();
			} else {
				$status.hide();
			}
		},

		/**
		 * Escape HTML entities.
		 *
		 * @param {string} text Text to escape.
		 * @return {string} Escaped text.
		 */
		escapeHtml: function(text) {
			if (!text) {
				return '';
			}

			var map = {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#039;'
			};

			return String(text).replace(/[&<>"']/g, function(m) {
				return map[m];
			});
		}
	};

	// Initialize on document ready.
	$(document).ready(function() {
		Marginalia.init();
	});

})(jQuery);
