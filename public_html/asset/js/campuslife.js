/**
 * Campus Life Carousel Functionality
 * Handles carousel navigation, indicators, and auto-play
 */

(function() {
  'use strict';

  // Wait for DOM to be ready
  document.addEventListener('DOMContentLoaded', function() {
    const carouselTrack = document.getElementById('carouselTrack');
    const prevButton = document.getElementById('carouselPrev');
    const nextButton = document.getElementById('carouselNext');
    const indicators = document.querySelectorAll('.indicator');
    const panels = document.querySelectorAll('.carousel-panel');

    if (!carouselTrack || panels.length === 0) {
      return; // Exit if required elements are not found
    }

    let currentIndex = 0;
    let isTransitioning = false;
    let autoPlayInterval = null;
    const autoPlayDelay = 5000; // 5 seconds

    /**
     * Update carousel position
     */
    function updateCarousel() {
      if (isTransitioning) return;
      
      isTransitioning = true;
      const translateX = -currentIndex * 100;
      carouselTrack.style.transform = `translateX(${translateX}%)`;
      
      // Update indicators
      indicators.forEach((indicator, index) => {
        if (index === currentIndex) {
          indicator.classList.add('active');
          indicator.setAttribute('aria-current', 'true');
        } else {
          indicator.classList.remove('active');
          indicator.removeAttribute('aria-current');
        }
      });

      // Update button states (if buttons exist)
      if (prevButton) prevButton.disabled = currentIndex === 0;
      if (nextButton) nextButton.disabled = currentIndex === panels.length - 1;

      // Reset transition flag after animation completes
      setTimeout(() => {
        isTransitioning = false;
      }, 600);
    }

    /**
     * Go to next panel
     */
    function nextPanel() {
      if (currentIndex < panels.length - 1) {
        currentIndex++;
        updateCarousel();
        resetAutoPlay();
      }
    }

    /**
     * Go to previous panel
     */
    function prevPanel() {
      if (currentIndex > 0) {
        currentIndex--;
        updateCarousel();
        resetAutoPlay();
      }
    }

    /**
     * Go to specific panel by index
     */
    function goToPanel(index) {
      if (index >= 0 && index < panels.length && index !== currentIndex) {
        currentIndex = index;
        updateCarousel();
        resetAutoPlay();
      }
    }

    /**
     * Start auto-play
     */
    function startAutoPlay() {
      autoPlayInterval = setInterval(() => {
        if (currentIndex < panels.length - 1) {
          nextPanel();
        } else {
          // Loop back to first panel
          currentIndex = 0;
          updateCarousel();
        }
      }, autoPlayDelay);
    }

    /**
     * Stop auto-play
     */
    function stopAutoPlay() {
      if (autoPlayInterval) {
        clearInterval(autoPlayInterval);
        autoPlayInterval = null;
      }
    }

    /**
     * Reset auto-play timer
     */
    function resetAutoPlay() {
      stopAutoPlay();
      startAutoPlay();
    }

    // Event listeners (if buttons exist)
    if (prevButton) prevButton.addEventListener('click', prevPanel);
    if (nextButton) nextButton.addEventListener('click', nextPanel);

    // Indicator click handlers
    indicators.forEach((indicator, index) => {
      indicator.addEventListener('click', () => goToPanel(index));
    });

    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
      const carouselSection = document.getElementById('campus-life') || document.getElementById('campus-services');
      if (!carouselSection) return;

      // Check if carousel is in viewport or focused
      const rect = carouselSection.getBoundingClientRect();
      const isVisible = rect.top < window.innerHeight && rect.bottom > 0;

      if (!isVisible) return;

      if (e.key === 'ArrowLeft') {
        e.preventDefault();
        prevPanel();
      } else if (e.key === 'ArrowRight') {
        e.preventDefault();
        nextPanel();
      }
    });

    // Touch/swipe support for mobile
    let touchStartX = 0;
    let touchEndX = 0;

    carouselTrack.addEventListener('touchstart', function(e) {
      touchStartX = e.changedTouches[0].screenX;
      stopAutoPlay();
    }, { passive: true });

    carouselTrack.addEventListener('touchend', function(e) {
      touchEndX = e.changedTouches[0].screenX;
      handleSwipe();
      startAutoPlay();
    }, { passive: true });

    function handleSwipe() {
      const swipeThreshold = 50;
      const diff = touchStartX - touchEndX;

      if (Math.abs(diff) > swipeThreshold) {
        if (diff > 0) {
          // Swipe left - next panel
          nextPanel();
        } else {
          // Swipe right - previous panel
          prevPanel();
        }
      }
    }

    // Pause auto-play on hover
    const carouselContainer = carouselTrack.closest('.carousel-container');
    if (carouselContainer) {
      carouselContainer.addEventListener('mouseenter', stopAutoPlay);
      carouselContainer.addEventListener('mouseleave', startAutoPlay);
    }

    // Initialize
    updateCarousel();
    startAutoPlay();

    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
      stopAutoPlay();
    });
  });
})();

