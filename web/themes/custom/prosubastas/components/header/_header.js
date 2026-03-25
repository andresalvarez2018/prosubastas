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
    attach: function (context, settings) {
      const headers = once('auction-header', '.component--auction-header', context);
      
      headers.forEach((header) => {
        new AuctionHeader(header);
      });
    }
  };

  /**
   * Auction Header Class.
   */
  class AuctionHeader {
    constructor(element) {
      this.header = element;
      this.mobileToggle = null;
      this.mobileMenu = null;
      this.logoSection = null;
      
      this.init();
    }

    /**
     * Initialize header functionality.
     */
    init() {
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
    setupMobileMenu() {
      if (!this.logoSection || !this.mobileMenu) return;

      // Find navigation in the logo section (branding block)
      const navContent = this.logoSection.querySelector('.nav, .navbar-nav');
      const mobileContent = this.mobileMenu.querySelector('.mobile-menu__content');
      
      if (mobileContent && navContent) {
        // Clone navigation for mobile
        const clonedNav = navContent.cloneNode(true);
        
        // Clear existing content
        mobileContent.innerHTML = '';
        
        // Create wrapper and add cloned navigation
        const wrapper = document.createElement('div');
        wrapper.className = 'mobile-nav-wrapper';
        wrapper.appendChild(clonedNav);
        mobileContent.appendChild(wrapper);
      }
    }

    /**
     * Bind event listeners.
     */
    bindEvents() {
      // Mobile menu toggle
      if (this.mobileToggle && this.mobileMenu) {
        this.mobileToggle.addEventListener('click', this.toggleMobileMenu.bind(this));
      }

      // Close mobile menu on escape key
      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
          this.closeMobileMenu();
        }
      });

      // Close mobile menu on outside click
      document.addEventListener('click', (event) => {
        if (this.mobileMenu && 
            this.mobileMenu.classList.contains('is-active') &&
            !this.header.contains(event.target)) {
          this.closeMobileMenu();
        }
      });

      // Window resize handling
      window.addEventListener('resize', this.handleResize.bind(this));

      // Close mobile menu when clicking on menu links
      if (this.mobileMenu) {
        this.mobileMenu.addEventListener('click', (event) => {
          if (event.target.classList.contains('nav-link') || 
              event.target.closest('.nav-link')) {
            this.closeMobileMenu();
          }
        });
      }
    }

    /**
     * Toggle mobile menu.
     */
    toggleMobileMenu() {
      if (!this.mobileMenu || !this.mobileToggle) return;

      const isActive = this.mobileMenu.classList.contains('is-active');
      
      if (isActive) {
        this.closeMobileMenu();
      } else {
        this.openMobileMenu();
      }
    }

    /**
     * Open mobile menu.
     */
    openMobileMenu() {
      if (!this.mobileMenu || !this.mobileToggle) return;

      this.mobileMenu.classList.add('is-active');
      this.mobileMenu.setAttribute('aria-hidden', 'false');
      this.mobileToggle.setAttribute('aria-expanded', 'true');
      
      // Add class to body to prevent scrolling
      document.body.classList.add('mobile-menu-open');
      
      // Focus first menu item
      setTimeout(() => {
        const firstLink = this.mobileMenu.querySelector('.nav-link, a');
        if (firstLink) {
          firstLink.focus();
        }
      }, 100);
    }

    /**
     * Close mobile menu.
     */
    closeMobileMenu() {
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
    handleResize() {
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
    handleScroll() {
      let ticking = false;

      const updateHeader = () => {
        const scrollY = window.scrollY;
        
        if (scrollY > 50) {
          this.header.classList.add('is-scrolled');
        } else {
          this.header.classList.remove('is-scrolled');
        }
        
        ticking = false;
      };

      const requestTick = () => {
        if (!ticking) {
          requestAnimationFrame(updateHeader);
          ticking = true;
        }
      };

      window.addEventListener('scroll', requestTick);
    }
  }

})(Drupal, drupalSettings, once);