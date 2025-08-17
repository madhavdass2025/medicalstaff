<?php
session_start();
// This is a placeholder for user authentication.
// In a real app, you'd have a proper role check.
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../includes/db.php';

// Fetch prescribed lab tests that are awaiting results
// We can identify these by checking if a result for the CLT_ID exists in lab_test_results
$stmt = $conn->prepare(
    "SELECT clt.CLT_ID, clt.CustomTestName, l.name as lab_test_name, p.petnam, p.RegNo
     FROM consultation_lab_tests clt
     JOIN consultations c ON clt.ConsultationID = c.ConsultationID
     JOIN registration p ON c.RegID = p.RegID
     LEFT JOIN laboratory l ON clt.LabTestID = l.Lid
     LEFT JOIN lab_test_results ltr ON clt.CLT_ID = ltr.CLT_ID
     WHERE ltr.ResultID IS NULL
     GROUP BY clt.CLT_ID
     ORDER BY c.ConsultationDate ASC"
);
$stmt->execute();
$pending_tests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Staff Dashboard</title>
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
        <h1>Medical Staff Dashboard</h1>
        <nav><a href="../logout.php">Logout</a></nav>
    </header>
    <div class="container">
        <h2>Pending Lab Test Results</h2>
        <table>
            <thead>
                <tr>
                    <th>Pet Name</th>
                    <th>Reg No</th>
                    <th>Test Name</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($pending_tests) > 0): ?>
                    <?php foreach ($pending_tests as $test): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($test['petnam']); ?></td>
                            <td><?php echo htmlspecialchars($test['RegNo']); ?></td>
                            <td><?php echo htmlspecialchars($test['lab_test_name'] ?: $test['CustomTestName']); ?></td>
                            <td>
                                <a href="enter_lab_results.php?clt_id=<?php echo $test['CLT_ID']; ?>">
                                    Enter Results
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">No pending lab tests.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
