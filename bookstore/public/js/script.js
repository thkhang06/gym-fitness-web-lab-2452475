/**
 * TIGER FITNESS - Client Interaction & AJAX Engine (Labs 1.3, 2 & 3)
 * Handles:
 *  - Lab 1.3 baseline interactive message helper (preserved)
 *  - Lab 3 Task 1: Live AJAX Class Search & Multi-Criteria Filtering
 *  - Lab 3 Task 2: Real-Time AJAX Class Capacity Check & Quick Booking/Membership Actions
 */
(function () {
  'use strict';

  /**
   * Escape HTML entities safely to prevent DOM XSS when rendering AJAX JSON data
   */
  function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function getCsrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  function debounce(fn, delayMs) {
    var timer = null;
    return function () {
      var ctx = this;
      var args = arguments;
      clearTimeout(timer);
      timer = setTimeout(function () {
        fn.apply(ctx, args);
      }, delayMs);
    };
  }

  // Preserved Lab 1.3 helper (used on fallback/non-AJAX button if needed)
  window.showMessage = function () {
    alert('Thank you! Your fitness class session request has been registered with TIGER FITNESS.');
  };

  /* -------------------------------------------------------------------
   * LAB 3 TASK 1: AJAX Search & Multi-Filter on pages/classes.php
   * ------------------------------------------------------------------- */
  function initAjaxClassSearch() {
    var form = document.getElementById('ajax-class-filter-form');
    var grid = document.getElementById('classes-grid-container');
    if (!form || !grid) return;

    var searchInput = document.getElementById('search');
    var catSelect = document.getElementById('category_id');
    var trainerSelect = document.getElementById('trainer');
    var availSelect = document.getElementById('availability');
    var dateInput = document.getElementById('filter_date');
    var sortSelect = document.getElementById('sort');
    var resetBtn = document.getElementById('ajax-reset-filters');
    var statusBanner = document.getElementById('ajax-search-status');
    var countEl = document.getElementById('classes-visible-count');

    function setLoading(isLoading) {
      if (!statusBanner) return;
      if (isLoading) {
        statusBanner.innerHTML = '<span class="ajax-spinner me-2"></span><span>Querying MySQL catalog via AJAX...</span>';
        statusBanner.classList.remove('d-none');
      } else {
        statusBanner.classList.add('d-none');
      }
    }

    function renderClassesGrid(data) {
      var classes = data.classes || [];
      var targetDate = data.date || (dateInput ? dateInput.value : '');
      if (countEl) {
        countEl.textContent = String(classes.length);
      }

      if (classes.length === 0) {
        grid.innerHTML =
          '<div class="col-12">' +
            '<div class="glass-panel p-5 text-center my-2">' +
              '<h5 class="fw-bold font-headline text-uppercase mb-1">No Training Classes Found</h5>' +
              '<p class="text-secondary mb-3">No classes matched your live search or availability filter for ' + escapeHtml(targetDate) + '.</p>' +
              '<button type="button" class="btn btn-outline-apex btn-sm" id="empty-state-reset-btn">Clear Filters</button>' +
            '</div>' +
          '</div>';
        var emptyReset = document.getElementById('empty-state-reset-btn');
        if (emptyReset && resetBtn) {
          emptyReset.addEventListener('click', function () {
            resetBtn.click();
          });
        }
        return;
      }

      var csrf = escapeHtml(getCsrfToken());
      var html = classes.map(function (cls) {
        var spotsBadge = cls.is_full
          ? '<span class="badge bg-danger-subtle text-danger border border-danger">FULL (' + escapeHtml(cls.booked_count) + '/' + escapeHtml(cls.max_capacity) + ')</span>'
          : '<span class="badge bg-success-subtle text-success border border-success">' + escapeHtml(cls.remaining_spots) + ' / ' + escapeHtml(cls.max_capacity) + ' spots left</span>';

        var staffControls = '';
        if (data.is_staff) {
          staffControls =
            '<div class="d-flex gap-2 pt-3 mt-3 border-top border-secondary border-opacity-25">' +
              '<a href="class_form.php?id=' + encodeURIComponent(cls.id) + '" class="btn btn-sm btn-outline-apex flex-grow-1 fw-semibold">Edit</a>' +
              '<form action="delete_class.php" method="POST" class="d-inline flex-grow-1" onsubmit="return confirm(\'Are you sure you want to permanently delete this fitness class?\');">' +
                '<input type="hidden" name="csrf_token" value="' + csrf + '">' +
                '<input type="hidden" name="class_id" value="' + escapeHtml(cls.id) + '">' +
                '<input type="hidden" name="id" value="' + escapeHtml(cls.id) + '">' +
                '<button type="submit" class="btn btn-sm btn-danger w-100 fw-semibold">Delete</button>' +
              '</form>' +
            '</div>';
        }

        return (
          '<div class="col-12 col-md-6 col-lg-4">' +
            '<article class="class-card h-100">' +
              '<div class="class-card-img-wrap">' +
                '<img src="../public/' + escapeHtml(cls.image_url) + '" alt="' + escapeHtml(cls.title) + ' training class">' +
                '<div class="class-card-overlay"></div>' +
                '<span class="class-discipline-badge">' + escapeHtml(cls.category_name) + '</span>' +
              '</div>' +
              '<div class="card-body d-flex flex-column p-4">' +
                '<h3 class="h5 card-title fw-bold font-headline mb-2">' + escapeHtml(cls.title) + '</h3>' +
                '<p class="card-text text-secondary small flex-grow-1 mb-3" style="line-height: 1.6;">' + escapeHtml(cls.short_description) + '</p>' +
                '<div class="pt-3 border-top border-secondary border-opacity-25 mb-3">' +
                  '<div class="d-flex align-items-center justify-content-between small text-secondary mb-1">' +
                    '<span class="fw-semibold">Lead Coach:</span>' +
                    '<span class="text-white fw-medium">' + escapeHtml(cls.trainer_name) + '</span>' +
                  '</div>' +
                  '<div class="d-flex align-items-center justify-content-between small text-secondary mb-1">' +
                    '<span class="fw-semibold">Schedule:</span>' +
                    '<span class="opacity-90 text-end">' + escapeHtml(cls.schedule_info) + '</span>' +
                  '</div>' +
                  '<div class="d-flex align-items-center justify-content-between small text-secondary">' +
                    '<span class="fw-semibold">Spots (' + escapeHtml(targetDate) + '):</span>' +
                    spotsBadge +
                  '</div>' +
                '</div>' +
                '<div class="d-flex align-items-center justify-content-between pt-3 border-top border-secondary border-opacity-25 gap-2">' +
                  '<div>' +
                    '<span class="small text-secondary d-block font-headline text-uppercase" style="font-size: 0.7rem;">Session Pass</span>' +
                    '<span class="class-price-tag">$' + escapeHtml(cls.price_formatted) + '</span>' +
                  '</div>' +
                  '<div class="d-flex gap-2">' +
                    '<a href="item_details.php?id=' + encodeURIComponent(cls.id) + '" class="btn btn-outline-apex btn-sm px-3 fw-semibold">Details</a>' +
                    '<a href="bookings.php?class_id=' + encodeURIComponent(cls.id) + '&amp;date=' + encodeURIComponent(targetDate) + '" class="btn btn-apex-crimson btn-sm px-3 fw-semibold">Book</a>' +
                  '</div>' +
                '</div>' +
                staffControls +
              '</div>' +
            '</article>' +
          '</div>'
        );
      }).join('');

      grid.innerHTML = html;
    }

    function executeAjaxSearch() {
      var params = new URLSearchParams({
        search: searchInput ? searchInput.value.trim() : '',
        category_id: catSelect ? catSelect.value : '',
        trainer: trainerSelect ? trainerSelect.value : '',
        availability: availSelect ? availSelect.value : 'all',
        date: dateInput ? dateInput.value : '',
        sort: sortSelect ? sortSelect.value : 'newest'
      });

      setLoading(true);
      fetch('../api/search_classes.php?' + params.toString(), {
        headers: { 'Accept': 'application/json' }
      })
        .then(function (resp) { return resp.json(); })
        .then(function (data) {
          setLoading(false);
          if (data && data.success) {
            renderClassesGrid(data);
          } else {
            grid.innerHTML = '<div class="col-12"><div class="alert alert-danger">Unable to filter classes right now.</div></div>';
          }
        })
        .catch(function () {
          setLoading(false);
          grid.innerHTML = '<div class="col-12"><div class="alert alert-danger">Network error while querying classes.</div></div>';
        });
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      executeAjaxSearch();
    });

    if (searchInput) {
      searchInput.addEventListener('input', debounce(executeAjaxSearch, 250));
    }
    [catSelect, trainerSelect, availSelect, dateInput, sortSelect].forEach(function (el) {
      if (el) {
        el.addEventListener('change', executeAjaxSearch);
      }
    });

    if (resetBtn) {
      resetBtn.addEventListener('click', function (e) {
        e.preventDefault();
        if (searchInput) searchInput.value = '';
        if (catSelect) catSelect.value = '';
        if (trainerSelect) trainerSelect.value = '';
        if (availSelect) availSelect.value = 'all';
        if (sortSelect) sortSelect.value = 'newest';
        executeAjaxSearch();
      });
    }
  }

  /* -------------------------------------------------------------------
   * LAB 3 TASK 2: Live AJAX Capacity Inspector (item_details.php & bookings.php)
   * ------------------------------------------------------------------- */
  function renderCapacityPanel(container, data) {
    var snap = data.snapshot;
    if (!snap || !container) return;

    var barColor = snap.is_full ? 'bg-danger' : (snap.fill_percent >= 75 ? 'bg-warning' : 'bg-success');
    var statusBadge = snap.is_full
      ? '<span class="badge bg-danger text-uppercase">Full Capacity Reached</span>'
      : (snap.remaining_spots <= 2
          ? '<span class="badge bg-warning text-dark text-uppercase">Limited Spots Left</span>'
          : '<span class="badge bg-success text-uppercase">Open for Booking</span>');

    var alreadyNote = snap.user_already_booked
      ? '<div class="small text-success fw-semibold mt-2">&#10003; You already have a confirmed booking for this session on ' + escapeHtml(snap.booking_date) + '.</div>'
      : '';

    container.innerHTML =
      '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">' +
        '<span class="small fw-bold font-headline text-uppercase text-secondary">Live Capacity (' + escapeHtml(snap.booking_date) + ')</span>' +
        statusBadge +
      '</div>' +
      '<div class="d-flex justify-content-between align-items-baseline mb-1">' +
        '<span class="fw-bold fs-5 text-white">' + escapeHtml(snap.remaining_spots) + ' <span class="small fw-normal text-secondary">spots remaining</span></span>' +
        '<span class="small text-secondary">' + escapeHtml(snap.booked_count) + ' / ' + escapeHtml(snap.max_capacity) + ' booked (' + escapeHtml(snap.fill_percent) + '%)</span>' +
      '</div>' +
      '<div class="capacity-progress mb-1">' +
        '<div class="capacity-progress-bar ' + barColor + '" style="width: ' + escapeHtml(snap.fill_percent) + '%;"></div>' +
      '</div>' +
      alreadyNote;
  }

  function initLiveCapacityCheckers() {
    var checkerRoot = document.getElementById('live-capacity-widget');
    if (!checkerRoot) return;

    var classIdEl = document.getElementById('capacity_class_id');
    var dateEl = document.getElementById('capacity_date');
    var outputBox = document.getElementById('live-capacity-output');
    var pillsContainer = document.getElementById('capacity-date-pills');
    var ajaxBookBtn = document.getElementById('ajax-quick-book-btn');
    var feedbackBox = document.getElementById('ajax-booking-feedback');
    var notesInput = document.getElementById('ajax_booking_notes');

    function syncBookButton(data) {
      if (!ajaxBookBtn || !data || !data.snapshot) return;
      var snap = data.snapshot;
      if (snap.user_already_booked) {
        ajaxBookBtn.disabled = true;
        ajaxBookBtn.textContent = 'Already Booked for ' + snap.booking_date;
      } else if (snap.is_full) {
        ajaxBookBtn.disabled = true;
        ajaxBookBtn.textContent = 'Class Full (' + snap.booked_count + '/' + snap.max_capacity + ')';
      } else {
        ajaxBookBtn.disabled = false;
        ajaxBookBtn.textContent = 'Book Session (' + snap.remaining_spots + ' spots left)';
      }
    }

    function renderPills(upcoming, selectedDate) {
      if (!pillsContainer || !Array.isArray(upcoming)) return;
      pillsContainer.innerHTML = upcoming.map(function (day) {
        var active = day.date === selectedDate ? 'active' : '';
        var tag = day.is_full
          ? '<span class="text-danger fw-bold">Full</span>'
          : '<span class="text-success fw-semibold">' + escapeHtml(day.remaining_spots) + '/' + escapeHtml(day.max_capacity) + '</span>';
        return (
          '<button type="button" class="date-pill-btn ' + active + '" data-date="' + escapeHtml(day.date) + '">' +
            '<span class="d-block fw-bold">' + escapeHtml(day.label) + '</span>' +
            '<span class="small">' + tag + '</span>' +
          '</button>'
        );
      }).join('');

      pillsContainer.querySelectorAll('.date-pill-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          if (dateEl) {
            dateEl.value = btn.getAttribute('data-date');
            loadCapacity();
          }
        });
      });
    }

    function loadCapacity() {
      if (!classIdEl || !dateEl || !outputBox) return;
      var cid = classIdEl.value;
      var bdate = dateEl.value;
      if (!cid || !bdate) return;

      outputBox.innerHTML = '<div class="small text-secondary"><span class="ajax-spinner me-2"></span>Checking live MySQL spot count...</div>';

      fetch('../api/class_capacity.php?class_id=' + encodeURIComponent(cid) + '&date=' + encodeURIComponent(bdate), {
        headers: { 'Accept': 'application/json' }
      })
        .then(function (resp) { return resp.json(); })
        .then(function (data) {
          if (data && data.success) {
            renderCapacityPanel(outputBox, data);
            renderPills(data.upcoming, data.snapshot.booking_date);
            syncBookButton(data);
          } else {
            outputBox.innerHTML = '<div class="alert alert-warning py-2 small mb-0">' + escapeHtml((data && data.message) || 'Could not load class capacity.') + '</div>';
          }
        })
        .catch(function () {
          outputBox.innerHTML = '<div class="alert alert-danger py-2 small mb-0">Error fetching class capacity.</div>';
        });
    }

    if (classIdEl) classIdEl.addEventListener('change', loadCapacity);
    if (dateEl) dateEl.addEventListener('change', loadCapacity);

    var refreshBtn = document.getElementById('refresh-capacity-btn');
    if (refreshBtn) {
      refreshBtn.addEventListener('click', loadCapacity);
    }

    if (ajaxBookBtn) {
      ajaxBookBtn.addEventListener('click', function () {
        var cid = classIdEl ? classIdEl.value : '';
        var bdate = dateEl ? dateEl.value : '';
        var notes = notesInput ? notesInput.value.trim() : '';

        ajaxBookBtn.disabled = true;
        if (feedbackBox) {
          feedbackBox.innerHTML = '<div class="alert alert-info py-2 small"><span class="ajax-spinner me-2"></span>Submitting booking...</div>';
        }

        fetch('../api/booking_action.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': getCsrfToken()
          },
          body: JSON.stringify({
            action: 'book_class',
            class_id: cid,
            booking_date: bdate,
            notes: notes
          })
        })
          .then(function (resp) { return resp.json(); })
          .then(function (data) {
            if (feedbackBox) {
              var cls = data.success ? 'alert-success' : 'alert-danger';
              feedbackBox.innerHTML = '<div class="alert ' + cls + ' py-2 small mb-0">' + escapeHtml(data.message) + '</div>';
            }
            loadCapacity();
            // If we are on bookings.php and booking succeeded, refresh table section smoothly
            if (data.success && document.getElementById('bookings-page-marker')) {
              setTimeout(function () { window.location.reload(); }, 900);
            }
          })
          .catch(function () {
            if (feedbackBox) {
              feedbackBox.innerHTML = '<div class="alert alert-danger py-2 small mb-0">Request failed. Please try again.</div>';
            }
            loadCapacity();
          });
      });
    }

    loadCapacity();
  }

  /* -------------------------------------------------------------------
   * LAB 3 TASK 2 & 3: AJAX Status Updates / Cancel on pages/bookings.php
   * ------------------------------------------------------------------- */
  function initBookingTableActions() {
    document.querySelectorAll('.ajax-cancel-booking-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var bookingId = btn.getAttribute('data-booking-id');
        if (!confirm('Cancel booking #' + bookingId + '? This frees up your spot immediately.')) {
          return;
        }
        btn.disabled = true;
        fetch('../api/booking_action.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': getCsrfToken()
          },
          body: JSON.stringify({
            action: 'cancel_booking',
            booking_id: bookingId
          })
        })
          .then(function (resp) { return resp.json(); })
          .then(function (data) {
            var toastArea = document.getElementById('bookings-ajax-toast');
            if (toastArea) {
              var cls = data.success ? 'alert-success' : 'alert-danger';
              toastArea.innerHTML = '<div class="alert ' + cls + ' py-2 small">' + escapeHtml(data.message) + '</div>';
            }
            if (data.success) {
              setTimeout(function () { window.location.reload(); }, 650);
            } else {
              btn.disabled = false;
            }
          })
          .catch(function () {
            btn.disabled = false;
          });
      });
    });

    document.querySelectorAll('.ajax-staff-status-select').forEach(function (sel) {
      sel.addEventListener('change', function () {
        var bookingId = sel.getAttribute('data-booking-id');
        var newStatus = sel.value;
        fetch('../api/booking_action.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': getCsrfToken()
          },
          body: JSON.stringify({
            action: 'update_status',
            booking_id: bookingId,
            status: newStatus
          })
        })
          .then(function (resp) { return resp.json(); })
          .then(function (data) {
            var toastArea = document.getElementById('bookings-ajax-toast');
            if (toastArea) {
              var cls = data.success ? 'alert-success' : 'alert-danger';
              toastArea.innerHTML = '<div class="alert ' + cls + ' py-2 small">' + escapeHtml(data.message) + '</div>';
            }
            if (data.success) {
              setTimeout(function () { window.location.reload(); }, 650);
            }
          });
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initAjaxClassSearch();
    initLiveCapacityCheckers();
    initBookingTableActions();
  });
})();
