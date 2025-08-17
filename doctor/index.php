<?php
// Note: The header file already starts the session and includes db.php
include '../includes/header.php';

// Fetch today's consultations
$today = date('Y-m-d');
$stmt = $conn->prepare(
    "SELECT cb.BookingID, cb.Status, r.petnam, r.RegNo
     FROM consultation_booking cb
     JOIN registration r ON cb.RegID = r.RegID
     WHERE cb.BookingDate = ?
     ORDER BY cb.created_at ASC"
);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
$consultations = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<h2>Today's Consultations (<?php echo date('d-m-Y'); ?>)</h2>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Reg No</th>
            <th>Pet Name</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($consultations) > 0): ?>
            <?php foreach ($consultations as $index => $consultation): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($consultation['RegNo']); ?></td>
                    <td><?php echo htmlspecialchars($consultation['petnam']); ?></td>
                    <td>
                        <span class="status-<?php echo str_replace(' ', '-', strtolower($consultation['Status'])); ?>">
                            <?php echo htmlspecialchars($consultation['Status']); ?>
                        </span>
                    </td>
                    <td>
                        <a href="consultation.php?booking_id=<?php echo $consultation['BookingID']; ?>">
                            Start Consultation
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="5">No consultations scheduled for today.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php include '../includes/footer.php'; ?>
