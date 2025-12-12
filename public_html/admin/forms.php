<?php
declare(strict_types=1);

$pageTitle = 'Forms';
$currentSection = 'forms';

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth.php';
require_super_admin();

$formsDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'forms';

// Ensure forms directory exists
if (!is_dir($formsDir)) {
    mkdir($formsDir, 0775, true);
}

// Get forms metadata from site_settings
function get_forms_metadata(mysqli $conn): array {
    $metadataJson = get_setting($conn, 'forms_metadata', '{}');
    $metadata = json_decode($metadataJson, true) ?: [];
    return $metadata;
}

// Save forms metadata
function save_forms_metadata(mysqli $conn, array $metadata): bool {
    return set_setting($conn, 'forms_metadata', json_encode($metadata));
}

// Scan forms directory
function scan_forms_directory(string $dir): array {
    $forms = [];
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $filePath = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_file($filePath)) {
                $forms[] = [
                    'filename' => $file,
                    'path' => 'files/forms/' . $file,
                    'size' => filesize($filePath),
                    'modified' => filemtime($filePath)
                ];
            }
        }
    }
    return $forms;
}

// Delete form
if (isset($_GET['delete'])) {
    $filename = basename($_GET['delete']);
    $filePath = $formsDir . DIRECTORY_SEPARATOR . $filename;
    
    if (file_exists($filePath)) {
        unlink($filePath);
        
        // Remove from metadata
        $metadata = get_forms_metadata($conn);
        unset($metadata[$filename]);
        save_forms_metadata($conn, $metadata);
        
        add_flash('success', 'Form deleted successfully.');
    } else {
        add_flash('error', 'Form file not found.');
    }
    
    header('Location: forms.php');
    exit;
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'citizen_charter') {
        $citizenCharterData = [];

        $citizenCharterUpload = handle_file_upload('citizen_charter_upload', 'files', ['pdf']);
        if (isset($citizenCharterUpload['error'])) {
            add_flash('error', $citizenCharterUpload['error']);
            header('Location: forms.php');
            exit;
        }

        if (isset($citizenCharterUpload['path'])) {
            $citizenCharterData['citizen_charter_path'] = $citizenCharterUpload['path'];
        } else {
            // If no new file uploaded, keep existing path
            if (!empty($_POST['citizen_charter_path_current'])) {
                $citizenCharterData['citizen_charter_path'] = $_POST['citizen_charter_path_current'];
            }
        }

        if (save_settings($conn, $citizenCharterData)) {
            add_flash('success', 'Citizen Charter updated successfully.');
        } else {
            add_flash('error', 'Unable to update Citizen Charter.');
        }

        header('Location: forms.php');
        exit;

    } elseif ($action === 'student_handbook') {
        $studentHandbookData = [];

        $studentHandbookUpload = handle_file_upload('student_handbook_upload', 'files', ['pdf']);
        if (isset($studentHandbookUpload['error'])) {
            add_flash('error', $studentHandbookUpload['error']);
            header('Location: forms.php');
            exit;
        }

        if (isset($studentHandbookUpload['path'])) {
            $studentHandbookData['student_handbook_path'] = $studentHandbookUpload['path'];
        } else {
            // If no new file uploaded, keep existing path
            if (!empty($_POST['student_handbook_path_current'])) {
                $studentHandbookData['student_handbook_path'] = $_POST['student_handbook_path_current'];
            }
        }

        if (save_settings($conn, $studentHandbookData)) {
            add_flash('success', 'Student Handbook updated successfully.');
        } else {
            add_flash('error', 'Unable to update Student Handbook.');
        }

        header('Location: forms.php');
        exit;
        
    } elseif ($action === 'upload_form') {
        // Upload new form file
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? '');
        
        if ($title === '' || $category === '') {
            add_flash('error', 'Title and category are required.');
            header('Location: forms.php');
            exit;
        }
        
        $fileUpload = handle_file_upload('form_file', 'files/forms', ['pdf', 'docx', 'doc']);
        if (isset($fileUpload['error'])) {
            add_flash('error', $fileUpload['error']);
            header('Location: forms.php');
            exit;
        }
        
        if (!isset($fileUpload['path'])) {
            add_flash('error', 'Please upload a form file.');
            header('Location: forms.php');
            exit;
        }
        
        // Save metadata
        $filename = basename($fileUpload['path']);
        $metadata = get_forms_metadata($conn);
        $metadata[$filename] = [
            'title' => $title,
            'category' => $category
        ];
        save_forms_metadata($conn, $metadata);
        
        add_flash('success', 'Form uploaded successfully.');
        header('Location: forms.php');
        exit;
        
    } elseif ($action === 'update_metadata') {
        // Update form metadata
        $filename = basename($_POST['filename'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? '');
        
        if ($filename === '' || $title === '' || $category === '') {
            add_flash('error', 'Filename, title, and category are required.');
            header('Location: forms.php');
            exit;
        }
        
        $metadata = get_forms_metadata($conn);
        $metadata[$filename] = [
            'title' => $title,
            'category' => $category
        ];
        save_forms_metadata($conn, $metadata);
        
        add_flash('success', 'Form metadata updated successfully.');
        header('Location: forms.php');
        exit;
    }
}

// Get all forms
$allForms = scan_forms_directory($formsDir);
$metadata = get_forms_metadata($conn);

// Get citizen charter and student handbook paths
$citizenCharterPath = get_setting($conn, 'citizen_charter_path', '');
$studentHandbookPath = get_setting($conn, 'student_handbook_path', '');

// Merge forms with metadata
$formsWithMetadata = [];
foreach ($allForms as $form) {
    $filename = $form['filename'];
    $formData = $form;
    $formData['metadata'] = $metadata[$filename] ?? [
        'title' => pathinfo($filename, PATHINFO_FILENAME),
        'category' => 'Other Forms'
    ];
    $formsWithMetadata[] = $formData;
}

// Sort by category, then title
usort($formsWithMetadata, function($a, $b) {
    $catA = $a['metadata']['category'] ?? '';
    $catB = $b['metadata']['category'] ?? '';
    if ($catA !== $catB) {
        return strcmp($catA, $catB);
    }
    $titleA = $a['metadata']['title'] ?? '';
    $titleB = $b['metadata']['title'] ?? '';
    return strcmp($titleA, $titleB);
});

require_once __DIR__ . '/includes/header.php';
?>

<style>
    .file-pill {
        display: inline-block;
        padding: 0.4rem 0.85rem;
        background: linear-gradient(135deg, var(--maroon, #7a0019), #540013);
        color: #fff;
        border-radius: 999px;
        font-weight: 700;
        font-size: 0.95rem;
        letter-spacing: 0.01em;
        box-shadow: 0 6px 14px rgba(0, 0, 0, 0.12);
        margin: 0.35rem 0 0.5rem;
    }
</style>

<section class="card">
    <h2>Citizen Charter</h2>
    <form method="post" enctype="multipart/form-data" class="form">
        <input type="hidden" name="action" value="citizen_charter">
        <input type="hidden" name="citizen_charter_path_current" value="<?php echo htmlspecialchars($citizenCharterPath); ?>">
        
        <div class="form__group--inline">
            <div>
                <label>Current Citizen Charter</label>
                <div class="media-preview">
                    <?php if ($citizenCharterPath && file_exists(dirname(__DIR__) . '/' . $citizenCharterPath)): ?>
                        <div style="padding: 20px; text-align: center;">
                            <i class="fa-solid fa-file-pdf" style="font-size: 48px; color: #dc2626; margin-bottom: 10px;"></i>
                            <p style="margin: 0.5rem 0; font-weight: 600;">Citizen Charter PDF</p>
                            <div class="file-pill" aria-label="Current Citizen Charter file">
                                <?php echo htmlspecialchars(basename($citizenCharterPath)); ?>
                            </div>
                            <a href="../<?php echo htmlspecialchars($citizenCharterPath); ?>" target="_blank" class="btn btn--small" style="margin-top: 10px;">View PDF</a>
                        </div>
                    <?php else: ?>
                        <span>No Citizen Charter uploaded.</span>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <label for="citizen_charter_upload">Upload Citizen Charter PDF</label>
                <input type="file" id="citizen_charter_upload" name="citizen_charter_upload" accept=".pdf" <?php echo empty($citizenCharterPath) ? 'required' : ''; ?>>
                <p style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--muted);">Upload a PDF file containing the Citizen Charter</p>
                <?php if ($citizenCharterPath): ?>
                    <p style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--muted);">Leave empty to keep current file.</p>
                <?php endif; ?>
            </div>
        </div>

        <button type="submit" class="btn btn--primary">Save Citizen Charter</button>
    </form>
</section>

<section class="card">
    <h2>Student Handbook</h2>
    <form method="post" enctype="multipart/form-data" class="form">
        <input type="hidden" name="action" value="student_handbook">
        <input type="hidden" name="student_handbook_path_current" value="<?php echo htmlspecialchars($studentHandbookPath); ?>">
        
        <div class="form__group--inline">
            <div>
                <label>Current Student Handbook</label>
                <div class="media-preview">
                    <?php if ($studentHandbookPath && file_exists(dirname(__DIR__) . '/' . $studentHandbookPath)): ?>
                        <div style="padding: 20px; text-align: center;">
                            <i class="fa-solid fa-file-pdf" style="font-size: 48px; color: #dc2626; margin-bottom: 10px;"></i>
                            <p style="margin: 0.5rem 0; font-weight: 600;">Student Handbook PDF</p>
                            <div class="file-pill" aria-label="Current Student Handbook file">
                                <?php echo htmlspecialchars(basename($studentHandbookPath)); ?>
                            </div>
                            <a href="../<?php echo htmlspecialchars($studentHandbookPath); ?>" target="_blank" class="btn btn--small" style="margin-top: 10px;">View PDF</a>
                        </div>
                    <?php else: ?>
                        <span>No Student Handbook uploaded.</span>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <label for="student_handbook_upload">Upload Student Handbook PDF</label>
                <input type="file" id="student_handbook_upload" name="student_handbook_upload" accept=".pdf" <?php echo empty($studentHandbookPath) ? 'required' : ''; ?>>
                <p style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--muted);">Upload a PDF file containing the Student Handbook</p>
                <?php if ($studentHandbookPath): ?>
                    <p style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--muted);">Leave empty to keep current file.</p>
                <?php endif; ?>
            </div>
        </div>

        <button type="submit" class="btn btn--primary">Save Student Handbook</button>
    </form>
</section>

<section class="card">
    <h2>Upload New Form</h2>
    <form method="post" enctype="multipart/form-data" class="form">
        <input type="hidden" name="action" value="upload_form">
        
        <label for="form_file">Form File <span class="required">*</span></label>
        <input type="file" id="form_file" name="form_file" accept=".pdf,.docx,.doc" required>
        <small>Accepted formats: PDF, DOCX, DOC</small>
        
        <label for="title">Title <span class="required">*</span></label>
        <input type="text" id="title" name="title" required>
        
        <label for="category">Category <span class="required">*</span></label>
        <select id="category" name="category" required>
            <option value="">-- Select Category --</option>
            <option value="Request Forms">Request Forms</option>
            <option value="Student Forms">Student Forms</option>
            <option value="Other Forms">Other Forms</option>
        </select>
        
        <button type="submit" class="btn btn--primary">Upload Form</button>
    </form>
</section>

<section class="card">
    <h2>Existing Forms</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>File</th>
                    <th>Modified</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($formsWithMetadata)): ?>
                    <?php foreach ($formsWithMetadata as $form): ?>
                        <?php $meta = $form['metadata']; ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($meta['title'], ENT_QUOTES); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($meta['category'], ENT_QUOTES); ?></td>
                            <td>
                                <a href="../<?php echo htmlspecialchars($form['path'], ENT_QUOTES); ?>" target="_blank">
                                    <?php echo htmlspecialchars($form['filename'], ENT_QUOTES); ?>
                                </a>
                            </td>
                            <td><?php echo date('M j, Y', $form['modified']); ?></td>
                            <td class="table-actions">
                                <button type="button" class="btn btn--small" onclick="editForm('<?php echo htmlspecialchars($form['filename'], ENT_QUOTES); ?>')">Edit</button>
                                <a class="btn btn--small btn--danger" href="forms.php?delete=<?php echo urlencode($form['filename']); ?>" onclick="return confirm('Delete this form? This will also delete the file.');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">No forms uploaded yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Edit Form Modal -->
<div id="editFormModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; padding: 2rem;">
    <div style="background: white; max-width: 600px; margin: 2rem auto; padding: 2rem; border-radius: 0.5rem;">
        <h2>Edit Form Metadata</h2>
        <form method="post" id="editFormForm">
            <input type="hidden" name="action" value="update_metadata">
            <input type="hidden" name="filename" id="edit_filename">
            
            <label for="edit_title">Title <span class="required">*</span></label>
            <input type="text" id="edit_title" name="title" required>
            
            <label for="edit_category">Category <span class="required">*</span></label>
            <select id="edit_category" name="category" required>
                <option value="">-- Select Category --</option>
                <option value="Request Forms">Request Forms</option>
                <option value="Student Forms">Student Forms</option>
                <option value="Other Forms">Other Forms</option>
            </select>
            
            <div style="margin-top: 1rem;">
                <button type="submit" class="btn btn--primary">Update</button>
                <button type="button" class="btn btn--secondary" onclick="closeEditModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function editForm(filename) {
    // Get form data from table row
    const btn = event.target;
    const row = btn.closest('tr');
    const title = row.querySelector('strong').textContent.trim();
    const category = row.cells[1].textContent.trim();
    
    // Populate edit form
    document.getElementById('edit_filename').value = filename;
    document.getElementById('edit_title').value = title;
    document.getElementById('edit_category').value = category;
    
    // Show modal
    document.getElementById('editFormModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editFormModal').style.display = 'none';
}

// Close modal when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('editFormModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php';

