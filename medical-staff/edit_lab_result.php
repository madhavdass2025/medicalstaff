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
    "SELECT clt.*, l.name as lab_test_name, p.petnam
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

// Fetch results to pre-fill the form
$stmt = $conn->prepare(
    "SELECT ltr.*, lts.SubCategoryName, lts.Unit, lts.ReferenceRange
     FROM lab_test_results ltr
     LEFT JOIN lab_test_subcategories lts ON ltr.SubCategoryID = lts.SubCategoryID
     WHERE ltr.CLT_ID = ? AND ltr.is_deleted = 0"
);
$stmt->bind_param("i", $clt_id);
$stmt->execute();
$results_to_edit = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_results'])) {
    $remarks = $_POST['remarks'];

    foreach ($_POST['result_value'] as $result_id => $result_value) {
        $stmt = $conn->prepare(
            "UPDATE lab_test_results SET ResultValue = ?, Remarks = ? WHERE ResultID = ?"
        );
        $stmt->bind_param("ssi", $result_value, $remarks, $result_id);
        $stmt->execute();
    }
    header("Location: list_lab_results.php?status=updated");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Lab Result</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { display: block; }
        .container { width: 80%; margin: 2em auto; }
        .form-group { margin-bottom: 1em; }
    </style>
</head>
<body>
    <header>
        <h1>Edit Lab Result</h1>
        <nav><a href="list_lab_results.php">Back to List</a> | <a href="../logout.php">Logout</a></nav>
    </header>
    <div class="container">
        <h3>Test for <?php echo htmlspecialchars($test_info['petnam']); ?></h3>
        <h2><?php echo htmlspecialchars($test_info['lab_test_name'] ?: $test_info['CustomTestName']); ?></h2>

        <form action="" method="POST">
            <?php if (count($results_to_edit) > 0): ?>
                <?php foreach ($results_to_edit as $result): ?>
                    <div class="form-group">
                        <label for="result_<?php echo $result['ResultID']; ?>">
                            <?php echo htmlspecialchars($result['SubCategoryName'] ?: 'Result'); ?>
                             (<?php echo htmlspecialchars($result['Unit'] ?? 'N/A'); ?>)
                            <small>[Ref: <?php echo htmlspecialchars($result['ReferenceRange'] ?? 'N/A'); ?>]</small>
                        </label>
                        <input type="text" id="result_<?php echo $result['ResultID']; ?>"
                               name="result_value[<?php echo $result['ResultID']; ?>]"
                               value="<?php echo htmlspecialchars($result['ResultValue']); ?>" required>
                    </div>
                <?php endforeach; ?>
                <div class="form-group">
                    <label for="remarks">Remarks</label>
                    <textarea id="remarks" name="remarks"><?php echo htmlspecialchars($results_to_edit[0]['Remarks'] ?? ''); ?></textarea>
                </div>
                <button type="submit" name="update_results">Update Results</button>
            <?php else: ?>
                <p>No results found to edit.</p>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
