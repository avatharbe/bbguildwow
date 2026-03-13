(function($) {
	'use strict';

	var $level1 = $('#achiev-level1');
	var $level2 = $('#achiev-level2');
	var $overlay = $('#achiev-modal-overlay');

	function loadCategory(id, name) {
		$('#achiev-list-heading').text(name);
		$('#achiev-list-content').html('<p>Loading...</p>');
		$level1.hide();
		$level2.show();

		$.get(bbguild_achiev_list_url + id, function(data) {
			var html = '';
			if (!data.achievements || data.achievements.length === 0) {
				html = '<p>No achievements found.</p>';
			} else {
				for (var i = 0; i < data.achievements.length; i++) {
					var a = data.achievements[i];
					var cls = a.completed ? 'achiev-row' : 'achiev-row achiev-incomplete';
					var icon = a.icon ? '<img src="https://render.worldofwarcraft.com/icons/36/' + a.icon + '.jpg" width="36" height="36" alt="" />' : '';
					var date = a.completed ? a.completed_date : '';
					html += '<div class="' + cls + '" data-achievement-id="' + a.id + '">' +
						'<span class="achiev-row-icon">' + icon + '</span>' +
						'<span class="achiev-row-info">' +
							'<strong>' + $('<span>').text(a.title).html() + '</strong>' +
							'<span class="achiev-row-desc">' + $('<span>').text(a.description).html() + '</span>' +
						'</span>' +
						'<span class="achiev-row-meta">' +
							(a.points ? '<span class="achiev-points-badge">' + a.points + '</span>' : '') +
							(date ? '<span class="achiev-row-date">' + date + '</span>' : '') +
						'</span>' +
					'</div>';
				}
			}
			$('#achiev-list-content').html(html);
		}).fail(function() {
			$('#achiev-list-content').html('<p>Error loading achievements.</p>');
		});
	}

	function showDetail(id) {
		$('#achiev-modal-content').html('<p>Loading...</p>');
		$overlay.show();

		$.get(bbguild_achiev_detail_url + id, function(data) {
			var icon = data.icon ? '<img src="https://render.worldofwarcraft.com/icons/56/' + data.icon + '.jpg" width="56" height="56" alt="" />' : '';
			var date = data.completed ? data.completed_date : '';

			var html = '<div class="achiev-detail-header">' +
				'<span class="achiev-detail-icon">' + icon + '</span>' +
				'<div class="achiev-detail-title">' +
					'<h3>' + $('<span>').text(data.title).html() + '</h3>' +
					(data.points ? '<span class="achiev-points-badge achiev-points-lg">' + data.points + '</span>' : '') +
				'</div>' +
			'</div>' +
			'<p class="achiev-detail-desc">' + $('<span>').text(data.description).html() + '</p>';

			if (data.reward) {
				html += '<p class="achiev-detail-reward">' + $('<span>').text(data.reward).html() + '</p>';
			}

			if (date) {
				html += '<p class="achiev-detail-date">Completed: ' + date + '</p>';
			}

			if (data.criteria && data.criteria.length > 0) {
				html += '<div class="achiev-criteria"><h4>Criteria</h4>';
				for (var i = 0; i < data.criteria.length; i++) {
					var c = data.criteria[i];
					var pct = c.max > 0 ? Math.min(100, Math.round(c.quantity / c.max * 100)) : (c.quantity > 0 ? 100 : 0);
					html += '<div class="achiev-criterion">' +
						'<span class="achiev-criterion-desc">' + $('<span>').text(c.description).html() + '</span>' +
						'<div class="profile-progress border-4">' +
							'<div class="bar border-4 hover" style="width:' + pct + '%"></div>' +
							'<div class="bar-contents">' + c.quantity + ' / ' + c.max + '</div>' +
						'</div>' +
					'</div>';
				}
				html += '</div>';
			}

			$('#achiev-modal-content').html(html);
		}).fail(function() {
			$('#achiev-modal-content').html('<p>Error loading achievement detail.</p>');
		});
	}

	function backToCategories() {
		$level2.hide();
		$level1.show();
	}

	function closeModal() {
		$overlay.hide();
	}

	// Event handlers
	$(document).on('click', '.achiev-cat-card', function() {
		var id = $(this).data('category-id');
		var name = $(this).data('category-name');
		loadCategory(id, name);
	});

	$(document).on('click', '#achiev-back', function(e) {
		e.preventDefault();
		backToCategories();
	});

	$(document).on('click', '.achiev-row', function() {
		var id = $(this).data('achievement-id');
		if (id) {
			showDetail(id);
		}
	});

	$(document).on('click', '#achiev-modal-close', function() {
		closeModal();
	});

	$(document).on('click', '#achiev-modal-overlay', function(e) {
		if (e.target === this) {
			closeModal();
		}
	});

	$(document).on('keydown', function(e) {
		if (e.key === 'Escape') {
			if ($overlay.is(':visible')) {
				closeModal();
			} else if ($level2.is(':visible')) {
				backToCategories();
			}
		}
	});
})(jQuery);
