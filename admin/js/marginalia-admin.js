/**
 * Marginalia Admin JavaScript
 *
 * @package Marginalia
 */

/* global marginalia, wp, ajaxurl */

(function() {
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
			var searchBtn = document.getElementById('marginalia-search-btn');
			var searchQuery = document.getElementById('marginalia-search-query');

			if (searchBtn) {
				searchBtn.addEventListener('click', this.searchBooks.bind(this));
			}

			if (searchQuery) {
				searchQuery.addEventListener('keypress', function(e) {
					if (e.which === 13) {
						e.preventDefault();
						this.searchBooks();
					}
				}.bind(this));
			}

			// Delegated events on document.
			document.addEventListener('click', function(e) {
				var target;

				if ((target = e.target.closest('.marginalia-select-book'))) {
					this.selectBook(e, target);
					return;
				}

				if ((target = e.target.closest('.marginalia-quick-add-btn'))) {
					this.openModal(e);
					return;
				}

				if ((target = e.target.closest('.marginalia-modal-close, .marginalia-modal-cancel, .marginalia-modal-overlay'))) {
					this.closeModal();
					return;
				}

				if ((target = e.target.closest('#marginalia-modal-search-btn'))) {
					this.modalSearchBooks();
					return;
				}

				if ((target = e.target.closest('.marginalia-modal-select-book'))) {
					this.modalSelectBook(e, target);
					return;
				}

				if ((target = e.target.closest('.marginalia-modal-back'))) {
					this.modalBackToSearch();
					return;
				}

				if ((target = e.target.closest('.marginalia-modal-create'))) {
					this.modalCreateBook(e, target);
					return;
				}
			}.bind(this));

			// Modal search keypress.
			document.addEventListener('keypress', function(e) {
				if (e.target.id === 'marginalia-modal-search-query' && e.which === 13) {
					e.preventDefault();
					this.modalSearchBooks();
				}
			}.bind(this));

			// Close modal on escape key.
			document.addEventListener('keydown', function(e) {
				if (e.which === 27) {
					this.closeModal();
				}
			}.bind(this));
		},

		/**
		 * Initialize quick add button on books list page.
		 */
		initQuickAddButton: function() {
			var template = document.getElementById('tmpl-marginalia-quick-add-button');
			if (template) {
				var buttonHtml = template.innerHTML;
				var pageTitleAction = document.querySelector('.page-title-action');
				if (pageTitleAction) {
					pageTitleAction.insertAdjacentHTML('afterend', buttonHtml);
				}
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
			var modal = document.getElementById('marginalia-quick-add-modal');
			if (modal) {
				modal.style.display = '';
			}
			var searchInput = document.getElementById('marginalia-modal-search-query');
			if (searchInput) {
				searchInput.focus();
			}
			document.body.classList.add('marginalia-modal-open');
		},

		/**
		 * Close the quick add modal.
		 */
		closeModal: function() {
			var modal = document.getElementById('marginalia-quick-add-modal');
			if (modal) {
				modal.style.display = 'none';
			}
			document.body.classList.remove('marginalia-modal-open');
			this.resetModal();
		},

		/**
		 * Reset modal to initial state.
		 */
		resetModal: function() {
			var searchQuery = document.getElementById('marginalia-modal-search-query');
			if (searchQuery) {
				searchQuery.value = '';
			}

			var searchResults = document.getElementById('marginalia-modal-search-results');
			if (searchResults) {
				searchResults.innerHTML = '';
			}

			var searchStatus = document.getElementById('marginalia-modal-search-status');
			if (searchStatus) {
				searchStatus.style.display = 'none';
				searchStatus.textContent = '';
			}

			var modalSearch = document.querySelector('.marginalia-modal-search');
			if (modalSearch) {
				modalSearch.style.display = '';
			}

			var bookDetails = document.querySelector('.marginalia-modal-book-details');
			if (bookDetails) {
				bookDetails.style.display = 'none';
			}

			var backBtn = document.querySelector('.marginalia-modal-back');
			if (backBtn) {
				backBtn.style.display = 'none';
			}

			var createBtn = document.querySelector('.marginalia-modal-create');
			if (createBtn) {
				createBtn.style.display = 'none';
			}

			var readingStatus = document.getElementById('marginalia-modal-reading-status');
			if (readingStatus) {
				readingStatus.value = '';
			}

			var dateStarted = document.getElementById('marginalia-modal-date-started');
			if (dateStarted) {
				dateStarted.value = '';
			}

			var dateFinished = document.getElementById('marginalia-modal-date-finished');
			if (dateFinished) {
				dateFinished.value = '';
			}

			var ratingZero = document.querySelector('input[name="marginalia_modal_rating"][value="0"]');
			if (ratingZero) {
				ratingZero.checked = true;
			}

			var privateCheckbox = document.getElementById('marginalia-modal-private');
			if (privateCheckbox) {
				privateCheckbox.checked = false;
			}

			this.selectedBook = null;
		},

		/**
		 * Search books from modal.
		 */
		modalSearchBooks: function() {
			var queryEl = document.getElementById('marginalia-modal-search-query');
			var typeEl = document.getElementById('marginalia-modal-search-type');
			var query = queryEl ? queryEl.value.trim() : '';
			var type = typeEl ? typeEl.value : '';

			if (!query) {
				this.showModalStatus(marginalia.strings.error, 'error');
				return;
			}

			this.showModalStatus(marginalia.strings.searching, 'loading');
			var searchResults = document.getElementById('marginalia-modal-search-results');
			if (searchResults) {
				searchResults.innerHTML = '';
			}

			var params = new URLSearchParams();
			params.append('action', 'marginalia_search_books');
			params.append('nonce', marginalia.nonce);
			params.append('query', query);
			params.append('type', type);

			fetch(marginalia.ajax_url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: params.toString()
			})
			.then(function(response) { return response.json(); })
			.then(function(response) {
				if (response.success && response.data.length > 0) {
					this.displayModalResults(response.data);
					this.showModalStatus('');
				} else {
					this.showModalStatus(marginalia.strings.no_results, 'error');
				}
			}.bind(this))
			.catch(function() {
				this.showModalStatus(marginalia.strings.error, 'error');
			}.bind(this));
		},

		/**
		 * Display search results in modal.
		 *
		 * @param {Array} results Search results.
		 */
		displayModalResults: function(results) {
			var container = document.getElementById('marginalia-modal-search-results');
			if (!container) {
				return;
			}
			container.innerHTML = '';

			results.forEach(function(book) {
				var coverUrl = book.cover_url || marginalia.placeholder_cover;
				var authors = book.authors ? book.authors.join(', ') : book.author || '';

				var html = '<div class="marginalia-search-result">' +
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
				'</div>';

				container.insertAdjacentHTML('beforeend', html);
			}.bind(this));
		},

		/**
		 * Select a book in the modal.
		 *
		 * @param {Event}   e      Click event.
		 * @param {Element} button The clicked button element.
		 */
		modalSelectBook: function(e, button) {
			e.preventDefault();

			var key = button.dataset.key;

			button.disabled = true;
			button.textContent = marginalia.strings.loading_details;

			var params = new URLSearchParams();
			params.append('action', 'marginalia_get_book_details');
			params.append('nonce', marginalia.nonce);
			params.append('key', key);

			fetch(marginalia.ajax_url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: params.toString()
			})
			.then(function(response) { return response.json(); })
			.then(function(response) {
				if (response.success) {
					this.selectedBook = response.data;
					this.showModalBookDetails(response.data);
				} else {
					this.showModalStatus(response.data.message || marginalia.strings.error, 'error');
					button.disabled = false;
					button.textContent = marginalia.strings.select_book;
				}
			}.bind(this))
			.catch(function() {
				this.showModalStatus(marginalia.strings.error, 'error');
				button.disabled = false;
				button.textContent = marginalia.strings.select_book;
			}.bind(this));
		},

		/**
		 * Show book details in modal.
		 *
		 * @param {Object} book Book data.
		 */
		showModalBookDetails: function(book) {
			var coverUrl = book.cover_url_large || book.cover_url_medium || marginalia.placeholder_cover;

			var coverImg = document.getElementById('marginalia-modal-book-cover');
			if (coverImg) {
				coverImg.src = coverUrl;
			}

			var titleEl = document.getElementById('marginalia-modal-book-title');
			if (titleEl) {
				titleEl.textContent = book.title;
			}

			var authorEl = document.getElementById('marginalia-modal-book-author');
			if (authorEl) {
				authorEl.textContent = book.author || '';
			}

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

			var metaEl = document.getElementById('marginalia-modal-book-meta');
			if (metaEl) {
				metaEl.textContent = meta.join(' • ');
			}

			var modalSearch = document.querySelector('.marginalia-modal-search');
			if (modalSearch) {
				modalSearch.style.display = 'none';
			}

			var bookDetailsEl = document.querySelector('.marginalia-modal-book-details');
			if (bookDetailsEl) {
				bookDetailsEl.style.display = '';
			}

			var backBtn = document.querySelector('.marginalia-modal-back');
			if (backBtn) {
				backBtn.style.display = '';
			}

			var createBtn = document.querySelector('.marginalia-modal-create');
			if (createBtn) {
				createBtn.style.display = '';
			}
		},

		/**
		 * Go back to search from book details.
		 */
		modalBackToSearch: function() {
			var bookDetails = document.querySelector('.marginalia-modal-book-details');
			if (bookDetails) {
				bookDetails.style.display = 'none';
			}

			var modalSearch = document.querySelector('.marginalia-modal-search');
			if (modalSearch) {
				modalSearch.style.display = '';
			}

			var backBtn = document.querySelector('.marginalia-modal-back');
			if (backBtn) {
				backBtn.style.display = 'none';
			}

			var createBtn = document.querySelector('.marginalia-modal-create');
			if (createBtn) {
				createBtn.style.display = 'none';
			}

			this.selectedBook = null;
		},

		/**
		 * Create book from modal.
		 *
		 * @param {Event}   e         Click event.
		 * @param {Element} createBtn The clicked button element.
		 */
		modalCreateBook: function(e, createBtn) {
			if (!this.selectedBook) {
				return;
			}

			createBtn.disabled = true;
			createBtn.textContent = marginalia.strings.creating_book;

			var ratingEl = document.querySelector('input[name="marginalia_modal_rating"]:checked');
			var readingStatusEl = document.getElementById('marginalia-modal-reading-status');
			var dateStartedEl = document.getElementById('marginalia-modal-date-started');
			var dateFinishedEl = document.getElementById('marginalia-modal-date-finished');
			var privateEl = document.getElementById('marginalia-modal-private');

			var params = new URLSearchParams();
			params.append('action', 'marginalia_quick_add_book');
			params.append('nonce', marginalia.nonce);
			params.append('title', this.selectedBook.title);
			params.append('author', this.selectedBook.author);
			params.append('isbn_10', this.selectedBook.isbn_10);
			params.append('isbn_13', this.selectedBook.isbn_13);
			params.append('oclc', this.selectedBook.oclc);
			params.append('publisher', this.selectedBook.publisher);
			params.append('publication_date', this.selectedBook.publication_date);
			params.append('page_count', this.selectedBook.page_count);
			params.append('openlibrary_key', this.selectedBook.key);
			params.append('cover_url', this.selectedBook.cover_url_large || this.selectedBook.cover_url_medium || '');
			params.append('reading_status', readingStatusEl ? readingStatusEl.value : '');
			params.append('date_started', dateStartedEl ? dateStartedEl.value : '');
			params.append('date_finished', dateFinishedEl ? dateFinishedEl.value : '');
			params.append('star_rating', ratingEl ? ratingEl.value : '0');
			params.append('post_private', privateEl && privateEl.checked ? '1' : '0');

			fetch(marginalia.ajax_url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: params.toString()
			})
			.then(function(response) { return response.json(); })
			.then(function(response) {
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
					createBtn.disabled = false;
					createBtn.textContent = marginalia.strings.create_book;
				}
			}.bind(this))
			.catch(function() {
				this.showModalStatus(marginalia.strings.error, 'error');
				createBtn.disabled = false;
				createBtn.textContent = marginalia.strings.create_book;
			}.bind(this));
		},

		/**
		 * Show modal status message.
		 *
		 * @param {string} message Message to display.
		 * @param {string} type    Message type.
		 */
		showModalStatus: function(message, type) {
			var status = document.getElementById('marginalia-modal-search-status');
			if (!status) {
				return;
			}

			status.classList.remove('loading', 'success', 'error');

			if (message) {
				if (type) {
					status.classList.add(type);
				}
				status.innerHTML = message;
				status.style.display = '';
			} else {
				status.style.display = 'none';
			}
		},

		/**
		 * Show success notice after creating book.
		 *
		 * @param {string} message  Success message.
		 * @param {string} editUrl  URL to edit the book.
		 */
		showSuccessNotice: function(message, editUrl) {
			var html = '<div class="notice notice-success is-dismissible"><p>' +
				this.escapeHtml(message) +
				' <a href="' + editUrl + '">' + marginalia.strings.edit_book + '</a>' +
				'</p></div>';

			var heading = document.querySelector('.wrap > h1');
			if (heading) {
				heading.insertAdjacentHTML('afterend', html);
			}
		},

		/**
		 * Search OpenLibrary for books (edit page).
		 */
		searchBooks: function() {
			var queryEl = document.getElementById('marginalia-search-query');
			var typeEl = document.getElementById('marginalia-search-type');
			var query = queryEl ? queryEl.value.trim() : '';
			var type = typeEl ? typeEl.value : '';

			if (!query) {
				this.showStatus(marginalia.strings.error, 'error');
				return;
			}

			this.showStatus(marginalia.strings.searching, 'loading');
			var searchResults = document.getElementById('marginalia-search-results');
			if (searchResults) {
				searchResults.innerHTML = '';
			}

			var params = new URLSearchParams();
			params.append('action', 'marginalia_search_books');
			params.append('nonce', marginalia.nonce);
			params.append('query', query);
			params.append('type', type);

			fetch(marginalia.ajax_url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: params.toString()
			})
			.then(function(response) { return response.json(); })
			.then(function(response) {
				if (response.success && response.data.length > 0) {
					this.displayResults(response.data);
					this.showStatus('');
				} else {
					this.showStatus(marginalia.strings.no_results, 'error');
				}
			}.bind(this))
			.catch(function() {
				this.showStatus(marginalia.strings.error, 'error');
			}.bind(this));
		},

		/**
		 * Display search results (edit page).
		 *
		 * @param {Array} results Search results.
		 */
		displayResults: function(results) {
			var container = document.getElementById('marginalia-search-results');
			if (!container) {
				return;
			}
			container.innerHTML = '';

			results.forEach(function(book) {
				var coverUrl = book.cover_url || marginalia.placeholder_cover;
				var authors = book.authors ? book.authors.join(', ') : book.author || '';

				var html = '<div class="marginalia-search-result">' +
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
				'</div>';

				container.insertAdjacentHTML('beforeend', html);
			}.bind(this));
		},

		/**
		 * Select a book and populate fields (edit page).
		 *
		 * @param {Event}   e      Click event.
		 * @param {Element} button The clicked button element.
		 */
		selectBook: function(e, button) {
			e.preventDefault();

			var key = button.dataset.key;

			// Check if fields have values.
			var hasValues = this.fieldsHaveValues();
			if (hasValues && !confirm(marginalia.strings.confirm_overwrite)) {
				return;
			}

			button.disabled = true;
			button.textContent = marginalia.strings.loading_details;

			var params = new URLSearchParams();
			params.append('action', 'marginalia_get_book_details');
			params.append('nonce', marginalia.nonce);
			params.append('key', key);

			fetch(marginalia.ajax_url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: params.toString()
			})
			.then(function(response) { return response.json(); })
			.then(function(response) {
				if (response.success) {
					this.populateFields(response.data);
					this.showStatus(marginalia.strings.fields_populated, 'success');
				} else {
					this.showStatus(response.data.message || marginalia.strings.error, 'error');
				}
				button.disabled = false;
				button.textContent = marginalia.strings.select_book;
			}.bind(this))
			.catch(function() {
				this.showStatus(marginalia.strings.error, 'error');
				button.disabled = false;
				button.textContent = marginalia.strings.select_book;
			}.bind(this));
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
				var el = document.querySelector(fields[i]);
				if (el && el.value && el.value.trim()) {
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
			var fieldMap = {
				'author': 'marginalia-author',
				'isbn_10': 'marginalia-isbn-10',
				'isbn_13': 'marginalia-isbn-13',
				'oclc': 'marginalia-oclc',
				'publisher': 'marginalia-publisher',
				'publication_date': 'marginalia-publication-date',
				'page_count': 'marginalia-page-count',
				'key': 'marginalia-openlibrary-key'
			};

			for (var dataKey in fieldMap) {
				if (data[dataKey]) {
					var el = document.getElementById(fieldMap[dataKey]);
					if (el) {
						el.value = data[dataKey];
					}
				}
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

			var postIdEl = document.getElementById('post_ID');
			var postId = postIdEl ? postIdEl.value : 0;

			var params = new URLSearchParams();
			params.append('action', 'marginalia_import_cover');
			params.append('nonce', marginalia.nonce);
			params.append('cover_url', coverUrl);
			params.append('post_id', postId);
			params.append('title', title);

			fetch(marginalia.ajax_url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: params.toString()
			})
			.then(function(response) { return response.json(); })
			.then(function(response) {
				if (response.success) {
					this.showStatus(marginalia.strings.cover_imported, 'success');

					// Update featured image in editor.
					if (response.data.attachment_id && typeof wp !== 'undefined' && wp.media) {
						this.setFeaturedImage(response.data.attachment_id);
					}
				} else {
					this.showStatus(response.data.message || marginalia.strings.cover_import_error, 'error');
				}
			}.bind(this))
			.catch(function() {
				this.showStatus(marginalia.strings.cover_import_error, 'error');
			}.bind(this));
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
			var titleEl = document.getElementById('title');
			if (titleEl) {
				titleEl.value = title;
				titleEl.dispatchEvent(new Event('input'));
				titleEl.dispatchEvent(new Event('blur'));
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
			var metabox = document.getElementById('postimagediv');
			if (metabox) {
				var postIdEl = document.getElementById('post_ID');
				var nonceEl = document.getElementById('_wpnonce');

				var params = new URLSearchParams();
				params.append('action', 'get-post-thumbnail-html');
				params.append('post_id', postIdEl ? postIdEl.value : '');
				params.append('thumbnail_id', attachmentId);
				params.append('_wpnonce', nonceEl ? nonceEl.value : '');

				fetch(ajaxurl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
					body: params.toString()
				})
				.then(function(response) { return response.text(); })
				.then(function(responseText) {
					if (responseText && responseText !== '0') {
						var inside = metabox.querySelector('.inside');
						if (inside) {
							inside.innerHTML = responseText;
						}
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
			var status = document.getElementById('marginalia-search-status');
			if (!status) {
				return;
			}

			status.classList.remove('loading', 'success', 'error');

			if (message) {
				if (type) {
					status.classList.add(type);
				}
				status.textContent = message;
				status.style.display = '';
			} else {
				status.style.display = 'none';
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
	document.addEventListener('DOMContentLoaded', function() {
		Marginalia.init();
	});

})();
