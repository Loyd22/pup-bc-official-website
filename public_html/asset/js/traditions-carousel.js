/**
 * University Traditions Carousel Functionality
 * Handles horizontal scrolling for tradition carousels
 */

(function() {
  'use strict';

  document.addEventListener('DOMContentLoaded', function() {
    const carousels = document.querySelectorAll('.tradition-carousel-track');
    
    if (carousels.length === 0) {
      return;
    }

    carousels.forEach(function(carousel) {
      // Arrow controls removed - carousel displays all images without navigation
      // No click handlers needed
    });
  });
})();

