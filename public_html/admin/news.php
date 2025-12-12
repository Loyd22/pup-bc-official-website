<?php
declare(strict_types=1);

$pageTitle = 'News';
$currentSection = 'news';

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth.php';

$currentAdminId = get_current_admin_id($conn);
$editing = null;

// Debug: Test database connection and table
try {
    $testQuery = $conn->query("SHOW TABLES LIKE 'news'");
    if (!$testQuery || $testQuery->num_rows === 0) {
        error_log("ERROR: News table does not exist!");
        add_flash('error', 'Database table not found. Please contact administrator.');
    } else {
        error_log("News table exists and is accessible");
    }
    if ($testQuery) $testQuery->free();
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
}

// Load for edit
if (isset($_GET['id'])) {
    $newsId = (int)$_GET['id'];
    $editing = fetch_news_item($conn, $newsId);
    if (!$editing) {
        add_flash('error', 'News item not found.');
        header('Location: news.php');
        exit;
    }
}

function fetch_news_item(mysqli $conn, int $id): ?array {
    $stmt = $conn->prepare("SELECT * FROM news WHERE id = ?");
    if (!$stmt) return null;
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    return $row ?: null;
}

/**
 * Save uploaded image (if any) into ../images/uploads and return ['path'=>webPath].
 * Returns [] if no file chosen; returns ['error'=>msg] on failure.
 */
function save_image_upload(string $inputName, array $allowedExts = ['png','jpg','jpeg','webp']): array {
    // No file chosen
    if (
        !isset($_FILES[$inputName]) ||
        empty($_FILES[$inputName]['name']) ||
        ($_FILES[$inputName]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE
    ) {
        return [];
    }

    $err = $_FILES[$inputName]['error'] ?? UPLOAD_ERR_OK;
    if ($err !== UPLOAD_ERR_OK) {
        $map = [
            UPLOAD_ERR_INI_SIZE   => 'Image is larger than server limit (upload_max_filesize).',
            UPLOAD_ERR_FORM_SIZE  => 'Image exceeds form size limit.',
            UPLOAD_ERR_PARTIAL    => 'Upload was interrupted.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server temp folder missing.',
            UPLOAD_ERR_CANT_WRITE => 'Server cannot write uploaded file.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension blocked this upload.'
        ];
        $msg = $map[$err] ?? ('Upload failed (code '.$err.').');
        return ['error' => $msg];
    }

    $orig = $_FILES[$inputName]['name'];
    $tmp  = $_FILES[$inputName]['tmp_name'];
    $size = $_FILES[$inputName]['size'];
    $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    
    // Validate file extension
    if (!in_array($ext, $allowedExts, true)) {
        return ['error' => 'Invalid image type. Allowed: '.implode(', ', $allowedExts)];
    }
    
    // Validate file size (5MB limit)
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($size > $maxSize) {
        return ['error' => 'Image is too large. Maximum size is 5MB.'];
    }
    
    // Validate that it's actually an image
    $imageInfo = @getimagesize($tmp);
    if ($imageInfo === false) {
        return ['error' => 'File is not a valid image.'];
    }

    // Filesystem path (one level up from /admin) and web path to store in DB
    $fsDir = dirname(__DIR__) . '/images/uploads';
    if (!is_dir($fsDir) && !@mkdir($fsDir, 0775, true)) {
        return ['error' => 'Cannot create uploads dir: '.$fsDir];
    }
    if (!is_writable($fsDir)) {
        return ['error' => 'Uploads folder not writable: '.$fsDir];
    }

    // Generate unique filename
    $basename = bin2hex(random_bytes(8)).'.'.$ext;
    $fsPath   = $fsDir . '/' . $basename;
    $webPath  = 'images/uploads/' . $basename; // in HTML use ../$webPath

    // Move uploaded file to permanent location
    if (!@move_uploaded_file($tmp, $fsPath)) {
        return ['error' => 'Could not move uploaded file.'];
    }

    // Set proper file permissions
    @chmod($fsPath, 0644);

    return ['path' => $webPath];
}

/**
 * Delete image file from filesystem
 */
function delete_image_file(string $imagePath): bool {
    if (empty($imagePath)) {
        return true;
    }
    
    $fsPath = dirname(__DIR__) . '/' . $imagePath;
    if (file_exists($fsPath)) {
        return @unlink($fsPath);
    }
    
    return true; // File doesn't exist, consider it deleted
}

// Delete
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    
    // First, get the image path before deleting
    $stmt = $conn->prepare("SELECT image_path FROM news WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $deleteId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $imagePath = $row['image_path'] ?? null;
        $stmt->close();
        
        // Delete the news item
        $stmt = $conn->prepare("DELETE FROM news WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $deleteId);
            $stmt->execute();
            $stmt->close();
            
            // Clean up the image file
            if (!empty($imagePath)) {
                delete_image_file($imagePath);
            }
            
            add_flash('success', 'News item deleted.');
        }
    }
    header('Location: news.php');
    exit;
}

// Create/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // If PHP discarded body due to size limits, show a clear error
    if (empty($_POST) && empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        add_flash('error', 'Your submission is too large. Increase PHP post_max_size/upload_max_filesize or upload a smaller image.');
        header('Location: news.php');
        exit;
    }

    // Debug: Log POST data
    error_log("News POST data: " . print_r($_POST, true));
    error_log("Admin ID: " . ($currentAdminId ?? 'null'));

    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $title = trim($_POST['title'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $publishDate = trim($_POST['publish_date'] ?? '');
    $isPublished = isset($_POST['is_published']) ? 1 : 0;
    $removeImage = isset($_POST['remove_image']);

    if ($title === '' || $summary === '') {
        add_flash('error', 'Title and summary are required.');
        error_log("Validation failed: title='$title', summary='$summary'");
        header('Location: news.php' . ($id ? '?id='.$id : ''));
        exit;
    }
    
    error_log("Processing news: id=$id, title='$title', summary_length=" . strlen($summary));

    // Normalize/validate publish date
    $publishDate = ($publishDate !== '') ? $publishDate : null;
    if ($publishDate !== null) {
        $d = DateTime::createFromFormat('Y-m-d', $publishDate);
        if (!$d || $d->format('Y-m-d') !== $publishDate) {
            add_flash('error', 'Publish date must be YYYY-MM-DD.');
            header('Location: news.php' . ($id ? '?id='.$id : ''));
            exit;
        }
    }

    // Image upload only if file chosen
    $imageUpload = save_image_upload('image_upload', ['png','jpg','jpeg','webp']);
    if (isset($imageUpload['error'])) {
        add_flash('error', $imageUpload['error']);
        header('Location: news.php' . ($id ? '?id='.$id : ''));
        exit;
    }

    // For updates, fetch if not already loaded
    $targetRecord = $editing;
    if ($id && $targetRecord === null) {
        $targetRecord = fetch_news_item($conn, $id);
        if ($targetRecord === null) {
            add_flash('error', 'Unable to locate the news item you tried to update.');
            header('Location: news.php');
            exit;
        }
    }

    $stmt = null;
    try {
        if ($id) {
            // UPDATE
            $existingImage = $targetRecord['image_path'] ?? null;
            $oldImagePath = $existingImage; // Keep track of old image for cleanup
            
            if (isset($imageUpload['path'])) {
                // New image uploaded - will replace existing
                $existingImage = $imageUpload['path'];
            } elseif ($removeImage) {
                // User wants to remove image
                $existingImage = null;
            }

            $sql = "UPDATE news
                    SET title = ?, summary = ?, body = ?, publish_date = ?, is_published = ?, image_path = ?, updated_at = NOW()
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                'ssssisi',
                $title,
                $summary,
                $body,
                $publishDate,
                $isPublished,
                $existingImage,
                $id
            );
            $stmt->execute();

            // Clean up old image file if it was replaced or removed
            if (!empty($oldImagePath) && ($oldImagePath !== $existingImage)) {
                delete_image_file($oldImagePath);
            }

            add_flash('success', 'News item updated.');
        } else {
            // INSERT
            $imagePath = $imageUpload['path'] ?? null;

            if ($currentAdminId !== null) {
                $sql = "INSERT INTO news (title, summary, body, publish_date, is_published, image_path, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new mysqli_sql_exception("Failed to prepare INSERT statement: " . $conn->error);
                }
                $stmt->bind_param(
                    'ssssisi',
                    $title,
                    $summary,
                    $body,
                    $publishDate,
                    $isPublished,
                    $imagePath,
                    $currentAdminId
                );
            } else {
                $sql = "INSERT INTO news (title, summary, body, publish_date, is_published, image_path)
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new mysqli_sql_exception("Failed to prepare INSERT statement: " . $conn->error);
                }
                $stmt->bind_param(
                    'ssssis',
                    $title,
                    $summary,
                    $body,
                    $publishDate,
                    $isPublished,
                    $imagePath
                );
            }
            
            if (!$stmt->execute()) {
                throw new mysqli_sql_exception("Failed to execute INSERT statement: " . $stmt->error);
            }

            $newId = $conn->insert_id;
            add_flash('success', 'News item created successfully.');
        }
    } catch (mysqli_sql_exception $e) {
        if ($stmt instanceof mysqli_stmt) $stmt->close();
        error_log("Database error in news.php: " . $e->getMessage());
        add_flash('error', 'Unable to save the news item. Error: ' . $e->getMessage());
        header('Location: news.php' . ($id ? '?id='.$id : ''));
        exit;
    }

    if ($stmt instanceof mysqli_stmt) $stmt->close();
    header('Location: news.php');
    exit;
}

// List items (safe ordering even if publish_date is NULL)
$newsItems = $conn->query("
    SELECT n.id, n.title, n.publish_date, n.is_published, n.created_by,
           a.full_name as author_name
    FROM news n
    LEFT JOIN admins a ON n.created_by = a.id
    ORDER BY COALESCE(n.publish_date, n.created_at, NOW()) DESC
");

require_once __DIR__ . '/includes/header.php';
?>
<section class="card">
    <h2><?php echo $editing ? 'Edit News' : 'New News'; ?></h2>
    <form method="post" action="news.php" enctype="multipart/form-data" class="form">
        <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?php echo (int)$editing['id']; ?>">
        <?php endif; ?>

        <label for="title">Title</label>
        <input type="text" id="title" name="title"
               value="<?php echo htmlspecialchars($editing['title'] ?? '', ENT_QUOTES); ?>" required>

        <label for="summary">Summary</label>
        <textarea class="js-editor" id="summary" name="summary" rows="4"><?php
            echo htmlspecialchars($editing['summary'] ?? '', ENT_QUOTES); ?></textarea>

        <label for="body">Body (optional)</label>
        <textarea class="js-editor" id="body" name="body" rows="6"><?php
            echo htmlspecialchars($editing['body'] ?? '', ENT_QUOTES); ?></textarea>

        <?php if ($editing && isset($editing['created_by'])): ?>
            <?php
            $authorStmt = $conn->prepare("SELECT full_name FROM admins WHERE id = ?");
            $authorName = '—';
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
        <?php endif; ?>

        <label for="publish_date">Publish Date</label>
        <input type="date" id="publish_date" name="publish_date"
               value="<?php echo htmlspecialchars($editing['publish_date'] ?? '', ENT_QUOTES); ?>">

        <div class="form__group--inline">
            <div>
                <label>Featured Image</label>
                <div class="media-preview" id="mediaPreview">
  <?php if (!empty($editing['image_path'])): ?>
    <img id="preview-img" src="../<?php echo htmlspecialchars($editing['image_path'], ENT_QUOTES); ?>" alt="Featured image">
  <?php else: ?>
    <span id="preview-empty">No image uploaded.</span>
    <img id="preview-img" alt="Featured image" style="display:none;">
  <?php endif; ?>
</div>
            </div>
            <div>
                <label for="image_upload">Upload New Image</label>
                <input type="file" id="image_upload" name="image_upload" accept=".png,.jpg,.jpeg,.webp">
                <?php if (!empty($editing['image_path'])): ?>
                    <label class="checkbox">
                        <input type="checkbox" name="remove_image">
                        <span>Remove existing image</span>
                    </label>
                <?php endif; ?>
            </div>
        </div>

        <label class="checkbox">
            <input type="checkbox" name="is_published" <?php
                echo isset($editing['is_published'])
                    ? ($editing['is_published'] ? 'checked' : '')
                    : 'checked'; ?>>
            <span>Published</span>
        </label>

        <button type="submit" class="btn btn--primary">
            <?php echo $editing ? 'Update News' : 'Create News'; ?>
        </button>
        <?php if ($editing): ?>
            <a class="btn btn--secondary" href="news.php">Cancel</a>
        <?php endif; ?>
    </form>
</section>

<section class="card">
    <h2>Existing News</h2>
    <div class="table-responsive">
        <table>
            <thead>
            <tr>
                <th>Title</th>
                <th>Author</th>
                <th>Publish Date</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php if ($newsItems && $newsItems->num_rows > 0): ?>
                <?php while ($row = $newsItems->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['title'], ENT_QUOTES); ?></td>
                        <td><?php echo htmlspecialchars($row['author_name'] ?: '—', ENT_QUOTES); ?></td>
                        <td><?php echo $row['publish_date']
                                ? htmlspecialchars(date('M j, Y', strtotime($row['publish_date'])), ENT_QUOTES)
                                : '—'; ?></td>
                        <td><?php echo $row['is_published'] ? 'Published' : 'Draft'; ?></td>
                        <td class="table-actions">
                            <a class="btn btn--small" href="news.php?id=<?php echo (int)$row['id']; ?>">Edit</a>
                            <a class="btn btn--small btn--danger"
                               href="news.php?delete=<?php echo (int)$row['id']; ?>"
                               onclick="return confirm('Delete this news item?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">No news items created yet.</td></tr>
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
    const summary = document.getElementById('summary');
    
    if (!title.value.trim()) {
      e.preventDefault();
      alert('Title is required.');
      title.focus();
      return false;
    }
    
    if (!summary.value.trim()) {
      e.preventDefault();
      alert('Summary is required.');
      summary.focus();
      return false;
    }
  });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const fileInput  = document.getElementById('image_upload');
  const previewImg = document.getElementById('preview-img');
  const emptyMsg   = document.getElementById('preview-empty');

  function showPreview(file) {
    if (!file || !file.type.startsWith('image/')) return;
    const url = URL.createObjectURL(file);
    previewImg.src = url;
    previewImg.style.display = 'block';
    if (emptyMsg) emptyMsg.style.display = 'none';
    previewImg.onload = () => URL.revokeObjectURL(url); // free memory
  }

  fileInput?.addEventListener('change', () => {
    const file = fileInput.files && fileInput.files[0];
    showPreview(file);
  });

  // Optional: if user ticks "Remove existing image", show placeholder again
  const removeCb = document.querySelector('input[name="remove_image"]');
  removeCb?.addEventListener('change', () => {
    if (removeCb.checked) {
      previewImg.removeAttribute('src');
      previewImg.style.display = 'none';
      if (emptyMsg) emptyMsg.style.display = '';
      fileInput.value = ''; // clear file input
    }
  });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php';