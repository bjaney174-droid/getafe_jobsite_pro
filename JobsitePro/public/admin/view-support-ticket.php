<?php
require_once '../../config/config.php';
requireAdmin();

$ticket_id = (int)($_GET['id'] ?? 0);

$ticket = $conn->query("SELECT * FROM support_tickets WHERE id = $ticket_id")->fetch_assoc();

if (!$ticket) {
    die('Ticket not found');
}

$replies = $conn->query("SELECT sr.*, u.first_name, u.last_name FROM support_replies sr 
                        LEFT JOIN users u ON sr.sender_id = u.id 
                        WHERE sr.ticket_id = $ticket_id 
                        ORDER BY sr.created_at ASC");
?>

<div class="admin-ticket-view">
    <div class="ticket-view-header">
        <div>
            <h2><?php echo htmlspecialchars($ticket['subject']); ?></h2>
            <p class="ticket-from">From: <strong><?php echo htmlspecialchars($ticket['name']); ?></strong> (<?php echo htmlspecialchars($ticket['email']); ?>)</p>
        </div>
        <span class="ticket-id-badge"><?php echo $ticket['ticket_id']; ?></span>
    </div>

    <div class="ticket-meta">
        <span>Submitted: <?php echo date('M d, Y H:i', strtotime($ticket['created_at'])); ?></span>
        <span style="color: <?php echo ($ticket['status'] == 'open' ? '#f59e0b' : ($ticket['status'] == 'in_progress' ? '#3b82f6' : '#10b981')); ?>;">
            Status: <strong><?php echo ucfirst($ticket['status']); ?></strong>
        </span>
    </div>

    <div class="messages-section">
        <div class="message-item message-original">
            <div class="message-header">
                <strong><?php echo htmlspecialchars($ticket['name']); ?></strong>
                <span class="message-time"><?php echo date('M d, Y H:i', strtotime($ticket['created_at'])); ?></span>
                <span class="user-badge">Customer</span>
            </div>
            <div class="message-body">
                <?php echo nl2br(htmlspecialchars($ticket['message'])); ?>
            </div>
        </div>

        <?php while ($reply = $replies->fetch_assoc()): 
            $is_admin = $reply['sender_type'] == 'admin';
        ?>
            <div class="message-item <?php echo $is_admin ? 'message-admin-reply' : ''; ?>">
                <div class="message-header">
                    <strong><?php echo $is_admin ? 'Support Team' : htmlspecialchars($reply['first_name'] . ' ' . $reply['last_name']); ?></strong>
                    <span class="message-time"><?php echo date('M d, Y H:i', strtotime($reply['created_at'])); ?></span>
                    <?php if ($is_admin): ?>
                        <span class="admin-reply-badge">👨‍💼 Admin Reply</span>
                    <?php else: ?>
                        <span class="user-badge">Customer</span>
                    <?php endif; ?>
                </div>
                <div class="message-body">
                    <?php echo nl2br(htmlspecialchars($reply['message'])); ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <?php if ($ticket['status'] != 'closed'): ?>
        <form method="POST" action="support-tickets.php" class="admin-reply-form">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="action" value="reply">
            <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
            
            <div class="form-group">
                <label>Your Reply</label>
                <textarea name="reply_message" rows="5" placeholder="Type your support response..." required></textarea>
            </div>
            
            <div class="form-group">
                <label>Update Status</label>
                <select name="status" required>
                    <option value="in_progress" <?php echo $ticket['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="closed">Close Ticket</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success">📤 Send Reply</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('ticketModal')">Cancel</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<style>
.admin-ticket-view {
    padding: 0;
}

.ticket-view-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
    gap: 20px;
}

.ticket-view-header h2 {
    margin: 0 0 5px 0;
    color: #1f2937;
}

.ticket-from {
    margin: 0;
    font-size: 14px;
    color: #6b7280;
}

.ticket-id-badge {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #1e40af;
    padding: 8px 12px;
    border-radius: 6px;
    font-family: monospace;
    font-weight: 600;
}

.ticket-meta {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    font-size: 13px;
    color: #6b7280;
}

.messages-section {
    background: #f9fafb;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    max-height: 400px;
    overflow-y: auto;
}

.message-item {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
}

.message-original {
    background: #eff6ff;
    border-color: #bfdbfe;
}

.message-admin-reply {
    margin-left: 20px;
    background: #dcfce7;
    border-color: #86efac;
}

.message-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 10px;
    font-size: 13px;
    flex-wrap: wrap;
}

.message-header strong {
    color: #1f2937;
}

.message-time {
    color: #9ca3af;
    font-size: 12px;
}

.user-badge {
    background: #dbeafe;
    color: #1e40af;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.admin-reply-badge {
    background: #10b981;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.message-body {
    color: #374151;
    font-size: 14px;
    line-height: 1.5;
}

.admin-reply-form {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 20px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #374151;
    font-size: 14px;
}

.form-group textarea,
.form-group select {
    width: 100%;
    padding: 12px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-family: inherit;
    font-size: 14px;
}

.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
    flex: 1;
}

.btn-success {
    background: #10b981;
    color: white;
}

.btn-success:hover {
    background: #059669;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5c636a;
}
</style>