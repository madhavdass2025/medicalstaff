<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
require_once '../includes/db.php';

// Handle marking sample as collected
if (isset($_GET['action']) && $_GET['action'] == 'collect_sample' && isset($_GET['clt_id'])) {
    $clt_id_to_update = $_GET['clt_id'];
    $stmt = $conn->prepare("UPDATE consultation_lab_tests SET SampleCollected = 1 WHERE CLT_ID = ?");
    $stmt->bind_param("i", $clt_id_to_update);
    $stmt->execute();
    header("Location: lab_status_overview.php?status=sample_collected");
    exit();
}

// Fetch all prescribed lab tests, grouped by consultation
// This is a complex query to get all statuses in one go.
// Note: This assumes a 'Paid' status in the `bill` table indicates payment.
$query = "
    SELECT
        c.ConsultationID,
        c.ConsultationDate,
        p.petnam,
        p.RegNo,
        clt.CLT_ID,
        l.name as lab_test_name,
        clt.CustomTestName,
        clt.SampleCollected,
        (CASE WHEN bl.billID IS NOT NULL THEN 'Paid' ELSE 'Not Paid' END) as PaymentStatus,
        (CASE WHEN ltr.ResultID IS NOT NULL THEN 'Result Entered' ELSE 'Pending' END) as ResultStatus
    FROM consultations c
    JOIN registration p ON c.RegID = p.RegID
    JOIN consultation_lab_tests clt ON c.ConsultationID = clt.ConsultationID
    LEFT JOIN laboratory l ON clt.LabTestID = l.Lid
    LEFT JOIN billlaboratory bl ON clt.LabTestID = bl.labID AND p.RegNo = bl.regID -- This join is an assumption
    LEFT JOIN lab_test_results ltr ON clt.CLT_ID = ltr.CLT_ID AND ltr.is_deleted = 0
    ORDER BY c.ConsultationDate DESC, c.ConsultationID, clt.CLT_ID
";

$result = $conn->query($query);
$all_tests = $result->fetch_all(MYSQLI_ASSOC);

// Group tests by consultation
$consultations = [];
foreach ($all_tests as $test) {
    $consultations[$test['ConsultationID']]['details'] = [
        'ConsultationDate' => $test['ConsultationDate'],
        'petnam' => $test['petnam'],
        'RegNo' => $test['RegNo']
    ];
    $consultations[$test['ConsultationID']]['tests'][] = $test;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Test Status Overview</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { display: block; }
        .container { width: 90%; margin: 2em auto; }
        .consultation-group { border: 1px solid #ccc; padding: 1em; margin-bottom: 1.5em; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.8em; text-align: left; border-bottom: 1px solid #ddd; }
    </style>
</head>
<body>
    <header>
        <h1>Lab Test Status Overview</h1>
        <nav><a href="index.php">Pending Tasks</a> | <a href="../logout.php">Logout</a></nav>
    </header>
    <div class="container">
        <?php if (count($consultations) > 0): ?>
            <?php foreach ($consultations as $cid => $data): ?>
                <div class="consultation-group">
                    <h3>
                        Consultation #<?php echo $cid; ?>
                        (<?php echo date('d-m-Y', strtotime($data['details']['ConsultationDate'])); ?>) -
                        <?php echo htmlspecialchars($data['details']['petnam']); ?>
                    </h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Test Name</th>
                                <th>Payment Status</th>
                                <th>Sample Status</th>
                                <th>Result Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['tests'] as $test): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($test['lab_test_name'] ?: $test['CustomTestName']); ?></td>
                                    <td><?php echo $test['PaymentStatus']; ?></td>
                                    <td>
                                        <?php if ($test['SampleCollected']): ?>
                                            <span style="color: green;">Collected</span>
                                        <?php else: ?>
                                            <a href="?action=collect_sample&clt_id=<?php echo $test['CLT_ID']; ?>">Mark as Collected</a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $test['ResultStatus']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No lab tests found.</p>
        <?php endif; ?>
    </div>
</body>
</html>
