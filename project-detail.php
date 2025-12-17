<?php include 'include/config.php';

if (!isset($_GET['id'])) {
    redirect('projects.php');
}

$id = (int)$_GET['id'];
$result = mysqli_query($con, "SELECT * FROM projects WHERE id = $id AND is_published = 1");
$project = mysqli_fetch_assoc($result);

if (!$project) {
    redirect('projects.php');
}

$images = mysqli_query($con, "SELECT * FROM project_images WHERE project_id = $id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $project['name']; ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'include/header.php'; ?>
    
    <!-- Page Banner -->
    <section class="page-banner">
        <div class="container">
            <h1><?php echo $project['name']; ?></h1>
            <nav class="breadcrumb">
                <a href="index.php">Home</a> / <a href="projects.php">Projects</a> / <span><?php echo $project['name']; ?></span>
            </nav>
        </div>
    </section>
    
    <!-- Project Detail -->
    <section class="section project-detail">
        <div class="container">
            <div class="project-detail-grid">
                <!-- Image Gallery -->
                <div class="project-gallery">
                    <?php 
                    $imageList = [];
                    while ($img = mysqli_fetch_assoc($images)) {
                        $imageList[] = $img['image_path'];
                    }
                    $mainImage = !empty($imageList) ? $imageList[0] : 'assets/images/placeholder.jpg';
                    ?>
                    <div class="main-image">
                        <img src="<?php echo $mainImage; ?>" alt="<?php echo $project['name']; ?>" id="mainImage">
                    </div>
                    <?php if (count($imageList) > 1): ?>
                    <div class="thumbnail-list">
                        <?php foreach ($imageList as $index => $img): ?>
                        <div class="thumbnail <?php echo $index == 0 ? 'active' : ''; ?>" onclick="changeImage('<?php echo $img; ?>', this)">
                            <img src="<?php echo $img; ?>" alt="Thumbnail">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Project Info -->
                <div class="project-info-detail">
                    <div class="project-meta">
                        <span class="badge type"><?php echo $project['project_type']; ?></span>
                        <span class="badge status <?php echo strtolower($project['status']); ?>"><?php echo $project['status']; ?></span>
                    </div>
                    
                    <h2><?php echo $project['name']; ?></h2>
                    
                    <div class="info-list">
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <span class="label">Location</span>
                                <span class="value"><?php echo $project['location']; ?></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <span class="label">Duration</span>
                                <span class="value"><?php echo $project['duration']; ?></span>
                            </div>
                        </div>
                        <?php if ($project['budget']): ?>
                        <div class="info-item">
                            <i class="fas fa-rupee-sign"></i>
                            <div>
                                <span class="label">Budget</span>
                                <span class="value"><?php echo $project['budget']; ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="project-description">
                        <h3>Project Description</h3>
                        <p><?php echo nl2br($project['description']); ?></p>
                    </div>
                    
                    <a href="contact.php" class="btn btn-primary btn-large">
                        <i class="fas fa-phone"></i> Contact Engineer
                    </a>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Related Projects -->
    <section class="section related-projects bg-light">
        <div class="container">
            <h2 class="section-title">Related Projects</h2>
            <div class="projects-grid">
                <?php
                $related = mysqli_query($con, "SELECT p.*, (SELECT image_path FROM project_images WHERE project_id = p.id LIMIT 1) as image 
                                               FROM projects p 
                                               WHERE is_published = 1 AND id != $id AND project_type = '{$project['project_type']}'
                                               ORDER BY RAND() LIMIT 3");
                
                while ($rel = mysqli_fetch_assoc($related)):
                ?>
                <div class="project-card">
                    <div class="project-image">
                        <img src="<?php echo $rel['image'] ?: 'assets/images/placeholder.jpg'; ?>" alt="<?php echo $rel['name']; ?>">
                        <span class="project-type"><?php echo $rel['project_type']; ?></span>
                    </div>
                    <div class="project-info">
                        <h3><?php echo $rel['name']; ?></h3>
                        <a href="project-detail.php?id=<?php echo $rel['id']; ?>" class="btn btn-small">View Details</a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
    
    <?php include 'include/footer.php'; ?>
    
    <script>
    function changeImage(src, el) {
        document.getElementById('mainImage').src = src;
        document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
        el.classList.add('active');
    }
    </script>
    <script src="assets/js/main.js"></script>
</body>
</html>
