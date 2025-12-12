const faqItems = document.querySelectorAll('.faq-item');

faqItems.forEach(item => {
  const btn = item.querySelector('.faq-question');
  btn.addEventListener('click', () => {
    const isOpen = item.classList.contains('open');
    if (isOpen) {
      item.classList.remove('open');
      btn.setAttribute('aria-expanded', 'false');
    } else {
      item.classList.add('open');
      btn.setAttribute('aria-expanded', 'true');
    }
  });
});
