<?php
include '../includes/header_frontdesk.php';

if (!isset($_GET['consultation_id']) || !is_numeric($_GET['consultation_id'])) {
    header("Location: index.php");
    exit();
}

$consultation_id = $_GET['consultation_id'];

// Fetch consultation details
$stmt = $conn->prepare(
    "SELECT c.id, c.RegNo, c.ConDt, p.petnam, p.ownnam
     FROM consultation c
     JOIN registration p ON c.RegNo = p.RegNo
     WHERE c.id = ?"
);
$stmt->bind_param("i", $consultation_id);
$stmt->execute();
$consultation_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$consultation_info) {
    die("Consultation not found.");
}

// Find the casesheetbillid for this consultation
$stmt = $conn->prepare("SELECT casesheetbillid FROM casesheetbill WHERE RegNo = ? AND BillDate = ?");
$stmt->bind_param("ss", $consultation_info['RegNo'], $consultation_info['ConDt']);
$stmt->execute();
$casesheet = $stmt->get_result()->fetch_assoc();
$stmt->close();

$casesheet_id = $casesheet['casesheetbillid'] ?? null;
$prescribed_lab_tests = [];
$total_lab_fees = 0;

if ($casesheet_id) {
    // Fetch prescribed lab tests from the casesheet
    $stmt = $conn->prepare(
        "SELECT csbl.*, l.amount
         FROM casesheetbilllaboratory csbl
         JOIN laboratory l ON csbl.labID = l.Lid
         WHERE csbl.billID = ?"
    );
    $stmt->bind_param("i", $casesheet_id);
    $stmt->execute();
    $prescribed_lab_tests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach($prescribed_lab_tests as $test) {
        $total_lab_fees += (float)$test['amount'];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_bill'])) {
    // 1. Create a new bill in the `bill` table
    $bill_date = date('Y-m-d');
    $reg_no = $consultation_info['RegNo'];
    $total_bill = $_POST['total_bill']; // This will come from the form

    $stmt = $conn->prepare("INSERT INTO bill (BillDate, RegNo, Laboratory, TotalBill, netAmount, SubmitBy) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssddss", $bill_date, $reg_no, $total_lab_fees, $total_bill, $total_bill, $_SESSION['username']);
    $stmt->execute();
    $new_bill_id = $stmt->insert_id;
    $stmt->close();

    // 2. Insert into `billlaboratory` for each lab test
    if ($new_bill_id) {
        foreach ($prescribed_lab_tests as $test) {
            $stmt = $conn->prepare(
                "INSERT INTO billlaboratory (billID, billDate, regID, labID, labName, labAmount, submittedBy) VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("isssiss", $new_bill_id, $bill_date, $reg_no, $test['labID'], $test['labName'], $test['amount'], $_SESSION['username']);
            $stmt->execute();
        }
        header("Location: index.php?status=bill_created");
        exit();
    }
}

?>
<h1>Generate Bill</h1>
<h3>Patient: <?php echo htmlspecialchars($consultation_info['petnam']); ?> (Reg No: <?php echo htmlspecialchars($consultation_info['RegNo']); ?>)</h3>

<form action="" method="POST">
    <div class="billing-section">
        <h4>Laboratory Tests</h4>
        <?php if (count($prescribed_lab_tests) > 0): ?>
            <ul>
                <?php foreach ($prescribed_lab_tests as $test): ?>
                    <li><?php echo htmlspecialchars($test['labName']); ?> - Rs. <?php echo htmlspecialchars($test['amount']); ?></li>
                <?php endforeach; ?>
            </ul>
            <p><strong>Lab Total: Rs. <?php echo $total_lab_fees; ?></strong></p>
        <?php else: ?>
            <p>No lab tests prescribed.</p>
        <?php endif; ?>
    </div>

    <!-- Other billing sections (medicines, etc.) will go here -->

    <hr>
    <h3>Total Bill: Rs. <?php echo $total_lab_fees; ?></h3>
    <input type="hidden" name="total_bill" value="<?php echo $total_lab_fees; ?>">

    <button type="submit" name="save_bill">Save & Generate Bill</button>
</form>

<?php include '../includes/footer.php'; ?>
