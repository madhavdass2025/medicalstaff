<?php
include '../includes/header_frontdesk.php';

// Fetch all non-billed consultations
$stmt = $conn->prepare(
    "SELECT
        c.id as consultation_id,
        c.ConDt,
        p.petnam,
        p.RegNo
     FROM consultation c
     JOIN registration p ON c.RegNo = p.RegNo
     LEFT JOIN bill b ON c.RegNo = b.RegNo AND c.ConDt = b.BillDate
     WHERE c.cancel = '0' AND b.BillNo IS NULL
     ORDER BY c.ConDt DESC"
);
$stmt->execute();
$pending_bills = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>
<h1>Patients Ready for Billing</h1>

<table>
    <thead>
        <tr>
            <th>Consultation Date</th>
            <th>Reg No</th>
            <th>Pet Name</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($pending_bills) > 0): ?>
            <?php foreach ($pending_bills as $consultation): ?>
                <tr>
                    <td><?php echo date('d-m-Y', strtotime($consultation['ConDt'])); ?></td>
                    <td><?php echo htmlspecialchars($consultation['RegNo']); ?></td>
                    <td><?php echo htmlspecialchars($consultation['petnam']); ?></td>
                    <td>
                        <a href="create_bill.php?consultation_id=<?php echo $consultation['consultation_id']; ?>">
                            Generate Bill
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="4">No patients waiting for billing.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php include '../includes/footer.php'; ?>
