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
    LEFT JOIN billlaboratory bl ON clt.LabTestID = bl.labID AND p.RegNo = bl.regID
    LEFT JOIN lab_test_results ltr ON clt.CLT_ID = ltr.CLT_ID AND ltr.is_deleted = 0
    GROUP BY clt.CLT_ID
    ORDER BY c.ConsultationDate DESC, c.ConsultationID
";

$result = $conn->query($query);
$all_tests = $result->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Test Status Overview</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <style>
        body { display: block; }
        .container { width: 90%; margin: 2em auto; }
    </style>
</head>
<body>
    <header>
        <h1>Complete Consultation & Lab Status List</h1>
        <nav><a href="index.php">Main Dashboard</a> | <a href="../logout.php">Logout</a></nav>
    </header>
    <div class="container">
        <table id="lab_status_table" class="display">
            <thead>
                <tr>
                    <th>Consultation ID</th>
                    <th>Date</th>
                    <th>Reg No</th>
                    <th>Pet Name</th>
                    <th>Test Name</th>
                    <th>Payment Status</th>
                    <th>Sample Status</th>
                    <th>Result Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_tests as $test): ?>
                    <tr>
                        <td><?php echo $test['ConsultationID']; ?></td>
                        <td><?php echo date('d-m-Y', strtotime($test['ConsultationDate'])); ?></td>
                        <td><?php echo htmlspecialchars($test['RegNo']); ?></td>
                        <td><?php echo htmlspecialchars($test['petnam']); ?></td>
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

    <script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <script>
    $(document).ready( function () {
        $('#lab_status_table').DataTable();
    } );
    </script>
</body>
</html>
