document.addEventListener("DOMContentLoaded", () => {
    const texts = document.querySelectorAll('.history-text');
    const slides = document.querySelectorAll('.history-slide');
    const total = texts.length;
    let currentIndex = 0;
  
    function showSlide(index) {
      slides.forEach(slide => slide.classList.remove('active'));
      slides[index].classList.add('active');
    }
  
    function updateTexts(centerIndex) {
      texts.forEach((text, i) => {
        text.classList.remove('above', 'center', 'below', 'hidden');
  
        const prevIndex = (centerIndex - 1 + total) % total;
        const nextIndex = (centerIndex + 1) % total;
  
        if (i === centerIndex) {
          text.classList.add('center');
        } else if (i === prevIndex) {
          text.classList.add('above');
        } else if (i === nextIndex) {
          text.classList.add('below');
        } else {
          text.classList.add('hidden');
        }
      });
    }
  
    // Initial display
    updateTexts(currentIndex);
    showSlide(currentIndex);
  
    texts.forEach((text, i) => {
      text.addEventListener('click', () => {
        currentIndex = i;
        updateTexts(currentIndex);
        showSlide(currentIndex);
      });
  
      // Add click-and-drag scrolling for paragraph
      const paragraph = text.querySelector('.history-paragraph');
      if (paragraph) {
        let isDown = false;
        let startY;
        let scrollTop;
  
        paragraph.addEventListener('mousedown', (e) => {
          isDown = true;
          startY = e.pageY - paragraph.offsetTop;
          scrollTop = paragraph.scrollTop;
          paragraph.style.cursor = 'grabbing';
        });
  
        paragraph.addEventListener('mouseleave', () => { isDown = false; paragraph.style.cursor = 'grab'; });
        paragraph.addEventListener('mouseup', () => { isDown = false; paragraph.style.cursor = 'grab'; });
  
        paragraph.addEventListener('mousemove', (e) => {
          if (!isDown) return;
          e.preventDefault();
          const y = e.pageY - paragraph.offsetTop;
          const walk = (y - startY) * 1.5; // scroll speed
          paragraph.scrollTop = scrollTop - walk;
        });
      }
    });
  });
    