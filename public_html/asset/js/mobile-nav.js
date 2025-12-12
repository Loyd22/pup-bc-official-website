// Mobile Navigation - Hamburger Menu and Accordion
(function() {
  'use strict';
  
  // Disable search button when input is empty (for mobile search forms)
  function initMobileSearchButtonState() {
    const mobileForms = document.querySelectorAll('.mobile-nav-search-form');
    mobileForms.forEach(form => {
      const input = form.querySelector('input[name="q"], input[type="text"]');
      const button = form.querySelector('button[type="submit"]');
      
      if (!input || !button) return;
      
      function updateButtonState() {
        const value = (input.value || '').trim();
        if (value === '') {
          button.disabled = true;
          button.style.opacity = '0.5';
          button.style.cursor = 'not-allowed';
        } else {
          button.disabled = false;
          button.style.opacity = '1';
          button.style.cursor = 'pointer';
        }
      }
      
      input.addEventListener('input', updateButtonState);
      input.addEventListener('keyup', updateButtonState);
      
      form.addEventListener('submit', function(e) {
        const value = (input.value || '').trim();
        if (value === '') {
          e.preventDefault();
          return false;
        }
      });
      
      updateButtonState();
    });
  }
  
  // Initialize mobile navigation
  function initMobileNav() {
    const hamburger = document.getElementById('mobile-menu-toggle');
    const mobilePanel = document.getElementById('mobile-nav-panel');
    const body = document.body;
    
    if (!hamburger || !mobilePanel) return;
    
    // Toggle mobile menu
    hamburger.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const isOpen = mobilePanel.classList.contains('open');
      
      if (isOpen) {
        closeMobileMenu();
      } else {
        openMobileMenu();
      }
    });
    
    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
      if (mobilePanel.classList.contains('open')) {
        if (!mobilePanel.contains(e.target) && !hamburger.contains(e.target)) {
          closeMobileMenu();
        }
      }
    });
    
    // Close menu on escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && mobilePanel.classList.contains('open')) {
        closeMobileMenu();
        hamburger.focus();
      }
    });
    
    function openMobileMenu() {
      mobilePanel.classList.add('open');
      hamburger.classList.add('active');
      hamburger.setAttribute('aria-expanded', 'true');
      mobilePanel.setAttribute('aria-hidden', 'false');
      body.style.overflow = 'hidden'; // Prevent body scroll
    }
    
    function closeMobileMenu() {
      mobilePanel.classList.remove('open');
      hamburger.classList.remove('active');
      hamburger.setAttribute('aria-expanded', 'false');
      mobilePanel.setAttribute('aria-hidden', 'true');
      body.style.overflow = ''; // Restore body scroll
      
      // Close all accordions
      const accordions = mobilePanel.querySelectorAll('.mobile-nav-accordion.open');
      accordions.forEach(acc => {
        acc.classList.remove('open');
        const content = acc.querySelector('.mobile-nav-dropdown');
        if (content) {
          content.style.maxHeight = null;
        }
      });
    }
    
    // Accordion functionality for dropdowns
    const accordionTriggers = mobilePanel.querySelectorAll('.mobile-nav-accordion-trigger');
    accordionTriggers.forEach(trigger => {
      trigger.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const accordion = this.closest('.mobile-nav-accordion');
        const dropdown = accordion.querySelector('.mobile-nav-dropdown');
        
        if (!accordion || !dropdown) return;
        
        const isOpen = accordion.classList.contains('open');
        
        // Close all other accordions
        accordionTriggers.forEach(otherTrigger => {
          const otherAccordion = otherTrigger.closest('.mobile-nav-accordion');
          if (otherAccordion && otherAccordion !== accordion) {
            otherAccordion.classList.remove('open');
            const otherDropdown = otherAccordion.querySelector('.mobile-nav-dropdown');
            if (otherDropdown) {
              otherDropdown.style.maxHeight = null;
            }
          }
        });
        
        // Toggle current accordion
        if (isOpen) {
          accordion.classList.remove('open');
          dropdown.style.maxHeight = null;
        } else {
          accordion.classList.add('open');
          dropdown.style.maxHeight = dropdown.scrollHeight + 'px';
        }
      });
    });
    
    // Close menu when clicking a link
    const mobileLinks = mobilePanel.querySelectorAll('a');
    mobileLinks.forEach(link => {
      link.addEventListener('click', function() {
        // Don't close if it's a dropdown trigger
        if (!this.classList.contains('mobile-nav-accordion-trigger')) {
          closeMobileMenu();
        }
      });
    });
  }
  
  // Initialize on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      initMobileNav();
      initMobileSearchButtonState();
    });
  } else {
    initMobileNav();
    initMobileSearchButtonState();
  }
})();

