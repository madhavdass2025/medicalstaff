<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
require_once '../includes/db.php';

// Handle soft delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['clt_id'])) {
    $clt_id_to_delete = $_GET['clt_id'];
    $stmt = $conn->prepare("UPDATE lab_test_results SET is_deleted = 1 WHERE CLT_ID = ?");
    $stmt->bind_param("i", $clt_id_to_delete);
    if ($stmt->execute()) {
        header("Location: list_lab_results.php?status=deleted");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
    $stmt->close();
}

// Fetch all completed lab tests
$stmt = $conn->prepare(
    "SELECT
        clt.CLT_ID,
        p.petnam,
        p.RegNo,
        l.name as lab_test_name,
        clt.CustomTestName,
        MAX(ltr.EnteredAt) as result_date
     FROM consultation_lab_tests clt
     JOIN consultations c ON clt.ConsultationID = c.ConsultationID
     JOIN registration p ON c.RegID = p.RegID
     JOIN lab_test_results ltr ON clt.CLT_ID = ltr.CLT_ID
     LEFT JOIN laboratory l ON clt.LabTestID = l.Lid
     WHERE ltr.is_deleted = 0
     GROUP BY clt.CLT_ID
     ORDER BY result_date DESC"
);
$stmt->execute();
$completed_tests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completed Lab Results</title>
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
        <h1>Completed Lab Results</h1>
        <nav><a href="index.php">Pending Tests</a> | <a href="../logout.php">Logout</a></nav>
    </header>
    <div class="container">
        <table>
            <thead>
                <tr>
                    <th>Result Date</th>
                    <th>Pet Name</th>
                    <th>Test Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($completed_tests) > 0): ?>
                    <?php foreach ($completed_tests as $test): ?>
                        <tr>
                            <td><?php echo date('d-m-Y H:i', strtotime($test['result_date'])); ?></td>
                            <td><?php echo htmlspecialchars($test['petnam']); ?></td>
                            <td><?php echo htmlspecialchars($test['lab_test_name'] ?: $test['CustomTestName']); ?></td>
                            <td>
                                <a href="view_lab_result.php?clt_id=<?php echo $test['CLT_ID']; ?>">View</a> |
                                <a href="edit_lab_result.php?clt_id=<?php echo $test['CLT_ID']; ?>">Edit</a> |
                                <a href="?action=delete&clt_id=<?php echo $test['CLT_ID']; ?>" onclick="return confirm('Are you sure you want to delete this result?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">No completed lab results found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
