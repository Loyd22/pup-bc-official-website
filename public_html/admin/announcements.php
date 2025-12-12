<?php
declare(strict_types=1);

$pageTitle = 'Announcements';
$currentSection = 'announcements';

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth.php';

$currentAdminId = get_current_admin_id($conn);
$editing = null;

// Load for edit
if (isset($_GET['id'])) {
    $eventId = (int)$_GET['id'];
    $editing = fetch_event_item($conn, $eventId);
    if (!$editing) {
        add_flash('error', 'Announcement not found.');
        header('Location: announcements.php');
        exit;
    }
}

function fetch_event_item(mysqli $conn, int $id): ?array {
    $stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
    if (!$stmt) return null;
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    return $row ?: null;
}

// Delete
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $deleteId);
        $stmt->execute();
        $stmt->close();
        add_flash('success', 'Announcement deleted.');
    }
    header('Location: announcements.php');
    exit;
}

// Create/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $startDate = trim($_POST['start_date'] ?? '');
    $endDate = trim($_POST['end_date'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $category = trim($_POST['category'] ?? 'Events');
    $showInAnnouncement = isset($_POST['show_in_announcement']) ? 1 : 0;
    $showOnHomepage = isset($_POST['show_on_homepage']) ? 1 : 0;

    if ($title === '' || $startDate === '') {
        add_flash('error', 'Title and start date are required.');
        header('Location: announcements.php' . ($id ? '?id='.$id : ''));
        exit;
    }
    
    // Get current admin's full name for author field (backward compatibility)
    $adminName = $_SESSION['admin_name'] ?? 'Admin';
    
    // Get current admin ID for created_by
    $createdBy = $currentAdminId;

    // Validate start date
    $d = DateTime::createFromFormat('Y-m-d', $startDate);
    if (!$d || $d->format('Y-m-d') !== $startDate) {
        add_flash('error', 'Start date must be YYYY-MM-DD.');
        header('Location: announcements.php' . ($id ? '?id='.$id : ''));
        exit;
    }

    // Validate end date if provided
    $endDateNormalized = null;
    if ($endDate !== '') {
        $dEnd = DateTime::createFromFormat('Y-m-d', $endDate);
        if (!$dEnd || $dEnd->format('Y-m-d') !== $endDate) {
            add_flash('error', 'End date must be YYYY-MM-DD.');
            header('Location: announcements.php' . ($id ? '?id='.$id : ''));
            exit;
        }
        $endDateNormalized = $endDate;
    }

    // If end_date is empty, set it to start_date
    if ($endDateNormalized === null) {
        $endDateNormalized = $startDate;
    }

    $stmt = null;
    try {
        if ($id) {
            // UPDATE - preserve created_by, update author for backward compatibility
            $sql = "UPDATE events 
                    SET title = ?, description = ?, start_date = ?, end_date = ?, location = ?, 
                        category = ?, author = ?, show_in_announcement = ?, show_on_homepage = ?, created_at = created_at
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                'sssssssiii',
                $title,
                $description,
                $startDate,
                $endDateNormalized,
                $location,
                $category,
                $adminName,
                $showInAnnouncement,
                $showOnHomepage,
                $id
            );
            $stmt->execute();
            add_flash('success', 'Announcement updated.');
        } else {
            // INSERT - set both created_by and author
            $sql = "INSERT INTO events (title, description, start_date, end_date, location, category, author, created_by, show_in_announcement, show_on_homepage)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                'sssssssiii',
                $title,
                $description,
                $startDate,
                $endDateNormalized,
                $location,
                $category,
                $adminName,
                $createdBy,
                $showInAnnouncement,
                $showOnHomepage
            );
            $stmt->execute();
            add_flash('success', 'Announcement created.');
        }
    } catch (mysqli_sql_exception $e) {
        if ($stmt instanceof mysqli_stmt) $stmt->close();
        add_flash('error', 'Unable to save the announcement. Please review the form and try again.');
        header('Location: announcements.php' . ($id ? '?id='.$id : ''));
        exit;
    }

    if ($stmt instanceof mysqli_stmt) $stmt->close();
    header('Location: announcements.php');
    exit;
}

// List items
$eventItems = $conn->query("
    SELECT e.id, e.title, e.start_date, e.end_date, e.location, e.category, e.author, 
           e.show_in_announcement, e.show_on_homepage, e.created_by,
           a.full_name as author_name
    FROM events e
    LEFT JOIN admins a ON e.created_by = a.id
    ORDER BY e.start_date DESC, e.created_at DESC
");

require_once __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h2><?php echo $editing ? 'Edit Announcement' : 'Create Announcement'; ?></h2>
    <form method="post" action="announcements.php" class="form">
        <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?php echo (int)$editing['id']; ?>">
        <?php endif; ?>

        <label for="title">Title <span style="color: red;">*</span></label>
        <input type="text" id="title" name="title"
               value="<?php echo htmlspecialchars($editing['title'] ?? '', ENT_QUOTES); ?>" required>

        <label for="description">Description</label>
        <textarea class="js-editor" id="description" name="description" rows="6"><?php
            echo htmlspecialchars($editing['description'] ?? '', ENT_QUOTES); ?></textarea>

        <div class="form__group--inline">
            <div>
                <label for="start_date">Start Date <span style="color: red;">*</span></label>
                <input type="date" id="start_date" name="start_date"
                       value="<?php echo htmlspecialchars($editing['start_date'] ?? '', ENT_QUOTES); ?>" required>
            </div>
            <div>
                <label for="end_date">End Date</label>
                <input type="date" id="end_date" name="end_date"
                       value="<?php echo htmlspecialchars($editing['end_date'] ?? '', ENT_QUOTES); ?>"
                       placeholder="Leave empty to use start date">
            </div>
        </div>

        <label for="location">Location</label>
        <input type="text" id="location" name="location"
               value="<?php echo htmlspecialchars($editing['location'] ?? '', ENT_QUOTES); ?>"
               placeholder="e.g., Main Auditorium, Library, Online">

        <label for="category">Category <span style="color: red;">*</span></label>
        <select id="category" name="category" required>
            <option value="Events" <?php echo ($editing['category'] ?? 'Events') === 'Events' ? 'selected' : ''; ?>>Events</option>
            <option value="Academics" <?php echo ($editing['category'] ?? 'Events') === 'Academics' ? 'selected' : ''; ?>>Academics</option>
            <option value="Alerts & Safety" <?php echo ($editing['category'] ?? 'Events') === 'Alerts & Safety' ? 'selected' : ''; ?>>Alerts & Safety</option>
        </select>

        <?php if ($editing && isset($editing['created_by'])): ?>
            <?php
            $authorStmt = $conn->prepare("SELECT full_name FROM admins WHERE id = ?");
            $authorName = $editing['author'] ?? '—';
            if ($authorStmt) {
                $authorStmt->bind_param('i', $editing['created_by']);
                $authorStmt->execute();
                $authorResult = $authorStmt->get_result();
                if ($authorRow = $authorResult->fetch_assoc()) {
                    $authorName = $authorRow['full_name'];
                }
                $authorStmt->close();
            }
            ?>
            <label>Author</label>
            <input type="text" value="<?php echo htmlspecialchars($authorName, ENT_QUOTES); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;">
            <small style="color: var(--muted);">Author is automatically set to the logged-in admin.</small>
        <?php else: ?>
            <label>Author</label>
            <input type="text" value="<?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin', ENT_QUOTES); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;">
            <small style="color: var(--muted);">Author will be automatically set to your name.</small>
        <?php endif; ?>

        <div class="form__group--inline">
            <label class="checkbox">
                <input type="checkbox" name="show_in_announcement" <?php
                    echo isset($editing['show_in_announcement'])
                        ? ($editing['show_in_announcement'] ? 'checked' : '')
                        : ''; ?>>
                <span>Show as Announcement</span>
            </label>
            <label class="checkbox">
                <input type="checkbox" name="show_on_homepage" <?php
                    echo isset($editing['show_on_homepage'])
                        ? ($editing['show_on_homepage'] ? 'checked' : '')
                        : ''; ?>>
                <span>Show on Homepage</span>
            </label>
        </div>

        <button type="submit" class="btn btn--primary">
            <?php echo $editing ? 'Update Announcement' : 'Create Announcement'; ?>
        </button>
        <?php if ($editing): ?>
            <a class="btn btn--secondary" href="announcements.php">Cancel</a>
        <?php endif; ?>
    </form>
</section>

<section class="card">
    <h2>Existing Announcements</h2>
    <div class="table-responsive">
        <table>
            <thead>
            <tr>
                <th>Title</th>
                <th>Author</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Location</th>
                <th>Category</th>
                <th>Announcement</th>
                <th>Homepage</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php if ($eventItems && $eventItems->num_rows > 0): ?>
                <?php while ($row = $eventItems->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['title'], ENT_QUOTES); ?></td>
                        <td><?php echo htmlspecialchars($row['author_name'] ?: ($row['author'] ?: '—'), ENT_QUOTES); ?></td>
                        <td><?php echo $row['start_date']
                                ? htmlspecialchars(date('M j, Y', strtotime($row['start_date'])), ENT_QUOTES)
                                : '—'; ?></td>
                        <td><?php echo $row['end_date']
                                ? htmlspecialchars(date('M j, Y', strtotime($row['end_date'])), ENT_QUOTES)
                                : '—'; ?></td>
                        <td><?php echo htmlspecialchars($row['location'] ?: '—', ENT_QUOTES); ?></td>
                        <td><?php echo htmlspecialchars($row['category'] ?? 'Events', ENT_QUOTES); ?></td>
                        <td><?php echo $row['show_in_announcement'] ? 'Yes' : 'No'; ?></td>
                        <td><?php echo $row['show_on_homepage'] ? 'Yes' : 'No'; ?></td>
                        <td class="table-actions">
                            <a class="btn btn--small" href="announcements.php?id=<?php echo (int)$row['id']; ?>">Edit</a>
                            <a class="btn btn--small btn--danger"
                               href="announcements.php?delete=<?php echo (int)$row['id']; ?>"
                               onclick="return confirm('Delete this announcement?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="9">No announcements created yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Force WYSIWYG editors to sync value back to textareas before submit -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.querySelector('form.form');
  if (!form) return;

  form.addEventListener('submit', function (e) {
    // TinyMCE
    if (window.tinymce && typeof tinymce.triggerSave === 'function') {
      tinymce.triggerSave();
    }
    // Quill (commonly stored on textarea._quill)
    document.querySelectorAll('textarea.js-editor').forEach(function (ta) {
      if (ta._quill && ta._quill.root) {
        ta.value = ta._quill.root.innerHTML.trim();
      }
    });
    // Trix: <trix-editor input="id"> → mirror back
    document.querySelectorAll('trix-editor[input]').forEach(function (ed) {
      const id = ed.getAttribute('input');
      const hidden = document.getElementById(id);
      if (hidden) hidden.value = ed.innerHTML.trim();
    });

    // Validate required fields
    const title = document.getElementById('title');
    const startDate = document.getElementById('start_date');
    
    if (!title.value.trim()) {
      e.preventDefault();
      alert('Title is required.');
      title.focus();
      return false;
    }
    
    if (!startDate.value.trim()) {
      e.preventDefault();
      alert('Start date is required.');
      startDate.focus();
      return false;
    }
  });

  // Auto-check "Show on Homepage" when "Show as Announcement" is checked
  // Auto-uncheck "Show on Homepage" when "Show as Announcement" is unchecked
  const showInAnnouncement = document.querySelector('input[name="show_in_announcement"]');
  const showOnHomepage = document.querySelector('input[name="show_on_homepage"]');
  
  if (showInAnnouncement && showOnHomepage) {
    showInAnnouncement.addEventListener('change', function() {
      if (this.checked) {
        // Auto-check "Show on Homepage" when "Show as Announcement" is checked
        showOnHomepage.checked = true;
      } else {
        // Auto-uncheck "Show on Homepage" when "Show as Announcement" is unchecked
        // (can't show on homepage if it's not an announcement)
        showOnHomepage.checked = false;
      }
    });
    
    // Allow manual unchecking of "Show on Homepage" even if "Show as Announcement" is checked
    // But prevent checking "Show on Homepage" if "Show as Announcement" is not checked
    showOnHomepage.addEventListener('change', function() {
      if (this.checked && !showInAnnouncement.checked) {
        // Can't show on homepage if it's not an announcement
        alert('You must check "Show as Announcement" first before showing on homepage.');
        this.checked = false;
      }
    });
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php';
