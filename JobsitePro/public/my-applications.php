<?php
require_once '../config/config.php';
requireLogin();

$user_id = (int)getUserId();
$status_filter = sanitize($_GET['status'] ?? '');
$job_filter = (int)($_GET['job_id'] ?? 0);

$sql = "SELECT a.*, j.title, j.company, j.location, j.salary_min, j.salary_max, j.created_at as job_posted, j.category
        FROM applications a 
        JOIN jobs j ON a.job_id = j.id 
        WHERE a.user_id = $user_id";

if (!empty($status_filter)) {
    $sql .= " AND a.status = '$status_filter'";
}

if (getUserType() === 'employer') {
    $sql = "SELECT a.*, j.title, j.company, j.id as job_id, u.first_name, u.last_name, u.email, u.phone, u.skills
            FROM applications a 
            JOIN jobs j ON a.job_id = j.id 
            JOIN users u ON a.user_id = u.id 
            WHERE j.posted_by = $user_id";

    if (!empty($status_filter)) {
        $sql .= " AND a.status = '$status_filter'";
    }

    if ($job_filter > 0) {
        $sql .= " AND a.job_id = $job_filter";
    }
}

$sql .= " ORDER BY a.applied_at DESC";
$applications = $conn->query($sql);

// Handle status update (employer only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $app_id = (int)($_POST['app_id'] ?? 0);
        $new_status = sanitize($_POST['status'] ?? '');

        if (in_array($new_status, ['pending', 'reviewed', 'approved', 'rejected'], true)) {
            $check = $conn->query("SELECT j.posted_by FROM applications a JOIN jobs j ON a.job_id = j.id WHERE a.id = $app_id");
            if ($check && $check->num_rows > 0) {
                $row = $check->fetch_assoc();
                if ((int)$row['posted_by'] === $user_id) {
                    $conn->query("UPDATE applications SET status = '$new_status', updated_at = NOW() WHERE id = $app_id");
                    log_action('application_status_updated', "Application ID: $app_id, Status: $new_status");
                }
            }
        }
    }

    $redirect = "my-applications.php";
    $params = [];
    if (!empty($status_filter)) $params[] = "status=" . urlencode($status_filter);
    if ($job_filter > 0) $params[] = "job_id=" . $job_filter;
    if (!empty($params)) $redirect .= "?" . implode("&", $params);

    header("Location: $redirect");
    exit();
}

$page_title = 'Applications - ' . getSetting('site_name', 'Getafe Jobsite');
require_once '../includes/header.php';
?>

<div class="container">
    <?php require_once '../includes/navbar.php'; ?>

    <h1><?php echo getUserType() === 'employer' ? 'Applications Received' : 'My Applications'; ?></h1>

    <div style="margin: 20px 0; display: flex; gap: 10px; flex-wrap: wrap;">
        <?php
            $base_link = 'my-applications.php';
            if ($job_filter > 0) $base_link .= '?job_id=' . $job_filter;
            $all_link = $job_filter > 0 ? 'my-applications.php?job_id=' . $job_filter : 'my-applications.php';
            $pending_link = 'my-applications.php?status=pending' . ($job_filter > 0 ? '&job_id=' . $job_filter : '');
            $reviewed_link = 'my-applications.php?status=reviewed' . ($job_filter > 0 ? '&job_id=' . $job_filter : '');
            $approved_link = 'my-applications.php?status=approved' . ($job_filter > 0 ? '&job_id=' . $job_filter : '');
            $rejected_link = 'my-applications.php?status=rejected' . ($job_filter > 0 ? '&job_id=' . $job_filter : '');
        ?>
        <a href="<?php echo $all_link; ?>" class="btn <?php echo empty($status_filter) ? 'btn-primary' : 'btn-secondary'; ?>">All</a>
        <a href="<?php echo $pending_link; ?>" class="btn <?php echo $status_filter === 'pending' ? 'btn-primary' : 'btn-secondary'; ?>">Pending</a>
        <a href="<?php echo $reviewed_link; ?>" class="btn <?php echo $status_filter === 'reviewed' ? 'btn-primary' : 'btn-secondary'; ?>">Reviewed</a>
        <a href="<?php echo $approved_link; ?>" class="btn <?php echo $status_filter === 'approved' ? 'btn-primary' : 'btn-secondary'; ?>">Approved</a>
        <a href="<?php echo $rejected_link; ?>" class="btn <?php echo $status_filter === 'rejected' ? 'btn-primary' : 'btn-secondary'; ?>">Rejected</a>
    </div>

    <div class="applications-list">
        <?php if (!$applications || $applications->num_rows === 0): ?>
            <div class="no-results">
                <p>No applications found.</p>
            </div>
        <?php else: ?>
            <?php while ($app = $applications->fetch_assoc()): ?>
                <div class="application-card <?php echo htmlspecialchars($app['status']); ?>">
                    <div class="app-header">
                        <?php if (getUserType() === 'employer'): ?>
                            <h3><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></h3>
                            <p style="color: #666;"><?php echo htmlspecialchars($app['title']); ?></p>
                        <?php else: ?>
                            <h3><?php echo htmlspecialchars($app['title']); ?></h3>
                            <p style="color: #666;"><?php echo htmlspecialchars($app['company']); ?></p>
                        <?php endif; ?>
                        <span class="status-badge status-<?php echo htmlspecialchars($app['status']); ?>">
                            <?php echo strtoupper(htmlspecialchars($app['status'])); ?>
                        </span>
                    </div>

                    <div class="app-details">
                        <?php if (getUserType() === 'employer'): ?>
                            <p>📧 <a href="mailto:<?php echo htmlspecialchars($app['email']); ?>"><?php echo htmlspecialchars($app['email']); ?></a></p>
                            <?php if (!empty($app['phone'])): ?>
                                <p>📱 <?php echo htmlspecialchars($app['phone']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($app['skills'])): ?>
                                <p>💼 <strong>Skills:</strong> <?php echo htmlspecialchars($app['skills']); ?></p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p>🏢 <strong><?php echo htmlspecialchars($app['company']); ?></strong></p>
                            <p>📍 <?php echo htmlspecialchars($app['location']); ?></p>
                            <?php if (!empty($app['salary_min']) && !empty($app['salary_max'])): ?>
                                <p>💰 <?php echo formatSalary($app['salary_min'], $app['salary_max']); ?></p>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if (!empty($app['cover_letter'])): ?>
                            <p><strong>Cover Letter:</strong></p>
                            <p style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin-top: 5px;">
                                <?php echo nl2br(htmlspecialchars($app['cover_letter'])); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="app-footer">
                        <small>Applied: <?php echo date('M d, Y', strtotime($app['applied_at'])); ?></small>

                        <?php if (getUserType() === 'employer'): ?>
                            <form method="POST" style="display: inline; margin-left: auto;">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="app_id" value="<?php echo (int)$app['id']; ?>">
                                <select name="status" onchange="this.form.submit();" style="padding: 5px; border-radius: 4px;">
                                    <option value="pending" <?php echo $app['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="reviewed" <?php echo $app['status'] === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                    <option value="approved" <?php echo $app['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $app['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <?php require_once '../includes/footer.php'; ?>
</div>

</body>
</html>