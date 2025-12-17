<?php
require_once '../include/config.php';
requireLogin();

$project = null;
$images = [];
$isEdit = false;

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $result = mysqli_query($con, "SELECT * FROM projects WHERE id = $id");
    $project = mysqli_fetch_assoc($result);
    
    if ($project) {
        $isEdit = true;
        $imgResult = mysqli_query($con, "SELECT * FROM project_images WHERE project_id = $id");
        while ($img = mysqli_fetch_assoc($imgResult)) {
            $images[] = $img;
        }
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $project_type = sanitize($_POST['project_type']);
    $location = sanitize($_POST['location']);
    $duration = sanitize($_POST['duration']);
    $budget = sanitize($_POST['budget']);
    $description = sanitize($_POST['description']);
    $status = sanitize($_POST['status']);
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    
    if (empty($name) || empty($project_type)) {
        $error = 'Project name and type are required';
    } else {
        if ($isEdit) {
            $query = "UPDATE projects SET 
                      name = '$name', 
                      project_type = '$project_type', 
                      location = '$location', 
                      duration = '$duration', 
                      budget = '$budget', 
                      description = '$description', 
                      status = '$status', 
                      is_published = $is_published 
                      WHERE id = $id";
            mysqli_query($con, $query);
            $project_id = $id;
            $msg = 'updated';
        } else {
            $query = "INSERT INTO projects (name, project_type, location, duration, budget, description, status, is_published) 
                      VALUES ('$name', '$project_type', '$location', '$duration', '$budget', '$description', '$status', $is_published)";
            mysqli_query($con, $query);
            $project_id = mysqli_insert_id($con);
            $msg = 'created';
        }
        
        // Handle image uploads
        if (!empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] == 0) {
                    $file = [
                        'name' => $_FILES['images']['name'][$key],
                        'tmp_name' => $tmp_name
                    ];
                    $uploaded_path = uploadImage($file, 'projects');
                    if ($uploaded_path) {
                        $is_primary = ($key == 0 && !$isEdit) ? 1 : 0;
                        mysqli_query($con, "INSERT INTO project_images (project_id, image_path, is_primary) 
                                           VALUES ($project_id, '$uploaded_path', $is_primary)");
                    }
                }
            }
        }
        
        redirect('projects.php?msg=' . $msg);
    }
}

// Handle image deletion via AJAX
if (isset($_GET['delete_image'])) {
    $img_id = (int)$_GET['delete_image'];
    $img = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM project_images WHERE id = $img_id"));
    if ($img) {
        deleteImage('../' . $img['image_path']);
        mysqli_query($con, "DELETE FROM project_images WHERE id = $img_id");
    }
    redirect('project-form.php?id=' . $_GET['id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Edit' : 'Add'; ?> Project - Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        
        <main class="main-content">
            <header class="top-header">
                <h1><i class="fas fa-<?php echo $isEdit ? 'edit' : 'plus'; ?>"></i> <?php echo $isEdit ? 'Edit' : 'Add'; ?> Project</h1>
                <a href="projects.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Projects
                </a>
            </header>
            
            <div class="dashboard-content">
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" class="form-card">
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-project-diagram"></i> Project Name *</label>
                            <input type="text" name="name" class="form-control" required
                                   value="<?php echo $project['name'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-tags"></i> Project Type *</label>
                            <select name="project_type" class="form-control" required>
                                <option value="">Select Type</option>
                                <?php 
                                $types = ['Road', 'Residential', 'Commercial', 'Infrastructure'];
                                foreach ($types as $type):
                                    $selected = (isset($project['project_type']) && $project['project_type'] == $type) ? 'selected' : '';
                                ?>
                                <option value="<?php echo $type; ?>" <?php echo $selected; ?>><?php echo $type; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-map-marker-alt"></i> Location</label>
                            <input type="text" name="location" class="form-control"
                                   value="<?php echo $project['location'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-clock"></i> Duration</label>
                            <input type="text" name="duration" class="form-control" placeholder="e.g., 12 Months"
                                   value="<?php echo $project['duration'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-rupee-sign"></i> Budget (Optional)</label>
                            <input type="text" name="budget" class="form-control" placeholder="e.g., ₹5 Crore"
                                   value="<?php echo $project['budget'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-tasks"></i> Status</label>
                            <select name="status" class="form-control">
                                <option value="Ongoing" <?php echo (isset($project['status']) && $project['status'] == 'Ongoing') ? 'selected' : ''; ?>>Ongoing</option>
                                <option value="Completed" <?php echo (isset($project['status']) && $project['status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-align-left"></i> Description</label>
                        <textarea name="description" class="form-control" rows="5"><?php echo $project['description'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-images"></i> Project Images</label>
                        <div class="image-upload-area" onclick="document.getElementById('images').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Click to upload images</p>
                            <small>JPG, PNG, GIF, WEBP (Multiple files allowed)</small>
                        </div>
                        <input type="file" id="images" name="images[]" multiple accept="image/*" style="display:none" onchange="previewImages(this)">
                        
                        <div class="image-preview-grid" id="newPreviews"></div>
                        
                        <?php if (!empty($images)): ?>
                        <h4 style="margin: 20px 0 10px;">Current Images</h4>
                        <div class="image-preview-grid">
                            <?php foreach ($images as $img): ?>
                            <div class="image-preview-item">
                                <img src="../<?php echo $img['image_path']; ?>" alt="Project Image">
                                <a href="?id=<?php echo $id; ?>&delete_image=<?php echo $img['id']; ?>" 
                                   class="remove-btn" onclick="return confirm('Delete this image?')">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="toggle-label">
                            <span><i class="fas fa-globe"></i> Publish on Website</span>
                            <label class="toggle-switch">
                                <input type="checkbox" name="is_published" 
                                       <?php echo (!isset($project['is_published']) || $project['is_published']) ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> <?php echo $isEdit ? 'Update' : 'Save'; ?> Project
                        </button>
                        <a href="projects.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <script>
    function previewImages(input) {
        const preview = document.getElementById('newPreviews');
        preview.innerHTML = '';
        
        if (input.files) {
            Array.from(input.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'image-preview-item';
                    div.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                    preview.appendChild(div);
                }
                reader.readAsDataURL(file);
            });
        }
    }
    </script>
    
    <style>
    .toggle-label {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    </style>
</body>
</html>
