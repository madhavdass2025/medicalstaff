<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
require_once '../includes/db.php';

if (!isset($_GET['consultation_id']) || !is_numeric($_GET['consultation_id'])) {
    die("Invalid Consultation ID.");
}

$consultation_id = $_GET['consultation_id'];

// Fetch consultation details
$stmt = $conn->prepare(
    "SELECT c.*, p.petnam, p.RegNo, p.ownnam, p.ownmob
     FROM consultations c
     JOIN registration p ON c.RegID = p.RegID
     WHERE c.ConsultationID = ?"
);
$stmt->bind_param("i", $consultation_id);
$stmt->execute();
$consultation_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$consultation_info) {
    die("Consultation not found.");
}

// Fetch all lab tests for this consultation
$stmt = $conn->prepare(
    "SELECT clt.*, l.name as lab_test_name
     FROM consultation_lab_tests clt
     LEFT JOIN laboratory l ON clt.LabTestID = l.Lid
     WHERE clt.ConsultationID = ?"
);
$stmt->bind_param("i", $consultation_id);
$stmt->execute();
$lab_tests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Lab Results for Consultation #<?php echo $consultation_id; ?></title>
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
        <a href="list_lab_results.php" class="no-print">Back to List</a>

        <h1>Lab Results</h1>
        <p><strong>Consultation ID:</strong> <?php echo $consultation_id; ?></p>
        <p><strong>Date:</strong> <?php echo date('d-m-Y', strtotime($consultation_info['ConsultationDate'])); ?></p>
        <p><strong>Patient:</strong> <?php echo htmlspecialchars($consultation_info['petnam']); ?> (Reg No: <?php echo htmlspecialchars($consultation_info['RegNo']); ?>)</p>
        <p><strong>Owner:</strong> <?php echo htmlspecialchars($consultation_info['ownnam']); ?> (Mobile: <?php echo htmlspecialchars($consultation_info['ownmob']); ?>)</p>

        <hr>

        <?php foreach ($lab_tests as $test): ?>
            <h3><?php echo htmlspecialchars($test['lab_test_name'] ?: $test['CustomTestName']); ?></h3>
            <?php
            // Fetch results for this specific test
            $results_stmt = $conn->prepare(
                "SELECT ltr.*, lts.SubCategoryName, lts.Unit, lts.ReferenceRange
                 FROM lab_test_results ltr
                 LEFT JOIN lab_test_subcategories lts ON ltr.SubCategoryID = lts.SubCategoryID
                 WHERE ltr.CLT_ID = ? AND ltr.is_deleted = 0"
            );
            $results_stmt->bind_param("i", $test['CLT_ID']);
            $results_stmt->execute();
            $results = $results_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $results_stmt->close();
            ?>
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
                 <?php if (!empty($results[0]['Remarks'])): ?>
                    <p><strong>Remarks:</strong> <?php echo nl2br(htmlspecialchars($results[0]['Remarks'])); ?></p>
                <?php endif; ?>
            <?php else: ?>
                <p>No results entered for this test.</p>
            <?php endif; ?>
            <hr>
        <?php endforeach; ?>
    </div>
</body>
</html>
