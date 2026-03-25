/******/ (function() { // webpackBootstrap
/*!**************************************!*\
  !*** ./components/header/_header.js ***!
  \**************************************/
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
/**
 * @file
 * Auction Header functionality for SDC component.
 */

(function (Drupal, drupalSettings, once) {
  'use strict';

  /**
   * Auction Header behavior.
   */
  Drupal.behaviors.auctionHeader = {
    attach: function attach(context, settings) {
      var headers = once('auction-header', '.component--auction-header', context);
      headers.forEach(function (header) {
        new AuctionHeader(header);
      });
    }
  };

  /**
   * Auction Header Class.
   */
  var AuctionHeader = /*#__PURE__*/function () {
    function AuctionHeader(element) {
      _classCallCheck(this, AuctionHeader);
      this.header = element;
      this.mobileToggle = null;
      this.mobileMenu = null;
      this.logoSection = null;
      this.init();
    }

    /**
     * Initialize header functionality.
     */
    return _createClass(AuctionHeader, [{
      key: "init",
      value: function init() {
        this.mobileToggle = this.header.querySelector('.mobile-menu-toggle');
        this.mobileMenu = this.header.querySelector('.mobile-menu');
        this.logoSection = this.header.querySelector('.logo');
        this.setupMobileMenu();
        this.bindEvents();
        this.handleScroll();
      }

      /**
       * Setup mobile menu functionality.
       */
    }, {
      key: "setupMobileMenu",
      value: function setupMobileMenu() {
        if (!this.logoSection || !this.mobileMenu) return;

        // Find navigation in the logo section (branding block)
        var navContent = this.logoSection.querySelector('.nav, .navbar-nav');
        var mobileContent = this.mobileMenu.querySelector('.mobile-menu__content');
        if (mobileContent && navContent) {
          // Clone navigation for mobile
          var clonedNav = navContent.cloneNode(true);

          // Clear existing content
          mobileContent.innerHTML = '';

          // Create wrapper and add cloned navigation
          var wrapper = document.createElement('div');
          wrapper.className = 'mobile-nav-wrapper';
          wrapper.appendChild(clonedNav);
          mobileContent.appendChild(wrapper);
        }
      }

      /**
       * Bind event listeners.
       */
    }, {
      key: "bindEvents",
      value: function bindEvents() {
        var _this = this;
        // Mobile menu toggle
        if (this.mobileToggle && this.mobileMenu) {
          this.mobileToggle.addEventListener('click', this.toggleMobileMenu.bind(this));
        }

        // Close mobile menu on escape key
        document.addEventListener('keydown', function (event) {
          if (event.key === 'Escape') {
            _this.closeMobileMenu();
          }
        });

        // Close mobile menu on outside click
        document.addEventListener('click', function (event) {
          if (_this.mobileMenu && _this.mobileMenu.classList.contains('is-active') && !_this.header.contains(event.target)) {
            _this.closeMobileMenu();
          }
        });

        // Window resize handling
        window.addEventListener('resize', this.handleResize.bind(this));

        // Close mobile menu when clicking on menu links
        if (this.mobileMenu) {
          this.mobileMenu.addEventListener('click', function (event) {
            if (event.target.classList.contains('nav-link') || event.target.closest('.nav-link')) {
              _this.closeMobileMenu();
            }
          });
        }
      }

      /**
       * Toggle mobile menu.
       */
    }, {
      key: "toggleMobileMenu",
      value: function toggleMobileMenu() {
        if (!this.mobileMenu || !this.mobileToggle) return;
        var isActive = this.mobileMenu.classList.contains('is-active');
        if (isActive) {
          this.closeMobileMenu();
        } else {
          this.openMobileMenu();
        }
      }

      /**
       * Open mobile menu.
       */
    }, {
      key: "openMobileMenu",
      value: function openMobileMenu() {
        var _this2 = this;
        if (!this.mobileMenu || !this.mobileToggle) return;
        this.mobileMenu.classList.add('is-active');
        this.mobileMenu.setAttribute('aria-hidden', 'false');
        this.mobileToggle.setAttribute('aria-expanded', 'true');

        // Add class to body to prevent scrolling
        document.body.classList.add('mobile-menu-open');

        // Focus first menu item
        setTimeout(function () {
          var firstLink = _this2.mobileMenu.querySelector('.nav-link, a');
          if (firstLink) {
            firstLink.focus();
          }
        }, 100);
      }

      /**
       * Close mobile menu.
       */
    }, {
      key: "closeMobileMenu",
      value: function closeMobileMenu() {
        if (!this.mobileMenu || !this.mobileToggle) return;
        this.mobileMenu.classList.remove('is-active');
        this.mobileMenu.setAttribute('aria-hidden', 'true');
        this.mobileToggle.setAttribute('aria-expanded', 'false');

        // Remove class from body
        document.body.classList.remove('mobile-menu-open');
      }

      /**
       * Handle window resize.
       */
    }, {
      key: "handleResize",
      value: function handleResize() {
        // Close mobile menu on larger screens
        if (window.innerWidth >= 992) {
          this.closeMobileMenu();
        }

        // Re-setup mobile menu if needed
        this.setupMobileMenu();
      }

      /**
       * Handle scroll effects.
       */
    }, {
      key: "handleScroll",
      value: function handleScroll() {
        var _this3 = this;
        var ticking = false;
        var updateHeader = function updateHeader() {
          var scrollY = window.scrollY;
          if (scrollY > 50) {
            _this3.header.classList.add('is-scrolled');
          } else {
            _this3.header.classList.remove('is-scrolled');
          }
          ticking = false;
        };
        var requestTick = function requestTick() {
          if (!ticking) {
            requestAnimationFrame(updateHeader);
            ticking = true;
          }
        };
        window.addEventListener('scroll', requestTick);
      }
    }]);
  }();
})(Drupal, drupalSettings, once);
/******/ })()
;
//# sourceMappingURL=header.js.map