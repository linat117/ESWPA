<!DOCTYPE html>
<html lang="en">

<?php
include 'head.php';
include 'include/config.php';

// Helper to remove a single outer <p>...</p> wrapper while keeping inner content
function strip_outer_p($html) {
    $html = trim($html);
    if (preg_match('/^<p[^>]*>(.*)<\/p>$/is', $html, $matches)) {
        return $matches[1];
    }
    return $html;
}

// Get event ID and type from URL
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$event_type = isset($_GET['type']) ? $_GET['type'] : 'upcoming';

// Validate event type
if (!in_array($event_type, ['upcoming', 'past'])) {
    $event_type = 'upcoming';
}

// Determine table and fetch event
$table = ($event_type === 'upcoming') ? 'upcoming' : 'events';
$event = null;

if ($event_id > 0) {
    $query = "SELECT * FROM $table WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $event = mysqli_fetch_assoc($result);
    }
    mysqli_stmt_close($stmt);
}

// If no event found, redirect to events page
if (!$event) {
    header("Location: events.php");
    exit();
}

// Parse event images
$event_images = json_decode($event['event_images'], true) ?: [];
?>

<style>
/* Event Details Styles */
.event-hero {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    padding: 80px 0 60px;
    margin-bottom: 40px;
}

.event-hero h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 20px;
}

.event-hero .event-meta {
    font-size: 1.1rem;
    opacity: 0.9;
}

.event-hero .event-meta i {
    margin-right: 8px;
}

.event-content {
    max-width: 800px;
    margin: 0 auto;
}

.event-image-gallery {
    margin-bottom: 40px;
}

.event-image-gallery .main-image {
    width: 100%;
    height: 400px;
    object-fit: cover;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.event-image-gallery .thumbnail-images {
    display: flex;
    gap: 10px;
    margin-top: 15px;
    flex-wrap: wrap;
}

.event-image-gallery .thumbnail {
    width: 100px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    cursor: pointer;
    transition: transform 0.3s ease;
    border: 2px solid transparent;
}

.event-image-gallery .thumbnail:hover,
.event-image-gallery .thumbnail.active {
    transform: scale(1.05);
    border-color: #007bff;
}

.event-description {
    font-size: 1.1rem;
    line-height: 1.8;
    color: #333;
    margin-bottom: 40px;
}

.event-actions {
    text-align: center;
    padding: 40px 0;
    border-top: 1px solid #eee;
    margin-top: 40px;
}

.event-actions .btn {
    margin: 0 10px;
    padding: 12px 30px;
    border-radius: 25px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.event-actions .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.breadcrumb-custom {
    background: rgba(255,255,255,0.1);
    border-radius: 25px;
    padding: 10px 20px;
    display: inline-block;
    margin-bottom: 20px;
}

.breadcrumb-custom a {
    color: white;
    text-decoration: none;
}

.breadcrumb-custom a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .event-hero h1 {
        font-size: 2rem;
    }
    
    .event-hero {
        padding: 60px 0 40px;
    }
    
    .event-image-gallery .main-image {
        height: 250px;
    }
}
</style>

<body>

    <?php
    include 'header-v1.2.php';
    ?>

    <!-- Event Hero Section -->
    <section class="event-hero">
        <div class="container">
            <div class="breadcrumb-custom">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <span class="mx-2">/</span>
                <a href="events.php">Events</a>
                <span class="mx-2">/</span>
                <span><?php echo htmlspecialchars($event['event_header']); ?></span>
            </div>
            
            <h1><?php echo htmlspecialchars($event['event_header']); ?></h1>
            
            <div class="event-meta">
                <div class="mb-2">
                    <i class="fas fa-calendar-alt"></i>
                    <?php echo date("F j, Y", strtotime($event['event_date'])); ?>
                </div>
                <?php if ($event_type === 'upcoming'): ?>
                    <div>
                        <i class="fas fa-clock"></i>
                        Upcoming Event
                    </div>
                <?php else: ?>
                    <div>
                        <i class="fas fa-history"></i>
                        Past Event
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Event Content -->
    <section class="event-content">
        <div class="container">
            <!-- Image Gallery -->
            <?php if (!empty($event_images)): ?>
                <div class="event-image-gallery">
                    <img id="mainImage" 
                         src="<?php echo htmlspecialchars('uploads/' . basename($event_images[0])); ?>" 
                         alt="<?php echo htmlspecialchars($event['event_header']); ?>" 
                         class="main-image">
                    
                    <?php if (count($event_images) > 1): ?>
                        <div class="thumbnail-images">
                            <?php foreach ($event_images as $index => $image): ?>
                                <img src="<?php echo htmlspecialchars('uploads/' . basename($image)); ?>" 
                                     alt="Event image <?php echo $index + 1; ?>" 
                                     class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                                     onclick="changeMainImage(this)">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Event Description -->
            <div class="event-description">
                <?php echo strip_outer_p($event['event_description']); ?>
            </div>

            <!-- Event Actions -->
            <div class="event-actions">
                <a href="events.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Back to Events
                </a>
                
                <button class="btn btn-outline-secondary" onclick="shareEvent()">
                    <i class="fas fa-share-alt me-2"></i>
                    Share Event
                </button>
            </div>
        </div>
    </section>

    <!-- Start Footer -->
    <?php
    include 'footer.php';
    ?>
    <!-- End Footer -->

    <!-- jQuery Frameworks -->
    <script src="assets/js/jquery-1.12.4.min.js"></script>
    <script src="assets/js/popper.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/main.js"></script>

    <script>
        // Image gallery functionality
        function changeMainImage(thumbnail) {
            const mainImage = document.getElementById('mainImage');
            const newSrc = thumbnail.src;
            
            // Fade out effect
            mainImage.style.opacity = '0.5';
            
            setTimeout(() => {
                mainImage.src = newSrc;
                mainImage.style.opacity = '1';
            }, 200);
            
            // Update active thumbnail
            document.querySelectorAll('.thumbnail').forEach(thumb => {
                thumb.classList.remove('active');
            });
            thumbnail.classList.add('active');
        }

        // Share event functionality
        function shareEvent() {
            const url = window.location.href;
            const title = '<?php echo htmlspecialchars($event["event_header"]); ?>';
            
            if (navigator.share) {
                navigator.share({
                    title: title,
                    url: url
                });
            } else {
                // Fallback - copy to clipboard
                const tempInput = document.createElement('input');
                tempInput.value = url;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                
                alert('Event link copied to clipboard!');
            }
        }

        // Smooth transitions for main image
        document.getElementById('mainImage').style.transition = 'opacity 0.3s ease';
    </script>

</body>

</html>
