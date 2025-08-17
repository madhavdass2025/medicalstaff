<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
require_once '../includes/db.php';

if (!isset($_GET['clt_id']) || !is_numeric($_GET['clt_id'])) {
    header("Location: index.php");
    exit();
}

$clt_id = $_GET['clt_id'];

// Fetch prescribed test details
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

// Fetch sub-categories for this lab test, if any
$subcategories = [];
if ($test_info['LabTestID']) {
    $stmt = $conn->prepare("SELECT * FROM lab_test_subcategories WHERE LabTestID = ?");
    $stmt->bind_param("i", $test_info['LabTestID']);
    $stmt->execute();
    $subcategories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_results'])) {
    $remarks = $_POST['remarks'];
    $entered_by = $_SESSION['user_id'];

    if (count($subcategories) > 0) {
        // Handle results for tests with sub-categories
        foreach ($subcategories as $sub) {
            $result_value = $_POST['result_value'][$sub['SubCategoryID']];
            $stmt = $conn->prepare(
                "INSERT INTO lab_test_results (CLT_ID, SubCategoryID, ResultValue, Remarks, EnteredBy) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("iissi", $clt_id, $sub['SubCategoryID'], $result_value, $remarks, $entered_by);
            $stmt->execute();
        }
    } else {
        // Handle result for simple test
        $result_value = $_POST['result_value'];
        $stmt = $conn->prepare(
            "INSERT INTO lab_test_results (CLT_ID, ResultValue, Remarks, EnteredBy) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("issi", $clt_id, $result_value, $remarks, $entered_by);
        $stmt->execute();
    }
    header("Location: index.php?status=results_saved");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Lab Results</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { display: block; }
        .container { width: 80%; margin: 2em auto; }
        .form-group { margin-bottom: 1em; }
    </style>
</head>
<body>
    <header>
        <h1>Enter Lab Results</h1>
        <nav><a href="index.php">Back to List</a> | <a href="../logout.php">Logout</a></nav>
    </header>
    <div class="container">
        <h3>Test for <?php echo htmlspecialchars($test_info['petnam']); ?></h3>
        <h2><?php echo htmlspecialchars($test_info['lab_test_name'] ?: $test_info['CustomTestName']); ?></h2>

        <form action="" method="POST">
            <?php if (count($subcategories) > 0): ?>
                <h4>Sub-Categories</h4>
                <?php foreach ($subcategories as $sub): ?>
                    <div class="form-group">
                        <label for="result_<?php echo $sub['SubCategoryID']; ?>">
                            <?php echo htmlspecialchars($sub['SubCategoryName']); ?>
                            (<?php echo htmlspecialchars($sub['Unit'] ?? 'N/A'); ?>)
                            <small>[Ref: <?php echo htmlspecialchars($sub['ReferenceRange'] ?? 'N/A'); ?>]</small>
                        </label>
                        <input type="text" id="result_<?php echo $sub['SubCategoryID']; ?>"
                               name="result_value[<?php echo $sub['SubCategoryID']; ?>]" required>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                 <div class="form-group">
                    <label for="result_value">Result</label>
                    <input type="text" id="result_value" name="result_value" required>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="remarks">Remarks</label>
                <textarea id="remarks" name="remarks"></textarea>
            </div>

            <button type="submit" name="save_results">Save Results</button>
        </form>
    </div>
</body>
</html>
