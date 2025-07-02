<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db = new Database();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name === '') {
        $message = "Name is required.";
    } else {
        // Handle profile image upload
        $profileImage = '';
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

        // Insert party
        $db->query("INSERT INTO parties (name, phone, email, address, profile_image) VALUES (:name, :phone, :email, :address, :profile_image)");
        $db->bind(':name', $name);
        $db->bind(':phone', $phone);
        $db->bind(':email', $email);
        $db->bind(':address', $address);
        $db->bind(':profile_image', $profileImage);
        if ($db->execute()) {
            $party_id = $db->lastInsertId();

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

            // Handle profile links
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

            header("Location: index.php?message=" . urlencode("Party added successfully.") . "&type=success");
            exit;
        } else {
            $message = "Failed to add party.";
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<h2>Add Party</h2>

<?php if ($message): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <div class="mb-3">
        <label>Name *</label>
        <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label>Phone</label>
        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label>Address</label>
        <textarea name="address" class="form-control"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
    </div>
    <div class="mb-3">
        <label>Profile Image</label>
        <input type="file" name="profile_image" class="form-control" accept="image/*">
    </div>

    <hr>
    <h5>Upload Documents</h5>
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
    <h5>Add Profile Links</h5>
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

    <button type="submit" class="btn btn-primary">Add Party</button>
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
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
