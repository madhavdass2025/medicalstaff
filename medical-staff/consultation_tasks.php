<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
require_once '../includes/db.php';

if (!isset($_GET['consultation_id']) || !is_numeric($_GET['consultation_id'])) {
    header("Location: index.php");
    exit();
}

$consultation_id = $_GET['consultation_id'];

// Fetch consultation details
$stmt = $conn->prepare(
    "SELECT c.*, p.petnam, p.RegNo
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
    "SELECT clt.*, l.name as lab_test_name, ltr.ResultID
     FROM consultation_lab_tests clt
     LEFT JOIN laboratory l ON clt.LabTestID = l.Lid
     LEFT JOIN lab_test_results ltr ON clt.CLT_ID = ltr.CLT_ID AND ltr.is_deleted = 0
     WHERE clt.ConsultationID = ?
     GROUP BY clt.CLT_ID"
);
$stmt->bind_param("i", $consultation_id);
$stmt->execute();
$lab_tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation Tasks</title>
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
        <h1>Lab Test Tasks for Consultation #<?php echo $consultation_id; ?></h1>
        <nav><a href="index.php">Back to Pending List</a> | <a href="../logout.php">Logout</a></nav>
    </header>
    <div class="container">
        <h3>Patient: <?php echo htmlspecialchars($consultation_info['petnam']); ?> (Reg No: <?php echo htmlspecialchars($consultation_info['RegNo']); ?>)</h3>
        <a href="print_results.php?consultation_id=<?php echo $consultation_id; ?>" target="_blank">Print All Results</a>

        <table style="margin-top: 2em;">
            <thead>
                <tr>
                    <th>Test Name</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($lab_tasks) > 0): ?>
                    <?php foreach ($lab_tasks as $task): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($task['lab_test_name'] ?: $task['CustomTestName']); ?></td>
                            <td>
                                <?php if ($task['ResultID']): ?>
                                    <span style="color: green;">Result Entered</span>
                                <?php else: ?>
                                    <span style="color: orange;">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($task['ResultID']): ?>
                                    <a href="view_lab_result.php?clt_id=<?php echo $task['CLT_ID']; ?>">View</a> /
                                    <a href="edit_lab_result.php?clt_id=<?php echo $task['CLT_ID']; ?>">Edit</a>
                                <?php else: ?>
                                    <a href="enter_lab_results.php?clt_id=<?php echo $task['CLT_ID']; ?>">Enter Result</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">No lab tests prescribed for this consultation.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
