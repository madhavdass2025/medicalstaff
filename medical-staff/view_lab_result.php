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
    "SELECT clt.*, c.ConsultationID, l.name as lab_test_name, p.petnam
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Lab Result</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { display: block; }
        .container { width: 80%; margin: 2em auto; }
        .result-section { margin-bottom: 1.5em; }
    </style>
</head>
<body>
    <header>
        <h1>View Lab Result</h1>
        <nav><a href="list_lab_results.php">Back to List</a> | <a href="../logout.php">Logout</a></nav>
    </header>
    <div class="container">
        <a href="print_results.php?consultation_id=<?php echo $test_info['ConsultationID']; ?>" target="_blank" class="print-button no-print" style="padding: 10px; background: #5cb85c; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin-bottom: 20px;">Print All Results for this Consultation</a>
        <a href="print_single_test.php?clt_id=<?php echo $clt_id; ?>" target="_blank" class="print-button no-print" style="padding: 10px; background: #4cae4c; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin-bottom: 20px;">Print This Test</a>
        <h3>Test for <?php echo htmlspecialchars($test_info['petnam']); ?></h3>
        <h2><?php echo htmlspecialchars($test_info['lab_test_name'] ?: $test_info['CustomTestName']); ?></h2>

        <div class="result-section">
            <h4>Results</h4>
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
    </div>
</body>
</html>
