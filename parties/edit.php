<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db = new Database();
$message = '';

$party_id = $_GET['id'] ?? null;
if (!$party_id) {
    header("Location: index.php?message=" . urlencode("Invalid party ID.") . "&type=danger");
    exit;
}

// Fetch party
$db->query("SELECT * FROM parties WHERE id = :id");
$db->bind(':id', $party_id);
$party = $db->single();

if (!$party) {
    header("Location: index.php?message=" . urlencode("Party not found.") . "&type=danger");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name === '') {
        $message = "Name is required.";
    } else {
        $profileImage = $party['profile_image'];

        // Handle profile image upload
        if (!empty($_FILES['profile_image']['name']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $newFileName = 'party_' . time() . '.' . $ext;
                $uploadDir = __DIR__ . '/../uploads/profile/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $destination = $uploadDir . $newFileName;
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $destination)) {
                    $profileImage = $newFileName;
                }
            }
        }

        $db->query("UPDATE parties SET name = :name, phone = :phone, email = :email, address = :address, profile_image = :profile_image WHERE id = :id");
        $db->bind(':name', $name);
        $db->bind(':phone', $phone);
        $db->bind(':email', $email);
        $db->bind(':address', $address);
        $db->bind(':profile_image', $profileImage);
        $db->bind(':id', $party_id);

        if ($db->execute()) {
            // Handle document uploads
            if (!empty($_FILES['documents']['name'][0])) {
                $uploadDir = __DIR__ . '/../uploads/document/parties/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                foreach ($_FILES['documents']['name'] as $i => $fileName) {
                    if ($_FILES['documents']['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        $newName = 'doc_' . $party_id . '_' . time() . '_' . $i . '.' . $ext;
                        $customName = trim($_POST['custom_names'][$i] ?? '');
                        $filePath = 'uploads/document/parties/' . $newName;

                        if (move_uploaded_file($_FILES['documents']['tmp_name'][$i], __DIR__ . '/../' . $filePath)) {
                            $db->query("INSERT INTO party_documents (party_id, file_name, file_path, custom_name) VALUES (:party_id, :file_name, :file_path, :custom_name)");
                            $db->bind(':party_id', $party_id);
                            $db->bind(':file_name', $newName);
                            $db->bind(':file_path', $filePath);
                            $db->bind(':custom_name', $customName);
                            $db->execute();
                        }
                    }
                }
            }

            // Handle new links
            if (!empty($_POST['link_labels']) && !empty($_POST['link_urls'])) {
                foreach ($_POST['link_labels'] as $i => $label) {
                    $url = trim($_POST['link_urls'][$i] ?? '');
                    if (trim($label) && filter_var($url, FILTER_VALIDATE_URL)) {
                        $db->query("INSERT INTO party_profile_links (party_id, label, url) VALUES (:party_id, :label, :url)");
                        $db->bind(':party_id', $party_id);
                        $db->bind(':label', trim($label));
                        $db->bind(':url', $url);
                        $db->execute();
                    }
                }
            }

            header("Location: profile.php?id=$party_id&message=" . urlencode("Party updated successfully.") . "&type=success");
            exit;
        } else {
            $message = "Failed to update party.";
        }
    }
}

// Fetch documents and links
$db->query("SELECT * FROM party_documents WHERE party_id = :id");
$db->bind(':id', $party_id);
$documents = $db->resultSet();

$db->query("SELECT * FROM party_profile_links WHERE party_id = :id");
$db->bind(':id', $party_id);
$links = $db->resultSet();

include __DIR__ . '/../includes/header.php';
?>

<h2>Edit Party</h2>

<?php if ($message): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <div class="mb-3">
        <label>Name *</label>
        <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($party['name']) ?>">
    </div>
    <div class="mb-3">
        <label>Phone</label>
        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($party['phone']) ?>">
    </div>
    <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($party['email']) ?>">
    </div>
    <div class="mb-3">
        <label>Address</label>
        <textarea name="address" class="form-control"><?= htmlspecialchars($party['address']) ?></textarea>
    </div>
    <div class="mb-3">
        <label>Profile Image</label>
        <?php if ($party['profile_image']): ?>
            <div class="mb-2" id="profile-image-container">
                <img src="<?= BASE_URL ?>/secure_view.php?file=profile/<?= urlencode($party['profile_image']) ?>" class="img-thumbnail" style="max-height:100px;">
                <button type="button" class="btn btn-sm btn-danger align-bottom ms-2" onclick="deleteProfileImage(<?= $party['id'] ?>, this)">Delete Image</button>
            </div>
        <?php endif; ?>
        <input type="file" name="profile_image" class="form-control" accept="image/*">
    </div>

    <hr>
    <h5>Documents</h5>
    <?php foreach ($documents as $doc): ?>
        <div class="mb-2 d-flex justify-content-between align-items-center">
            <div>
                <a href="../secure_view.php?file=<?= urlencode($doc['file_path']) ?>" target="_blank"><?= htmlspecialchars($doc['custom_name'] ?: $doc['file_name']) ?></a>
            </div>
            <button type="button" class="btn btn-sm btn-danger" onclick="deleteDocument(<?= $doc['id'] ?>, this)">Delete</button>
        </div>
    <?php endforeach; ?>

    <div id="documents-wrapper">
        <div class="row mb-2">
            <div class="col-md-6">
                <input type="file" name="documents[]" class="form-control">
            </div>
            <div class="col-md-6">
                <input type="text" name="custom_names[]" class="form-control" placeholder="Custom Name (optional)">
            </div>
        </div>
    </div>
    <button type="button" onclick="addDocumentField()" class="btn btn-sm btn-secondary mb-3">Add More</button>

    <hr>
    <h5>Profile Links</h5>
    <?php foreach ($links as $link): ?>
        <div class="mb-2 d-flex justify-content-between align-items-center">
            <div>
                <strong><?= htmlspecialchars($link['label']) ?>:</strong>
                <a href="<?= htmlspecialchars($link['url']) ?>" target="_blank"><?= htmlspecialchars($link['url']) ?></a>
            </div>
            <button type="button" class="btn btn-sm btn-danger" onclick="deleteLink(<?= $link['id'] ?>, this)">Delete</button>
        </div>
    <?php endforeach; ?>

    <div id="links-wrapper">
        <div class="row mb-2">
            <div class="col-md-6">
                <input type="text" name="link_labels[]" class="form-control" placeholder="Label (e.g. Facebook)">
            </div>
            <div class="col-md-6">
                <input type="url" name="link_urls[]" class="form-control" placeholder="URL (https://...)">
            </div>
        </div>
    </div>
    <button type="button" onclick="addLinkField()" class="btn btn-sm btn-secondary mb-3">Add More</button>

    <button type="submit" class="btn btn-primary">Update Party</button>
    <a href="index.php" class="btn btn-secondary">Cancel</a>
</form>

<script>
function addDocumentField() {
    const wrapper = document.getElementById('documents-wrapper');
    const row = document.createElement('div');
    row.className = 'row mb-2';
    row.innerHTML = `
        <div class="col-md-6">
            <input type="file" name="documents[]" class="form-control">
        </div>
        <div class="col-md-6">
            <input type="text" name="custom_names[]" class="form-control" placeholder="Custom Name (optional)">
        </div>`;
    wrapper.appendChild(row);
}

function addLinkField() {
    const wrapper = document.getElementById('links-wrapper');
    const row = document.createElement('div');
    row.className = 'row mb-2';
    row.innerHTML = `
        <div class="col-md-6">
            <input type="text" name="link_labels[]" class="form-control" placeholder="Label (e.g. Facebook)">
        </div>
        <div class="col-md-6">
            <input type="url" name="link_urls[]" class="form-control" placeholder="URL (https://...)">
        </div>`;
    wrapper.appendChild(row);
}

function deleteProfileImage(partyId, btn) {
    if (confirm('Are you sure you want to delete the profile image?')) {
        fetch('delete_profile_image.php?party_id=' + partyId)
            .then(res => res.text())
            .then(res => {
                if (res.trim() === 'success') {
                    btn.closest('#profile-image-container').remove();
                } else {
                    alert('Failed to delete image: ' + res);
                }
            }).catch(error => {
                console.error('Error deleting profile image:', error);
                alert('An error occurred while deleting the image.');
            });
    }
}

function deleteDocument(id, btn) {
    if (confirm('Delete this document?')) {
        fetch('delete_doc.php?id=' + id)
            .then(res => res.text())
            .then(res => {
                if (res.trim() === 'success') {
                    btn.closest('.mb-2').remove();
                }
            });
    }
}

function deleteLink(id, btn) {
    if (confirm('Delete this link?')) {
        fetch('delete_link.php?id=' + id)
            .then(res => res.text())
            .then(res => {
                if (res.trim() === 'success') {
                    // Remove the parent div of the button, which is the link display row
                    const linkRow = btn.closest('.mb-2');
                    if (linkRow) {
                        linkRow.remove();
                    }
                }
            });
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
