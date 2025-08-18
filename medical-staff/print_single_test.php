<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
require_once '../includes/db.php';

if (!isset($_GET['clt_id']) || !is_numeric($_GET['clt_id'])) {
    header("Location: list_lab_results.php");
    exit();
}

$clt_id = $_GET['clt_id'];

// Fetch main test info
$stmt = $conn->prepare(
    "SELECT clt.*, c.ConsultationDate, l.name as lab_test_name, p.petnam, p.RegNo, p.ownnam
     FROM consultation_lab_tests clt
     JOIN consultations c ON clt.ConsultationID = c.ConsultationID
     JOIN registration p ON c.RegID = p.RegID
     LEFT JOIN laboratory l ON clt.LabTestID = l.Lid
     WHERE clt.CLT_ID = ?"
);
$stmt->bind_param("i", $clt_id);
$stmt->execute();
$test_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$test_info) {
    die("Test not found.");
}

// Fetch results
$stmt = $conn->prepare(
    "SELECT ltr.*, lts.SubCategoryName, lts.Unit, lts.ReferenceRange, au.username as entered_by_username
     FROM lab_test_results ltr
     LEFT JOIN lab_test_subcategories lts ON ltr.SubCategoryID = lts.SubCategoryID
     LEFT JOIN admin_user au ON ltr.EnteredBy = au.id
     WHERE ltr.CLT_ID = ? AND ltr.is_deleted = 0"
);
$stmt->bind_param("i", $clt_id);
$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Lab Report</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/print.css" media="print">
    <style>
        body { display: block; }
        .container { width: 80%; margin: 2em auto; }
        .print-button { padding: 10px; background: #5cb85c; color: white; border: none; border-radius: 5px; cursor: pointer; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <button class="print-button" onclick="window.print();">Print this page</button>
        <a href="view_lab_result.php?clt_id=<?php echo $clt_id; ?>" class="no-print">Back to View</a>

        <h1>Lab Report</h1>
        <p><strong>Date:</strong> <?php echo date('d-m-Y', strtotime($test_info['ConsultationDate'])); ?></p>
        <p><strong>Patient:</strong> <?php echo htmlspecialchars($test_info['petnam']); ?> (Reg No: <?php echo htmlspecialchars($test_info['RegNo']); ?>)</p>
        <p><strong>Owner:</strong> <?php echo htmlspecialchars($test_info['ownnam']); ?></p>
        <hr>
        <h3>Test: <?php echo htmlspecialchars($test_info['lab_test_name'] ?: $test_info['CustomTestName']); ?></h3>

        <?php if (count($results) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Parameter</th>
                        <th>Result</th>
                        <th>Unit</th>
                        <th>Reference Range</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($results as $result): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($result['SubCategoryName'] ?: 'Result'); ?></td>
                        <td><?php echo htmlspecialchars($result['ResultValue']); ?></td>
                        <td><?php echo htmlspecialchars($result['Unit'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($result['ReferenceRange'] ?? 'N/A'); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p>
                <strong>Entered By:</strong> <?php echo htmlspecialchars($results[0]['entered_by_username'] ?? 'N/A'); ?>
                on <?php echo date('d-m-Y H:i', strtotime($results[0]['EnteredAt'])); ?>
            </p>
            <?php if (!empty($results[0]['Remarks'])): ?>
                <p><strong>Remarks:</strong> <?php echo nl2br(htmlspecialchars($results[0]['Remarks'])); ?></p>
            <?php endif; ?>
        <?php else: ?>
            <p>No results found for this test.</p>
        <?php endif; ?>
    </div>
</body>
</html>
