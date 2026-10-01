<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin', 'Super Admin'], true)) {
    header('Location: ../login.php');
    exit();
}

if (!isset($pending_workers) || !($pending_workers instanceof mysqli_result)) {
    $pending_workers = mysqli_query($conn, "SELECT u.*, COALESCE(u.created_at, hw.created_at) AS registration_date
        FROM users u
        LEFT JOIN health_workers hw ON hw.email = u.email
        WHERE u.role='Admin' AND u.status='Pending'
        ORDER BY registration_date DESC");
}

$worker_redirect = basename($_SERVER['PHP_SELF']);
?>

<div class="table-container worker-verification-panel" id="pendingWorkersPad">
    <h3>Pending Staff Worker Accounts</h3>
    <table>
        <thead>
            <tr>
                <th>Worker</th>
                <th>Contact Details</th>
                <th>Registered</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($pending_workers && mysqli_num_rows($pending_workers) > 0): ?>
                <?php while ($worker = mysqli_fetch_assoc($pending_workers)): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars(trim($worker['first_name'] . ' ' . $worker['last_name'])); ?></strong><br>
                            <small><?php echo htmlspecialchars($worker['generated_id'] ?? 'No worker ID'); ?></small>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($worker['email']); ?><br>
                            <small><?php echo htmlspecialchars($worker['contact_number'] ?? 'No contact number'); ?></small>
                        </td>
                        <td><?php echo !empty($worker['registration_date']) ? date('M d, Y', strtotime($worker['registration_date'])) : 'N/A'; ?></td>
                        <td>
                            <button type="button" class="btn-approve" onclick='openWorkerReview(<?php echo htmlspecialchars(json_encode($worker), ENT_QUOTES, "UTF-8"); ?>)'>REVIEW</button>
                            <a href="process_verification.php?remove_worker_id=<?php echo (int) $worker['id']; ?>&redirect=<?php echo urlencode($worker_redirect); ?>" class="btn-reject" onclick="return confirm('Reject this worker?')">REJECT</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4" align="center">No pending worker accounts.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="worker-review-modal" id="workerReviewModal" style="display:none;">
    <div class="worker-review-card">
        <button type="button" class="worker-review-close" onclick="closeWorkerReview()">&times;</button>
        <h2>Staff Worker Review</h2>
        <p class="worker-review-subtitle">Review the staff details before approving the account.</p>
        <div class="worker-review-grid">
            <div><strong>Full Name</strong><span id="reviewWorkerName"></span></div>
            <div><strong>Worker ID</strong><span id="reviewWorkerId"></span></div>
            <div><strong>Email</strong><span id="reviewWorkerEmail"></span></div>
            <div><strong>Contact Number</strong><span id="reviewWorkerContact"></span></div>
            <div class="worker-review-wide"><strong>Address</strong><span id="reviewWorkerAddress"></span></div>
            <div><strong>Date Registered</strong><span id="reviewWorkerDate"></span></div>
            <div><strong>Status</strong><span id="reviewWorkerStatus"></span></div>
        </div>
        <div class="worker-review-actions">
            <button type="button" class="btn-review-cancel" onclick="closeWorkerReview()">CANCEL</button>
            <a id="reviewWorkerReject" class="btn-reject" href="#" onclick="return confirm('Reject this worker?')">REJECT</a>
            <a id="reviewWorkerApprove" class="btn-approve" href="#">APPROVE</a>
        </div>
    </div>
</div>

<style>
.worker-review-modal { position: fixed; inset: 0; z-index: 5000; display: flex; background: rgba(26, 32, 44, 0.52); align-items: center; justify-content: center; padding: 20px; }
.worker-review-card { position: relative; width: min(650px, 100%); max-height: min(700px, 92vh); overflow-y: auto; background: #fff; border-radius: 14px; padding: 30px; box-shadow: 0 24px 60px rgba(0,0,0,0.22); box-sizing: border-box; border-top: 5px solid #8DAE74; }
.worker-review-card h2 { margin: 0; color: #465736; font-size: 1.35rem; letter-spacing: 0; }
.worker-review-subtitle { margin: 7px 0 24px; color: #718096; font-size: 0.88rem; }
.worker-review-close { position: absolute; top: 14px; right: 16px; width: 32px; height: 32px; border: 1px solid #E2E8F0; border-radius: 50%; background: #fff; color: #718096; font-size: 1.25rem; line-height: 1; cursor: pointer; }
.worker-review-close:hover { background: #F1F5ED; color: #465736; }
.worker-review-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.worker-review-grid div { background: #FBFCFA; border: 1px solid #E3EBDD; border-radius: 8px; padding: 13px 14px; min-width: 0; }
.worker-review-grid .worker-review-wide { grid-column: 1 / -1; }
.worker-review-grid strong, .worker-review-grid span { display: block; }
.worker-review-grid strong { color: #8A9681; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 5px; }
.worker-review-grid span { color: #2D3748; font-size: 0.9rem; line-height: 1.35; overflow-wrap: anywhere; }
.worker-review-actions { display: flex; justify-content: flex-end; align-items: center; gap: 9px; margin-top: 26px; padding-top: 18px; border-top: 1px solid #EDF2F7; }
.worker-review-actions .btn-approve, .worker-review-actions .btn-reject { margin: 0; padding: 9px 16px; border-radius: 6px; font-size: 0.72rem; }
.btn-review-cancel { border: 1px solid #CBD5E0; background: #fff; color: #667085; padding: 8px 14px; border-radius: 6px; cursor: pointer; font-size: 0.72rem; font-weight: bold; }
.btn-review-cancel:hover { background: #F8FAFC; }
@media (max-width: 560px) { .worker-review-card { padding: 24px 18px 18px; } .worker-review-grid { grid-template-columns: 1fr; } .worker-review-grid .worker-review-wide { grid-column: auto; } .worker-review-actions { flex-wrap: wrap; } .worker-review-actions .btn-review-cancel { margin-right: auto; } }
</style>

<script>
function openWorkerReview(worker) {
    const modal = document.getElementById('workerReviewModal');
    document.getElementById('reviewWorkerName').textContent = `${worker.first_name || ''} ${worker.last_name || ''}`.trim();
    document.getElementById('reviewWorkerId').textContent = worker.generated_id || 'N/A';
    document.getElementById('reviewWorkerEmail').textContent = worker.email || 'N/A';
    document.getElementById('reviewWorkerContact').textContent = worker.contact_number || 'N/A';
    document.getElementById('reviewWorkerAddress').textContent = worker.address || 'N/A';
    document.getElementById('reviewWorkerDate').textContent = worker.registration_date || 'N/A';
    document.getElementById('reviewWorkerStatus').textContent = worker.status || 'Pending';
    const redirect = encodeURIComponent('<?php echo $worker_redirect; ?>');
    document.getElementById('reviewWorkerApprove').href = `process_verification.php?approve_worker_id=${worker.id}&redirect=${redirect}`;
    document.getElementById('reviewWorkerReject').href = `process_verification.php?remove_worker_id=${worker.id}&redirect=${redirect}`;
    modal.style.display = 'flex';
}

function closeWorkerReview() {
    document.getElementById('workerReviewModal').style.display = 'none';
}
</script>