// Loading bar + page transition helpers
const Progress = (() => {
  let el, bar, timer, value = 0, visible = false;
  function ensure(){
    if (el) return;
    el = document.createElement('div');
    el.className = 'progress';
    bar = document.createElement('div');
    bar.className = 'bar';
    el.appendChild(bar);
    document.addEventListener('DOMContentLoaded', () => document.body.appendChild(el));
    // If body already available (defer scripts), append immediately
    if (document.body) document.body.appendChild(el);
  }
  function set(n){
    ensure();
    value = Math.max(0, Math.min(100, n));
    bar.style.width = value + '%';
    if (!visible){ el.classList.add('show'); visible = true; }
  }
  function start(){
    clearInterval(timer);
    set(2);
    timer = setInterval(() => {
      const next = value + Math.random()*5 + 3; // trickle
      set(Math.min(90, next));
      if (value >= 90) { clearInterval(timer); }
    }, 300);
  }
  function done(){
    clearInterval(timer);
    set(100);
    setTimeout(() => { if (el){ el.classList.remove('show'); bar.style.width = '0%'; visible=false; value=0; } }, 300);
  }
  return { start, set, done };
})();

// Start progress early on load
try { Progress.start(); } catch {}

// Page transition helpers (fade in/out)
const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
function navigateWithTransition(url){
  if (!url) return;
  if (prefersReducedMotion) { window.location.href = url; return; }
  try { Progress.start(); } catch {}
  document.body.classList.add('page-exit');
  setTimeout(() => { window.location.href = url; }, 220);
}

document.addEventListener('DOMContentLoaded', () => {
  if (!prefersReducedMotion) {
    requestAnimationFrame(() => document.body.classList.add('page-loaded'));
  }
  try { Progress.done(); } catch {}
});

// Intercept internal link clicks for smooth exit
document.addEventListener('click', (e) => {
  if (prefersReducedMotion) return;
  const a = e.target.closest && e.target.closest('a');
  if (!a) return;
  if (a.target === '_blank' || a.hasAttribute('download')) return;
  const href = a.getAttribute('href');
  if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:') || href.startsWith('javascript:')) return;
  const dest = new URL(a.href, window.location.href);
  if (dest.origin !== window.location.origin) return; // external link
  e.preventDefault();
  navigateWithTransition(dest.href);
});

// Mobile menu toggle
const hamburger = document.getElementById('hamburger');
const mobilePanel = document.getElementById('mobilePanel');
hamburger?.addEventListener('click', () => {
  const open = mobilePanel.classList.toggle('open');
  hamburger.setAttribute('aria-expanded', String(open));
  mobilePanel.setAttribute('aria-hidden', String(!open));
});

// Header logo pop-in on downward scroll
const header = document.querySelector('header');
let lastY = 0;
const logoThreshold = 50;
window.addEventListener('scroll', () => {
  const y = window.scrollY || document.documentElement.scrollTop;

  if (header) {
    const down = y > lastY;
    if (down && y > logoThreshold) {
      header.classList.add('scrolled'); // show nav logo with pop-in
    } else if (!down || y <= logoThreshold) {
      header.classList.remove('scrolled'); // hide when scrolling up or near top
    }
  }
  lastY = y;
});

// Current year in footer
const yearEl = document.getElementById('year');
if (yearEl) yearEl.textContent = new Date().getFullYear();

// Keep header unchanged on scroll (no 'scrolled' class toggling)

// Disable search button when input is empty
(function() {
  function initSearchButtonState() {
    const forms = document.querySelectorAll('.search-form, .mobile-nav-search-form');
    forms.forEach(form => {
      const input = form.querySelector('input[name="q"], input[type="text"]');
      const button = form.querySelector('button[type="submit"]');
      
      if (!input || !button) return;
      
      // Set initial state
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
      
      // Update on input
      input.addEventListener('input', updateButtonState);
      input.addEventListener('keyup', updateButtonState);
      
      // Prevent form submission if empty
      form.addEventListener('submit', function(e) {
        const value = (input.value || '').trim();
        if (value === '') {
          e.preventDefault();
          return false;
        }
      });
      
      // Set initial state
      updateButtonState();
    });
  }
  
  // Initialize on page load
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSearchButtonState);
  } else {
    initSearchButtonState();
  }
})();

(() => {
  const form = document.querySelector('.search-form');
  if (!form) return;
  const input = form.querySelector('input[name="q"]');
  if (!input) return;

  // Page routes with keywords for fuzzy matching
  const routes = [
    { label: 'Home', url: 'homepage.php', keywords: ['home','homepage','main'] },
    { label: 'About', url: 'pages/about.php', keywords: ['about','history','mission','vision','university'] },
    { label: 'Academic Programs', url: 'pages/programs.php', keywords: ['program','programs','course','courses','curriculum','degree','bscs','bsit'] },
    { label: 'Admissions', url: 'pages/admission_guide.php', keywords: ['admission','admissions','enroll','enrollment','requirements','apply','application'] },
    { label: 'Student Services', url: 'pages/services.php', keywords: ['service','services','scholarship','guidance','library','clinic','student services'] },
    { label: 'Campus Life', url: 'pages/campuslife.php', keywords: ['campus','life','event','events','calendar','schedule'] },
    { label: 'Contact', url: 'pages/contact.php', keywords: ['contact','email','phone','address','location'] },
  ];

  // Suggestion dropdown container
  const box = document.createElement('div');
  box.className = 'search-suggest';
  box.setAttribute('role', 'listbox');
  box.setAttribute('aria-label', 'Search suggestions');
  form.appendChild(box);

  let open = false;
  let activeIndex = -1;
  let current = [];
  let onPageCache = null; // cache of on-page candidates
  let lastQuery = '';

  // --- Fuzzy matching helpers (typo tolerant) ---
  const norm = (s) => (s || '').toLowerCase().trim();

  // Subsequence match: allows missing letters but in order. Returns positions if full subsequence matches.
  function subseqPositions(query, text) {
    const q = norm(query), t = norm(text);
    let ti = 0; const pos = [];
    for (let qi = 0; qi < q.length; qi++) {
      const ch = q[qi];
      let found = false;
      while (ti < t.length) {
        if (t[ti] === ch) { pos.push(ti); ti++; found = true; break; }
        ti++;
      }
      if (!found) return null;
    }
    return pos;
  }

  // Limited Levenshtein: early-exit if distance > maxDist
  function levenshteinWithin(a, b, maxDist = 2) {
    a = norm(a); b = norm(b);
    if (Math.abs(a.length - b.length) > maxDist) return maxDist + 1;
    const prev = new Array(b.length + 1).fill(0);
    for (let j = 0; j <= b.length; j++) prev[j] = j;
    for (let i = 1; i <= a.length; i++) {
      let minInRow = Infinity;
      let curr = new Array(b.length + 1);
      curr[0] = i;
      for (let j = 1; j <= b.length; j++) {
        const cost = a[i - 1] === b[j - 1] ? 0 : 1;
        curr[j] = Math.min(
          prev[j] + 1,
          curr[j - 1] + 1,
          prev[j - 1] + cost
        );
        if (curr[j] < minInRow) minInRow = curr[j];
      }
      if (minInRow > maxDist) return maxDist + 1; // early exit
      for (let j = 0; j <= b.length; j++) prev[j] = curr[j];
    }
    return prev[b.length];
  }

  // Score a candidate against query; returns {score, positions}
  function scoreCandidate(query, label, keywords) {
    const q = norm(query);
    const l = norm(label);
    if (!q) return { score: 0, positions: [] };

    // Exact/prefix/substring bonuses
    if (l === q) return { score: 1.0, positions: [...Array(q.length).keys()] };
    if (l.startsWith(q)) return { score: 0.9, positions: Array.from({length:q.length}, (_,i)=>i) };
    const idx = l.indexOf(q);
    if (idx !== -1) return { score: 0.8, positions: Array.from({length:q.length}, (_,i)=>idx+i) };

    // Subsequence (letters in order, possibly missing)
    const pos = subseqPositions(q, l);
    if (pos) {
      const span = pos[pos.length - 1] - pos[0] + 1;
      const density = pos.length / Math.max(1, span);
      const score = 0.55 + Math.min(0.35, density * 0.35); // up to ~0.9
      return { score, positions: pos };
    }

    // Edit distance against label and keywords
    let best = Infinity;
    best = Math.min(best, levenshteinWithin(q, l, 2));
    for (const k of (keywords || [])) best = Math.min(best, levenshteinWithin(q, k, 2));
    if (best <= 2) {
      // Map distance 0..2 to score 0.85..0.65
      return { score: 0.85 - best * 0.1, positions: [] };
    }

    // No good match
    return { score: 0, positions: [] };
  }

  function highlightLabel(label, positions) {
    if (!positions || !positions.length) return escapeHtml(label);
    const chars = label.split("");
    const set = new Set(positions);
    let out = '';
    let open = false;
    for (let i = 0; i < chars.length; i++) {
      const is = set.has(i);
      if (is && !open) { out += '<mark>'; open = true; }
      if (!is && open) { out += '</mark>'; open = false; }
      out += escapeHtml(chars[i]);
    }
    if (open) out += '</mark>';
    return out;
  }

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function buildOnPageCandidates() {
    if (onPageCache) return onPageCache;
    const out = [];
    // IDs
    document.querySelectorAll('[id]').forEach(el => {
      const id = el.id?.trim();
      if (id && id.length > 1) out.push({ type: 'anchor', label: id.replace(/[-_]/g, ' '), el });
    });
    // Headings text
    document.querySelectorAll('h1,h2,h3,h4,h5,h6').forEach(h => {
      const txt = h.textContent?.trim();
      if (txt) out.push({ type: 'anchor', label: txt, el: h });
    });
    onPageCache = out;
    return out;
  }

  function render(list) {
    current = list;
    activeIndex = list.length ? 0 : -1;
    if (!list.length) {
      hide();
      return;
    }
    const html = list.map((s, i) => `
      <div class="item${i===activeIndex?' active':''}" role="option" aria-selected="${i===activeIndex}">
        <span class="label">${highlightLabel(s.label, s.positions)}</span>
        <span class="badge">${s.type === 'page' ? 'Page' : 'On this page'}</span>
      </div>
    `).join('');
    box.innerHTML = html;
    box.classList.add('open');
    open = true;
  }

  function hide() {
    box.classList.remove('open');
    box.innerHTML = '';
    open = false;
    activeIndex = -1;
  }

  function choose(index) {
    const sel = current[index];
    if (!sel) return;
    if (sel.type === 'page') {
      navigateWithTransition(sel.url);
    } else if (sel.type === 'anchor' && sel.el) {
      sel.el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } else if (sel.type === 'site') {
      const q = encodeURIComponent(sel.query || lastQuery || input.value || '');
      if (q) {
        const inPages = location.pathname.includes('/pages/');
        const searchPath = inPages ? 'search.php' : 'pages/search.php';
        navigateWithTransition(`${searchPath}?q=${q}`);
      }
    }
    hide();
  }

  function computeSuggestions(q) {
    const query = norm(q);
    lastQuery = query;
    const results = [];

    // Pages
    for (const r of routes) {
      const { score, positions } = scoreCandidate(query, r.label, r.keywords);
      if (score > 0.55) results.push({ type: 'page', label: r.label, url: r.url, score, positions });
    }

    // On page
    const anchors = buildOnPageCandidates();
    for (const a of anchors) {
      const { score, positions } = scoreCandidate(query, a.label);
      if (score > 0.55) results.push({ ...a, score, positions });
    }

    // If nothing matches and query is not empty, relax threshold a bit
    if (!results.length && query) {
      for (const r of routes) {
        const { score, positions } = scoreCandidate(query, r.label, r.keywords);
        if (score > 0.4) results.push({ type: 'page', label: r.label, url: r.url, score, positions });
      }
    }

    results.sort((a, b) => (b.score - a.score) || ((a.type === 'page') ? -1 : 1));
    const top = results.slice(0, 7);
    // Always provide a fallback site-search option when user has typed something
    if (query) {
      top.push({ type: 'site', label: `Search site for "${q}"`, query, positions: [] });
    }
    return top;
  }

  // Mouse selection (use mousedown to run before blur)
  box.addEventListener('mousedown', (e) => {
    const item = e.target.closest('.item');
    if (!item) return;
    const idx = Array.from(box.children).indexOf(item);
    e.preventDefault();
    choose(idx);
  });

  input.addEventListener('input', () => {
    const list = computeSuggestions(input.value);
    render(list);
  });

  input.addEventListener('keydown', (e) => {
    if (!open) return;
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      activeIndex = (activeIndex + 1) % current.length;
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      activeIndex = (activeIndex - 1 + current.length) % current.length;
    } else if (e.key === 'Enter') {
      e.preventDefault();
      choose(activeIndex);
      return;
    } else if (e.key === 'Escape') {
      hide();
      return;
    } else {
      return; // other keys handled by input event
    }
    // re-render active state only
    Array.from(box.children).forEach((el, i) => {
      el.classList.toggle('active', i === activeIndex);
      el.setAttribute('aria-selected', String(i === activeIndex));
    });
  });

  input.addEventListener('focus', () => {
    const list = computeSuggestions(input.value);
    render(list);
  });

  input.addEventListener('blur', () => {
    // Delay hiding to allow click via mousedown
    setTimeout(hide, 100);
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    // If suggestions are open and a selection exists, choose it
    if (open && activeIndex >= 0) {
      choose(activeIndex);
      return;
    }
    const raw = (this.q?.value || '').trim();
    if (!raw) return;
    const query = norm(raw);

    // Use best suggestion if available
    const best = computeSuggestions(query)[0];
    if (best) {
      if (best.type === 'page') navigateWithTransition(best.url);
      else if (best.type === 'anchor' && best.el) best.el.scrollIntoView({ behavior: 'smooth', block: 'start' });
      hide();
      return;
    }

    alert('No results found for: ' + raw);
  });
})();

// Fix common encoding issues for "Biñan" across pages
document.addEventListener('DOMContentLoaded', () => {
  try {
    const replaceTo = 'Biñan';
    const patterns = [
      /Bi\uFFFDan/gi, // Bi�an shown due to replacement char
      /Bi\?an/gi,     // Bi?an variant
      /\bBinan\b/gi   // Unaccented Binan (but avoid emails separately)
    ];

    const shouldSkip = (text) => text.includes('@') || text.includes('mailto:');

    const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, null);
    const nodes = [];
    while (walker.nextNode()) nodes.push(walker.currentNode);
    nodes.forEach(node => {
      let t = node.nodeValue;
      if (!t || shouldSkip(t)) return;
      patterns.forEach(p => { t = t.replace(p, replaceTo); });
      if (t !== node.nodeValue) node.nodeValue = t;
    });

    // Also fix document.title
    if (document.title) {
      let t = document.title;
      patterns.forEach(p => { t = t.replace(p, replaceTo); });
      document.title = t;
    }
    // And meta description
    const meta = document.querySelector('meta[name="description"]');
    if (meta && meta.content) {
      let c = meta.content;
      if (!shouldSkip(c)) {
        patterns.forEach(p => { c = c.replace(p, replaceTo); });
        meta.content = c;
      }
    }
  } catch { /* no-op */ }
});

// Example: dynamically set calendar timezone if needed
// const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
// const cal = document.getElementById('calendarFrame');
// if (cal && !cal.src.includes('ctz=')) cal.src += (cal.src.includes('?')?'&':'?') + 'ctz=' + encodeURIComponent(tz);

// Headless WordPress: News & Announcements
document.addEventListener('DOMContentLoaded', () => {
  const base = (document.body?.dataset?.wpBase || '').trim().replace(/\/?$/, '');
  if (!base) return;

  const bySlug = async (slug) => {
    const u = `${base}/categories?slug=${encodeURIComponent(slug)}`;
    const r = await fetch(u, { headers: { 'Accept': 'application/json' } });
    if (!r.ok) throw new Error('Category lookup failed');
    const arr = await r.json();
    return (arr && arr[0] && arr[0].id) ? arr[0].id : null;
  };

  const stripHtml = (s) => (s || '').replace(/<[^>]*>/g, '').replace(/&[^;]+;/g, ' ').trim();
  const fmtDate = (iso) => new Date(iso).toLocaleDateString(undefined, { year:'numeric', month:'short', day:'numeric' });

  async function fetchPosts(opts){
    const { catSlug, count = 3 } = opts;
    const catId = await bySlug(catSlug);
    const q = new URLSearchParams({ per_page: String(count), _embed: '1', orderby: 'date', order: 'desc' });
    if (catId) q.set('categories', String(catId));
    const u = `${base}/posts?${q.toString()}`;
    const r = await fetch(u, { headers: { 'Accept': 'application/json' } });
    if (!r.ok) throw new Error('Posts fetch failed');
    return r.json();
  }

  function featured(post){
    const m = post?._embedded?.['wp:featuredmedia'];
    return (m && m[0] && (m[0].source_url || m[0].media_details?.sizes?.medium?.source_url)) || '';
  }

  function renderNews(list, posts){
    list.innerHTML = '';
    posts.forEach(p => {
      const item = document.createElement('div');
      item.className = 'news';
      const img = featured(p);
      item.innerHTML = `
        <div class="img">${img ? `<img src="${img}" alt="">` : ''}</div>
        <div class="txt">
          <h4>${stripHtml(p.title?.rendered || 'Untitled')}</h4>
          <p>${stripHtml(p.excerpt?.rendered || '')}</p>
        </div>`;
      list.appendChild(item);
    });
  }

  function renderAnnos(list, posts){
    list.innerHTML = '';
    posts.forEach(p => {
      const row = document.createElement('div');
      row.className = 'anno';
      const t = stripHtml(p.title?.rendered || 'Untitled');
      const d = fmtDate(p.date);
      const link = p.link || '#';
      row.innerHTML = `
        <strong>${t}</strong>
        <small>Published: ${d}</small>
        <a href="${link}" target="_blank" rel="noopener">Read details</a>`;
      list.appendChild(row);
    });
  }

  (async () => {
    const newsSec = document.getElementById('news');
    const annSec = document.getElementById('announcements');
    try {
      if (newsSec) {
        const grid = newsSec.querySelector('.news-grid');
        const cat = newsSec.dataset.wpCat || 'news';
        const count = parseInt(newsSec.dataset.wpCount || '3', 10);
        if (grid) {
          grid.innerHTML = '<div class="news"><div class="txt"><h4>Loading news…</h4></div></div>';
          const posts = await fetchPosts({ catSlug: cat, count });
          renderNews(grid, posts);
        }
      }
      if (annSec) {
        const list = annSec.querySelector('.annos');
        const cat = annSec.dataset.wpCat || 'announcements';
        const count = parseInt(annSec.dataset.wpCount || '3', 10);
        if (list) {
          list.innerHTML = '<div class="anno"><strong>Loading…</strong></div>';
          const posts = await fetchPosts({ catSlug: cat, count });
          renderAnnos(list, posts);
        }
      }
    } catch (e) {
      console.error('WP integration error:', e);
    }
  })();
});

// Calendar AJAX navigation - update full calendar without page refresh
document.addEventListener('DOMContentLoaded', () => {
  const fullCalendarContainer = document.getElementById('full-calendar-container');
  const fullCalendarHeading = document.getElementById('fullCalendarHeading');
  const fullCalendarSection = document.getElementById('fullCalendarSection');
  
  if (!fullCalendarContainer || !fullCalendarSection) return;
  
  let isLoading = false;
  
  function updateFullCalendar(year, month) {
    if (isLoading) return;
    
    isLoading = true;
    
    // Disable buttons during loading - only in full calendar section
    const allNavBtns = fullCalendarSection.querySelectorAll('button.calendar-nav-btn[data-year]');
    allNavBtns.forEach(btn => {
      if (btn.tagName === 'BUTTON') {
        btn.disabled = true;
        btn.style.opacity = '0.6';
        btn.style.cursor = 'not-allowed';
      }
    });
    
    // Show loading state
    const calendarGrid = fullCalendarContainer.querySelector('.calendar-grid');
    if (calendarGrid) {
      calendarGrid.style.opacity = '0.5';
    }
    
    // Fetch calendar data via AJAX
    fetch(`api/calendar.php?year=${year}&month=${month}`)
      .then(response => {
        if (!response.ok) {
          throw new Error('Failed to fetch calendar');
        }
        return response.json();
      })
      .then(data => {
        if (data.success) {
          // Update calendar HTML
          fullCalendarContainer.innerHTML = data.calendarHtml;
          
          // Update month heading
          if (fullCalendarHeading) {
            fullCalendarHeading.textContent = data.monthName;
          }
          
          // Update navigation buttons in full calendar section
          updateFullCalendarNavigationButtons(data);
          
          // Update data attributes
          fullCalendarContainer.setAttribute('data-current-year', data.year);
          fullCalendarContainer.setAttribute('data-current-month', data.month);
          
          // Re-apply current filter after calendar update
          const legendContainer = fullCalendarSection.querySelector('.calendar-legend');
          if (legendContainer) {
            const activeItem = legendContainer.querySelector('.legend-filter.active');
            if (activeItem) {
              const filterCategory = activeItem.getAttribute('data-category');
              filterCalendarEvents(fullCalendarContainer, filterCategory);
            }
          }
        } else {
          throw new Error(data.error || 'Failed to load calendar');
        }
      })
      .catch(error => {
        console.error('Calendar update error:', error);
        alert('Failed to load calendar. Please refresh the page.');
      })
      .finally(() => {
        isLoading = false;
        
        // Re-enable buttons
        allNavBtns.forEach(btn => {
          if (btn.tagName === 'BUTTON') {
            btn.disabled = false;
            btn.style.opacity = '';
            btn.style.cursor = '';
          }
        });
        
        // Restore opacity
        const calendarGrid = fullCalendarContainer.querySelector('.calendar-grid');
        if (calendarGrid) {
          calendarGrid.style.opacity = '';
        }
      });
  }
  
  function updateFullCalendarNavigationButtons(data) {
    // Find navigation container in full calendar section
    const navContainer = fullCalendarSection.querySelector('.calendar-navigation');
    if (!navContainer) return;
    
    navContainer.innerHTML = '';
    
    // Add Prev button
    if (data.canGoPrev) {
      const prevBtn = document.createElement('button');
      prevBtn.type = 'button';
      prevBtn.className = 'calendar-nav-btn calendar-nav-prev';
      prevBtn.setAttribute('data-year', data.prevYear);
      prevBtn.setAttribute('data-month', data.prevMonth);
      prevBtn.setAttribute('aria-label', 'Previous month');
      prevBtn.textContent = '← Prev';
      prevBtn.addEventListener('click', () => {
        updateFullCalendar(data.prevYear, data.prevMonth);
      });
      navContainer.appendChild(prevBtn);
    } else {
      const prevSpan = document.createElement('span');
      prevSpan.className = 'calendar-nav-btn calendar-nav-prev calendar-nav-disabled';
      prevSpan.setAttribute('aria-label', 'No previous month available');
      prevSpan.textContent = '← Prev';
      navContainer.appendChild(prevSpan);
    }
    
    // Add Next button
    if (data.canGoNext) {
      const nextBtn = document.createElement('button');
      nextBtn.type = 'button';
      nextBtn.className = 'calendar-nav-btn calendar-nav-next';
      nextBtn.setAttribute('data-year', data.nextYear);
      nextBtn.setAttribute('data-month', data.nextMonth);
      nextBtn.setAttribute('aria-label', 'Next month');
      nextBtn.textContent = 'Next →';
      nextBtn.addEventListener('click', () => {
        updateFullCalendar(data.nextYear, data.nextMonth);
      });
      navContainer.appendChild(nextBtn);
    } else {
      const nextSpan = document.createElement('span');
      nextSpan.className = 'calendar-nav-btn calendar-nav-next calendar-nav-disabled';
      nextSpan.setAttribute('aria-label', 'No next month available');
      nextSpan.textContent = 'Next →';
      navContainer.appendChild(nextSpan);
    }
  }
  
  // Add click handlers to initial navigation buttons in full calendar section
  const prevBtn = fullCalendarSection.querySelector('button.calendar-nav-prev[data-year]');
  const nextBtn = fullCalendarSection.querySelector('button.calendar-nav-next[data-year]');
  
  if (prevBtn) {
    prevBtn.addEventListener('click', (e) => {
      e.preventDefault();
      const year = parseInt(prevBtn.getAttribute('data-year'));
      const month = parseInt(prevBtn.getAttribute('data-month'));
      updateFullCalendar(year, month);
    });
  }
  
  if (nextBtn) {
    nextBtn.addEventListener('click', (e) => {
      e.preventDefault();
      const year = parseInt(nextBtn.getAttribute('data-year'));
      const month = parseInt(nextBtn.getAttribute('data-month'));
      updateFullCalendar(year, month);
    });
  }

  // Auto-advance calendar at end of month
  function checkAndAdvanceFullCalendar() {
    const displayedYear = parseInt(fullCalendarContainer.getAttribute('data-current-year'));
    const displayedMonth = parseInt(fullCalendarContainer.getAttribute('data-current-month'));
    
    if (!displayedYear || !displayedMonth) return;
    
    const today = new Date();
    const todayYear = today.getFullYear();
    const todayMonth = today.getMonth() + 1; // JavaScript months are 0-indexed
    const todayDay = today.getDate();
    
    // Get last day of displayed month
    const lastDayOfDisplayedMonth = new Date(displayedYear, displayedMonth, 0).getDate();
    
    // Check if displayed month has ended (we're past the last day of that month)
    // This happens when:
    // 1. Displayed year is in the past, OR
    // 2. Same year but displayed month is in the past, OR
    // 3. Same year and month but today is past the last day of that month
    const displayedMonthEnded = 
      (displayedYear < todayYear) || 
      (displayedYear === todayYear && displayedMonth < todayMonth) ||
      (displayedYear === todayYear && displayedMonth === todayMonth && todayDay > lastDayOfDisplayedMonth);
    
    // If displayed month has ended and we're not already showing current month, advance
    if (displayedMonthEnded && (todayYear !== displayedYear || todayMonth !== displayedMonth)) {
      updateFullCalendar(todayYear, todayMonth);
    }
  }
  
  // Check on page load
  checkAndAdvanceFullCalendar();
  
  // Check daily (every 24 hours) to auto-advance when month ends
  setInterval(checkAndAdvanceFullCalendar, 24 * 60 * 60 * 1000);
});

// Calendar toggle functionality - show/hide full calendar
document.addEventListener('DOMContentLoaded', () => {
  const toggleLink = document.getElementById('toggleFullCalendar');
  const fullCalendarSection = document.getElementById('fullCalendarSection');
  
  if (toggleLink && fullCalendarSection) {
    toggleLink.addEventListener('click', (e) => {
      e.preventDefault();
      
      if (fullCalendarSection.style.display === 'none' || !fullCalendarSection.classList.contains('show')) {
        // Show calendar
        fullCalendarSection.style.display = 'block';
        fullCalendarSection.classList.add('show');
        toggleLink.textContent = 'Close full calendar &rsaquo;';
        
        // Smooth scroll to calendar
        setTimeout(() => {
          fullCalendarSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 100);
      } else {
        // Hide calendar
        fullCalendarSection.style.display = 'none';
        fullCalendarSection.classList.remove('show');
        toggleLink.textContent = 'Open full academic calendar &rsaquo;';
      }
    });
  }
});

// Calendar filtering functions - accessible globally
function filterCalendarEvents(calendarContainer, filterCategory) {
  if (!calendarContainer) return;
  
  const allEvents = calendarContainer.querySelectorAll('.calendar-event');
  
  allEvents.forEach(event => {
    if (filterCategory === 'all') {
      event.classList.remove('filtered-out');
    } else {
      // Check if event has the matching category class
      if (event.classList.contains(filterCategory)) {
        event.classList.remove('filtered-out');
      } else {
        event.classList.add('filtered-out');
      }
    }
  });
  
  // Update calendar day highlighting based on visible events
  const calendarDays = calendarContainer.querySelectorAll('.calendar-day');
  calendarDays.forEach(day => {
    const dayEvents = day.querySelectorAll('.calendar-event:not(.filtered-out)');
    if (dayEvents.length > 0) {
      day.classList.add('calendar-day-has-events');
    } else {
      // Only remove has-events if it's not today and has no visible events
      if (!day.classList.contains('calendar-day-today')) {
        day.classList.remove('calendar-day-has-events');
      }
    }
  });
}

function updateLegendActiveState(legendContainer, activeFilter) {
  if (!legendContainer) return;
  
  const legendItems = legendContainer.querySelectorAll('.legend-filter');
  legendItems.forEach(item => {
    const filter = item.getAttribute('data-filter');
    if (filter === activeFilter) {
      item.classList.add('active');
    } else {
      item.classList.remove('active');
    }
  });
}

// Calendar AJAX navigation - update calendar grid in announcements section
document.addEventListener('DOMContentLoaded', () => {
  const announcementsCalendarContainer = document.querySelector('#announcements #calendar-container');
  const announcementsCalendarHeading = document.querySelector('#announcements #calendarHeading');
  const announcementsSection = document.getElementById('announcements');
  
  if (!announcementsCalendarContainer || !announcementsSection) return;
  
  let isLoading = false;
  
  function updateAnnouncementsCalendar(year, month) {
    if (isLoading) return;
    
    isLoading = true;
    
    // Disable buttons during loading - only in announcements section
    const allNavBtns = announcementsSection.querySelectorAll('button.calendar-nav-btn[data-year]');
    allNavBtns.forEach(btn => {
      if (btn.tagName === 'BUTTON') {
        btn.disabled = true;
        btn.style.opacity = '0.6';
        btn.style.cursor = 'not-allowed';
      }
    });
    
    // Show loading state
    const calendarGrid = announcementsCalendarContainer.querySelector('.calendar-grid');
    if (calendarGrid) {
      calendarGrid.style.opacity = '0.5';
    }
    
    // Fetch calendar data via AJAX
    fetch(`api/calendar.php?year=${year}&month=${month}`)
      .then(response => {
        if (!response.ok) {
          throw new Error('Failed to fetch calendar');
        }
        return response.json();
      })
      .then(data => {
        if (data.success) {
          // Update calendar HTML
          announcementsCalendarContainer.innerHTML = data.calendarHtml;
          
          // Update month heading
          if (announcementsCalendarHeading) {
            announcementsCalendarHeading.textContent = data.monthName;
          }
          
          // Update navigation buttons in announcements section
          updateAnnouncementsCalendarNavigationButtons(data);
          
          // Update data attributes
          announcementsCalendarContainer.setAttribute('data-current-year', data.year);
          announcementsCalendarContainer.setAttribute('data-current-month', data.month);
          
          // Re-apply current filter after calendar update
          const legendContainer = announcementsSection.querySelector('.calendar-legend');
          if (legendContainer) {
            const activeItem = legendContainer.querySelector('.legend-filter.active');
            if (activeItem) {
              const filterCategory = activeItem.getAttribute('data-category');
              filterCalendarEvents(announcementsCalendarContainer, filterCategory);
            }
          }
        } else {
          throw new Error(data.error || 'Failed to load calendar');
        }
      })
      .catch(error => {
        console.error('Calendar update error:', error);
        alert('Failed to load calendar. Please refresh the page.');
      })
      .finally(() => {
        isLoading = false;
        
        // Re-enable buttons
        allNavBtns.forEach(btn => {
          if (btn.tagName === 'BUTTON') {
            btn.disabled = false;
            btn.style.opacity = '';
            btn.style.cursor = '';
          }
        });
        
        // Restore opacity
        const calendarGrid = announcementsCalendarContainer.querySelector('.calendar-grid');
        if (calendarGrid) {
          calendarGrid.style.opacity = '';
        }
      });
  }
  
  function updateAnnouncementsCalendarNavigationButtons(data) {
    // Find navigation container in announcements section
    const navContainer = announcementsSection.querySelector('.calendar-navigation');
    if (!navContainer) return;
    
    navContainer.innerHTML = '';
    
    // Add Prev button
    if (data.canGoPrev) {
      const prevBtn = document.createElement('button');
      prevBtn.type = 'button';
      prevBtn.className = 'calendar-nav-btn calendar-nav-prev';
      prevBtn.setAttribute('data-year', data.prevYear);
      prevBtn.setAttribute('data-month', data.prevMonth);
      prevBtn.setAttribute('aria-label', 'Previous month');
      prevBtn.textContent = '← Prev';
      prevBtn.addEventListener('click', () => {
        updateAnnouncementsCalendar(data.prevYear, data.prevMonth);
      });
      navContainer.appendChild(prevBtn);
    } else {
      const prevSpan = document.createElement('span');
      prevSpan.className = 'calendar-nav-btn calendar-nav-prev calendar-nav-disabled';
      prevSpan.setAttribute('aria-label', 'No previous month available');
      prevSpan.textContent = '← Prev';
      navContainer.appendChild(prevSpan);
    }
    
    // Add Next button
    if (data.canGoNext) {
      const nextBtn = document.createElement('button');
      nextBtn.type = 'button';
      nextBtn.className = 'calendar-nav-btn calendar-nav-next';
      nextBtn.setAttribute('data-year', data.nextYear);
      nextBtn.setAttribute('data-month', data.nextMonth);
      nextBtn.setAttribute('aria-label', 'Next month');
      nextBtn.textContent = 'Next →';
      nextBtn.addEventListener('click', () => {
        updateAnnouncementsCalendar(data.nextYear, data.nextMonth);
      });
      navContainer.appendChild(nextBtn);
    } else {
      const nextSpan = document.createElement('span');
      nextSpan.className = 'calendar-nav-btn calendar-nav-next calendar-nav-disabled';
      nextSpan.setAttribute('aria-label', 'No next month available');
      nextSpan.textContent = 'Next →';
      navContainer.appendChild(nextSpan);
    }
  }
  
  // Add click handlers to initial navigation buttons in announcements section
  const prevBtn = announcementsSection.querySelector('button.calendar-nav-prev[data-year]');
  const nextBtn = announcementsSection.querySelector('button.calendar-nav-next[data-year]');
  
  if (prevBtn) {
    prevBtn.addEventListener('click', (e) => {
      e.preventDefault();
      const year = parseInt(prevBtn.getAttribute('data-year'));
      const month = parseInt(prevBtn.getAttribute('data-month'));
      updateAnnouncementsCalendar(year, month);
    });
  }
  
  if (nextBtn) {
    nextBtn.addEventListener('click', (e) => {
      e.preventDefault();
      const year = parseInt(nextBtn.getAttribute('data-year'));
      const month = parseInt(nextBtn.getAttribute('data-month'));
      updateAnnouncementsCalendar(year, month);
    });
  }

  // Auto-advance calendar at end of month
  function checkAndAdvanceAnnouncementsCalendar() {
    const displayedYear = parseInt(announcementsCalendarContainer.getAttribute('data-current-year'));
    const displayedMonth = parseInt(announcementsCalendarContainer.getAttribute('data-current-month'));
    
    if (!displayedYear || !displayedMonth) return;
    
    const today = new Date();
    const todayYear = today.getFullYear();
    const todayMonth = today.getMonth() + 1; // JavaScript months are 0-indexed
    const todayDay = today.getDate();
    
    // Get last day of displayed month
    const lastDayOfDisplayedMonth = new Date(displayedYear, displayedMonth, 0).getDate();
    
    // Check if displayed month has ended (we're past the last day of that month)
    // This happens when:
    // 1. Displayed year is in the past, OR
    // 2. Same year but displayed month is in the past, OR
    // 3. Same year and month but today is past the last day of that month
    const displayedMonthEnded = 
      (displayedYear < todayYear) || 
      (displayedYear === todayYear && displayedMonth < todayMonth) ||
      (displayedYear === todayYear && displayedMonth === todayMonth && todayDay > lastDayOfDisplayedMonth);
    
    // If displayed month has ended and we're not already showing current month, advance
    if (displayedMonthEnded && (todayYear !== displayedYear || todayMonth !== displayedMonth)) {
      updateAnnouncementsCalendar(todayYear, todayMonth);
    }
  }
  
  // Check on page load
  checkAndAdvanceAnnouncementsCalendar();
  
  // Check daily (every 24 hours) to auto-advance when month ends
  setInterval(checkAndAdvanceAnnouncementsCalendar, 24 * 60 * 60 * 1000);
});

// Calendar legend filtering - filter events by category
document.addEventListener('DOMContentLoaded', () => {
  const announcementsSection = document.getElementById('announcements');
  const fullCalendarSection = document.getElementById('fullCalendarSection');
  
  function setupLegendFiltering(section) {
    if (!section) return;
    
    const legendContainer = section.querySelector('.calendar-legend');
    const calendarContainer = section.querySelector('#calendar-container, #full-calendar-container');
    
    if (!legendContainer || !calendarContainer) return;
    
    // Set "All" as active by default
    const allItem = legendContainer.querySelector('[data-filter="all"]');
    if (allItem) {
      allItem.classList.add('active');
    }
    
    const legendItems = legendContainer.querySelectorAll('.legend-filter');
    legendItems.forEach(item => {
      item.addEventListener('click', () => {
        const filterCategory = item.getAttribute('data-category');
        const filterName = item.getAttribute('data-filter');
        
        // Update active state
        updateLegendActiveState(legendContainer, filterName);
        
        // Filter events
        filterCalendarEvents(calendarContainer, filterCategory);
      });
    });
  }
  
  // Setup filtering for announcements calendar
  setupLegendFiltering(announcementsSection);
  
  // Setup filtering for full calendar (when it's shown)
  if (fullCalendarSection) {
    setupLegendFiltering(fullCalendarSection);
  }
  
  // Re-apply filter when calendar is updated via AJAX
  const originalUpdateAnnouncementsCalendar = window.updateAnnouncementsCalendar;
  if (typeof originalUpdateAnnouncementsCalendar === 'undefined') {
    // Store reference if needed
  }
});

