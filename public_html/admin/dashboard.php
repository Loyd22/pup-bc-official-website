<?php
declare(strict_types=1);

$pageTitle = 'Dashboard';
$currentSection = 'dashboard';

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth.php';
// Dashboard is accessible to both super_admin and content_admin

require_once __DIR__ . '/includes/header.php';

$newsStats = ['total' => 0, 'published' => 0, 'drafts' => 0];
if ($result = $conn->query("SELECT COUNT(*) AS total, SUM(is_published = 1) AS published, SUM(is_published = 0) AS drafts FROM news")) {
    $row = $result->fetch_assoc() ?: [];
    $newsStats['total'] = (int)($row['total'] ?? 0);
    $newsStats['published'] = (int)($row['published'] ?? 0);
    $newsStats['drafts'] = (int)($row['drafts'] ?? 0);
    $result->free();
}


$visitorStats = ['today' => 0, 'week' => 0, 'month' => 0, 'total' => 0];
$hasVisitorTable = false;
if ($result = $conn->query("SHOW TABLES LIKE 'visitors'")) {
    $hasVisitorTable = $result->num_rows > 0;
    $result->free();
}

if ($hasVisitorTable) {
    $visitorDateColumn = 'visited_at';
    if ($result = $conn->query("SHOW COLUMNS FROM visitors LIKE 'visited_at'")) {
        if ($result->num_rows === 0) {
            if ($fallback = $conn->query("SHOW COLUMNS FROM visitors LIKE 'visit_time'")) {
                if ($fallback->num_rows > 0) {
                    $visitorDateColumn = 'visit_time';
                }
                $fallback->free();
            }
        }
        $result->free();
    }

    $column = $visitorDateColumn;
    $visitorQueries = [
        'today' => "SELECT COUNT(*) AS count FROM visitors WHERE DATE($column) = CURDATE()",
        'week' => "SELECT COUNT(*) AS count FROM visitors WHERE YEARWEEK($column, 1) = YEARWEEK(CURDATE(), 1)",
        'month' => "SELECT COUNT(*) AS count FROM visitors WHERE YEAR($column) = YEAR(CURDATE()) AND MONTH($column) = MONTH(CURDATE())",
        'total' => "SELECT COUNT(*) AS count FROM visitors"
    ];

    foreach ($visitorQueries as $key => $sql) {
        if ($result = $conn->query($sql)) {
            $row = $result->fetch_assoc() ?: [];
            $visitorStats[$key] = (int)($row['count'] ?? 0);
            $result->free();
        }
    }
}

$recentNews = [];
if ($result = $conn->query("SELECT id, title, COALESCE(publish_date, created_at) AS display_date FROM news ORDER BY COALESCE(publish_date, created_at) DESC LIMIT 5")) {
    while ($row = $result->fetch_assoc()) {
        $recentNews[] = [
            'id' => (int)($row['id'] ?? 0),
            'title' => (string)($row['title'] ?? ''),
            'display_date' => $row['display_date'] ?? null
        ];
    }
    $result->free();
}


$formatDate = static function (?string $value): string {
    if ($value === null || $value === '') {
        return 'Date not set';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return 'Date not set';
    }

    return date('M j, Y', $timestamp);
};
?>

<section class="card-grid">
    <article class="card">
        <h2>News</h2>
        <p class="metric"><?php echo number_format($newsStats['total']); ?></p>
        <p>
            <strong>Published:</strong> <?php echo number_format($newsStats['published']); ?><br>
            <strong>Drafts:</strong> <?php echo number_format($newsStats['drafts']); ?>
        </p>
        <a class="card__link" href="news.php">Manage news</a>
    </article>

    <article class="card">
        <h2>Visitor Traffic</h2>
        <p class="metric"><?php echo number_format($visitorStats['total']); ?></p>
        <p>
            <strong>Today:</strong> <?php echo number_format($visitorStats['today']); ?><br>
            <strong>This week:</strong> <?php echo number_format($visitorStats['week']); ?><br>
            <strong>This month:</strong> <?php echo number_format($visitorStats['month']); ?>
        </p>
        <a class="card__link" href="../DATAANALYTICS/visitors.php" target="_blank" rel="noopener">View visitor API</a>
    </article>
</section>

<section class="card">
    <h2>Recent Updates</h2>
    <div class="recent-columns">
        <div>
            <h3>Latest News</h3>
            <?php if ($recentNews !== []): ?>
                <ul>
                    <?php foreach ($recentNews as $item): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                            <br>
                            <small><?php echo htmlspecialchars($formatDate($item['display_date'])); ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No news articles yet.</p>
            <?php endif; ?>
            <a class="card__link" href="news.php">Go to news</a>
        </div>

    </div>
</section>

<section class="card">
    <h2>Analytics</h2>
    <div class="recent-columns">
        <div>
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                <h3 style="margin: 0;">Visits</h3>
                <select id="jsVisitRange" style="padding: 0.4rem 0.6rem; border: 1px solid var(--border, #ddd); border-radius: 0.5rem; font-size: 0.9rem;">
                    <option value="7">Last 7 Days</option>
                    <option value="14">Last 14 Days</option>
                    <option value="30">Last 30 Days</option>
                </select>
            </div>
            <canvas id="chartVisits7d" style="max-height: 300px;"></canvas>
        </div>
        <div>
            <h3>Visitors Summary</h3>
            <canvas id="chartVisitorsSummary" style="max-height: 300px;"></canvas>
            <small style="color: var(--muted);">Today, This week, This month</small>
        </div>
        <div>
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                <h3 style="margin: 0;">Views per Page</h3>
                <select id="jsPageViewsRange" style="padding: 0.4rem 0.6rem; border: 1px solid var(--border, #ddd); border-radius: 0.5rem; font-size: 0.9rem;">
                    <option value="7">Last 7 Days</option>
                    <option value="14">Last 14 Days</option>
                    <option value="30">Last 30 Days</option>
                </select>
            </div>
            <canvas id="chartPageViews" style="max-height: 300px;"></canvas>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const visitsEl = document.getElementById('chartVisits7d');
  const summaryEl = document.getElementById('chartVisitorsSummary');
  const rangeSelect = document.getElementById('jsVisitRange');
  let visitsChart = null;

  // Function to fetch and update visits chart
  function updateVisitsChart(days) {
    fetch('../DATAANALYTICS/visitors_chart.php?days=' + encodeURIComponent(days))
      .then(r => {
        if (!r.ok) throw new Error('Failed to fetch');
        return r.json();
      })
      .then(data => {
        // Handle empty data gracefully - ensure we have at least empty arrays
        const labels = Array.isArray(data) && data.length > 0 
          ? data.map(d => d.label || '') 
          : [];
        const counts = Array.isArray(data) && data.length > 0 
          ? data.map(d => d.count || 0) 
          : [];

        if (visitsEl) {
          // Destroy existing chart if it exists
          if (visitsChart) {
            visitsChart.destroy();
          }

          // Create or update chart
          visitsChart = new Chart(visitsEl, {
            type: 'line',
            data: {
              labels: labels.length > 0 ? labels : ['No data'],
              datasets: [{
                label: 'Visits',
                data: counts.length > 0 ? counts : [0],
                borderColor: '#7a0019',
                backgroundColor: 'rgba(122, 0, 25, 0.15)',
                tension: 0.35,
                fill: true,
                pointRadius: 3,
                pointBackgroundColor: '#7a0019'
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: true,
              plugins: { legend: { display: false } },
              scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, ticks: { precision: 0 } }
              }
            }
          });
        }
      })
      .catch(err => {
        console.error('Error loading visits chart:', err);
        // Show empty chart on error
        if (visitsEl && visitsChart) {
          visitsChart.destroy();
        }
        if (visitsEl) {
          visitsChart = new Chart(visitsEl, {
            type: 'line',
            data: {
              labels: ['No data'],
              datasets: [{
                label: 'Visits',
                data: [0],
                borderColor: '#7a0019',
                backgroundColor: 'rgba(122, 0, 25, 0.15)'
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: true,
              plugins: { legend: { display: false } },
              scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, ticks: { precision: 0 } }
              }
            }
          });
        }
      });
  }

  // Initial load with default 7 days
  const initialDays = rangeSelect ? rangeSelect.value : '7';
  updateVisitsChart(initialDays);

  // Listen for range changes
  if (rangeSelect) {
    rangeSelect.addEventListener('change', function() {
      const selectedDays = this.value;
      updateVisitsChart(selectedDays);
    });
  }

  // Fetch visitors summary (today/week/month)
  fetch('../DATAANALYTICS/visitors.php')
    .then(r => {
      if (!r.ok) throw new Error('Failed to fetch');
      return r.json();
    })
    .then(stats => {
      const labels = ['Today', 'This week', 'This month'];
      const counts = [
        stats.today || 0, 
        stats.week || 0, 
        stats.month || 0
      ];
      if (summaryEl) {
        new Chart(summaryEl, {
          type: 'doughnut',
          data: {
            labels,
            datasets: [{
              data: counts,
              backgroundColor: ['#f3b233', '#7a0019', '#540013'],
              borderWidth: 0
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { position: 'bottom' } },
            cutout: '60%'
          }
        });
      }
    })
    .catch(err => {
      console.error('Error loading visitors summary:', err);
      // Show empty chart on error
      if (summaryEl) {
        new Chart(summaryEl, {
          type: 'doughnut',
          data: {
            labels: ['Today', 'This week', 'This month'],
            datasets: [{
              data: [0, 0, 0],
              backgroundColor: ['#f3b233', '#7a0019', '#540013'],
              borderWidth: 0
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { position: 'bottom' } },
            cutout: '60%'
          }
        });
      }
    });

  // Page Views Chart
  const pageViewsEl = document.getElementById('chartPageViews');
  const pageViewsRangeSelect = document.getElementById('jsPageViewsRange');
  let pageViewsChart = null;

  function updatePageViewsChart(days) {
    fetch('../DATAANALYTICS/page_views_chart.php?days=' + encodeURIComponent(days))
      .then(r => {
        if (!r.ok) throw new Error('Failed to fetch');
        return r.json();
      })
      .then(data => {
        const pages = Array.isArray(data) && data.length > 0 
          ? data.map(d => d.page || d.slug || '') 
          : [];
        const views = Array.isArray(data) && data.length > 0 
          ? data.map(d => d.views || 0) 
          : [];

        if (pageViewsEl) {
          // Destroy existing chart if it exists
          if (pageViewsChart) {
            pageViewsChart.destroy();
          }

          // Create or update chart
          pageViewsChart = new Chart(pageViewsEl, {
            type: 'bar',
            data: {
              labels: pages.length > 0 ? pages : ['No data'],
              datasets: [{
                label: 'Page Views',
                data: views.length > 0 ? views : [0],
                backgroundColor: '#7a0019',
                borderColor: '#540013',
                borderWidth: 1
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: true,
              plugins: { 
                legend: { display: false },
                tooltip: {
                  callbacks: {
                    label: function(context) {
                      return 'Views: ' + context.parsed.y;
                    }
                  }
                }
              },
              scales: {
                x: { 
                  grid: { display: false },
                  ticks: {
                    maxRotation: 45,
                    minRotation: 45
                  }
                },
                y: { 
                  beginAtZero: true, 
                  ticks: { precision: 0 },
                  grid: { color: 'rgba(122, 0, 25, 0.1)' }
                }
              }
            }
          });
        }
      })
      .catch(err => {
        console.error('Error loading page views chart:', err);
        // Show empty chart on error
        if (pageViewsEl && pageViewsChart) {
          pageViewsChart.destroy();
        }
        if (pageViewsEl) {
          pageViewsChart = new Chart(pageViewsEl, {
            type: 'bar',
            data: {
              labels: ['No data'],
              datasets: [{
                label: 'Page Views',
                data: [0],
                backgroundColor: '#7a0019',
                borderColor: '#540013',
                borderWidth: 1
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: true,
              plugins: { legend: { display: false } },
              scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, ticks: { precision: 0 } }
              }
            }
          });
        }
      });
  }

  // Initial load with default 7 days
  const initialPageViewsDays = pageViewsRangeSelect ? pageViewsRangeSelect.value : '7';
  updatePageViewsChart(initialPageViewsDays);

  // Listen for range changes
  if (pageViewsRangeSelect) {
    pageViewsRangeSelect.addEventListener('change', function() {
      const selectedDays = this.value;
      updatePageViewsChart(selectedDays);
    });
  }
});
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
