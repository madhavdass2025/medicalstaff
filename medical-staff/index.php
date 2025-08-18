<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
require_once '../includes/db.php';

// Fetch consultations with pending or partial lab test results
$stmt = $conn->prepare(
    "SELECT
        c.ConsultationID,
        c.ConsultationDate,
        p.petnam,
        p.RegNo,
        COUNT(DISTINCT clt.CLT_ID) as total_tests,
        COUNT(DISTINCT ltr.CLT_ID) as completed_tests
     FROM consultations c
     JOIN registration p ON c.RegID = p.RegID
     JOIN consultation_lab_tests clt ON c.ConsultationID = clt.ConsultationID
     LEFT JOIN lab_test_results ltr ON clt.CLT_ID = ltr.CLT_ID AND ltr.is_deleted = 0
     GROUP BY c.ConsultationID
     HAVING total_tests > completed_tests
     ORDER BY c.ConsultationDate DESC"
);
$stmt->execute();
$pending_consultations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Staff Dashboard - Pending Tasks</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { display: block; }
        .container { width: 80%; margin: 2em auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.8em; text-align: left; border-bottom: 1px solid #ddd; }
    </style>
</head>
<body>
    <header>
        <h1>Pending Lab Results by Consultation</h1>
        <nav><a href="list_lab_results.php">All Completed Results</a> | <a href="../logout.php">Logout</a></nav>
    </header>
    <div class="container">
        <table>
            <thead>
                <tr>
                    <th>Consultation Date</th>
                    <th>Pet Name</th>
                    <th>Reg No</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($pending_consultations) > 0): ?>
                    <?php foreach ($pending_consultations as $con): ?>
                        <?php
                        $status = 'Pending';
                        if ($con['completed_tests'] > 0) {
                            $status = 'Partial';
                        }
                        ?>
                        <tr>
                            <td><?php echo date('d-m-Y', strtotime($con['ConsultationDate'])); ?></td>
                            <td><?php echo htmlspecialchars($con['petnam']); ?></td>
                            <td><?php echo htmlspecialchars($con['RegNo']); ?></td>
                            <td><?php echo $status; ?> (<?php echo $con['completed_tests']; ?>/<?php echo $con['total_tests']; ?>)</td>
                            <td>
                                <a href="consultation_tasks.php?consultation_id=<?php echo $con['ConsultationID']; ?>">
                                    View Tasks
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No consultations with pending lab tests.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
