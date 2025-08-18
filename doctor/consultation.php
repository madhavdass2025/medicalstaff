<?php
session_start();
// Use the doctor ID from the session
if (!isset($_SESSION['did'])) {
    $_SESSION['did'] = 1; // For testing purposes if session is not set
}
$doctor_id = $_SESSION["did"];

require_once '../includes/db_connect.php';

// --- Data Fetching ---
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
if ($booking_id <= 0) {
    die("Invalid booking ID.");
}

// Fetch Current Patient Details
$sql_patient = "SELECT r.*, cb.Status FROM registration r JOIN consultation_booking cb ON r.RegID = cb.RegID WHERE cb.BookingID = ? AND cb.DoctorID = ?";
$stmt_patient = $conn->prepare($sql_patient);
$stmt_patient->bind_param("ii", $booking_id, $doctor_id);
$stmt_patient->execute();
$patient = $stmt_patient->get_result()->fetch_assoc();
$stmt_patient->close();

if (!$patient) {
    die("Appointment not found or you do not have permission to view it.");
}
$reg_id = $patient['RegID'];

// Fetch Master Data for Dropdowns
$medicines = $conn->query("SELECT Mid, name FROM medicines WHERE status = 'available' ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$vaccines = $conn->query("SELECT VId, name FROM vaccination ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$lab_tests = $conn->query("SELECT Lid, name FROM laboratory ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$surgeries = $conn->query("SELECT surgeryID, surName FROM surgery ORDER BY surName")->fetch_all(MYSQLI_ASSOC);
$scans = $conn->query("SELECT sID, scanName FROM scan ORDER BY scanName")->fetch_all(MYSQLI_ASSOC);
$xrays = $conn->query("SELECT xID, xrayName FROM xray ORDER BY xrayName")->fetch_all(MYSQLI_ASSOC);

// --- Fetch Full Patient History ---
$history_consultations = $conn->query("SELECT * FROM consultations WHERE RegID = $reg_id ORDER BY ConsultationDate DESC")->fetch_all(MYSQLI_ASSOC);
$all_meds = [];
$all_injections = [];
$all_surgeries = [];
$all_scans = [];
$all_xrays = [];

$all_lab_tests = [];

if (!empty($history_consultations)) {
    $consultation_ids = array_column($history_consultations, 'ConsultationID');
    $ids_str = implode(',', $consultation_ids);

    $med_results = $conn->query("SELECT cm.*, m.name AS medicine_name FROM consultation_medicines cm LEFT JOIN medicines m ON cm.MedicineID = m.Mid WHERE cm.ConsultationID IN ($ids_str)");
    while ($row = $med_results->fetch_assoc()) { $all_meds[$row['ConsultationID']][] = $row; }

    $inj_results = $conn->query("SELECT ci.*, v.name AS vaccine_name FROM consultation_injections ci LEFT JOIN vaccination v ON ci.VaccinationID = v.VId WHERE ci.ConsultationID IN ($ids_str)");
    while ($row = $inj_results->fetch_assoc()) { $all_injections[$row['ConsultationID']][] = $row; }

    $surg_results = $conn->query("SELECT cs.*, s.surName AS surgery_name FROM consultation_surgeries cs LEFT JOIN surgery s ON cs.SurgeryID = s.surgeryID WHERE cs.ConsultationID IN ($ids_str)");
    while ($row = $surg_results->fetch_assoc()) { $all_surgeries[$row['ConsultationID']][] = $row; }

    $scan_results = $conn->query("SELECT csc.*, s.scanName AS scan_name FROM consultation_scans csc LEFT JOIN scan s ON csc.ScanID = s.sID WHERE csc.ConsultationID IN ($ids_str)");
    while ($row = $scan_results->fetch_assoc()) { $all_scans[$row['ConsultationID']][] = $row; }


    $xray_results = $conn->query("SELECT csc.*, s.xrayName AS xray_name FROM consultation_xrays csc LEFT JOIN xray s ON csc.XrayID = s.xID WHERE csc.ConsultationID IN ($ids_str)");
    while ($row = $xray_results->fetch_assoc()) { $all_xrays[$row['ConsultationID']][] = $row; }

    $lab_results = $conn->query("SELECT clt.*, l.name AS test_name FROM consultation_lab_tests clt LEFT JOIN laboratory l ON clt.LabTestID = l.Lid WHERE clt.ConsultationID IN ($ids_str)");
    while ($row = $lab_results->fetch_assoc()) { $all_lab_tests[$row['ConsultationID']][] = $row; }
}

// Fetch lab test results for the history
$all_clt_ids = [];
if (!empty($all_lab_tests)) {
    foreach ($all_lab_tests as $tests) {
        $all_clt_ids = array_merge($all_clt_ids, array_column($tests, 'CLT_ID'));
    }
}

$lab_results_map = [];
if (!empty($all_clt_ids)) {
    $clt_ids_str = implode(',', array_unique($all_clt_ids));
    $results_query = "SELECT ltr.*, lts.SubCategoryName, lts.Unit, lts.ReferenceRange
                      FROM lab_test_results ltr
                      LEFT JOIN lab_test_subcategories lts ON ltr.SubCategoryID = lts.SubCategoryID
                      WHERE ltr.CLT_ID IN ($clt_ids_str) AND ltr.is_deleted = 0";
    $results_res = $conn->query($results_query);
    if($results_res) {
        while ($row = $results_res->fetch_assoc()) {
            $lab_results_map[$row['CLT_ID']][] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation for <?= htmlspecialchars($patient['petnam']) ?> - The Cochin pet Clinic</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .history-visit-block { border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 8px; background-color: #f9f9f9; }
        .history-visit-block h4 { border-bottom: 1px solid #ccc; padding-bottom: 5px; }
    </style>
</head>
<body>

<div class="container">
    <h1>Consultation Workflow</h1>

    <div class="patient-details">
        <h2>Pet: <?= htmlspecialchars($patient['petnam']) ?> (<?= htmlspecialchars($patient['Pettyp']) ?>)</h2>
        <p><strong>Reg No:</strong> <?= htmlspecialchars($patient['RegNo']) ?></p>
        <p><strong>Owner:</strong> <?= htmlspecialchars($patient['ownnam']) ?></p>
    </div>

    <div class="tabs">
        <button class="tab-link active" data-tab="#history">History</button>
        <button class="tab-link" data-tab="#diagnosis">Diagnosis & Vitals</button>
        <button class="tab-link" data-tab="#medicine">Medicine</button>
        <button class="tab-link" data-tab="#procedures">Injections/Procedures</button>
        <button class="tab-link" data-tab="#lab-tests">Lab Tests</button>
        <button class="tab-link" data-tab="#preview">Preview & Finalize</button>
    </div>

    <form id="consultation-form" action="save_consultation.php" method="POST">
        <input type="hidden" name="booking_id" value="<?= $booking_id ?>">
        <input type="hidden" name="reg_id" value="<?= $reg_id ?>">

        <div id="history" class="tab-content active">
            <h3>Complete Medical History</h3>
            <?php if (empty($history_consultations)): ?>
                <p>No prior consultation history found for this patient.</p>
            <?php else: ?>
                <?php foreach ($history_consultations as $consult): ?>
                    <div class="history-visit-block">
                        <h4>Visit on: <?= date('d-M-Y H:i', strtotime($consult['ConsultationDate'])) ?></h4>
                        <p><strong>Temperature:</strong> <?= htmlspecialchars($consult['Temperature']) ?> | <strong>Weight:</strong> <?= htmlspecialchars($consult['Weight']) ?> kg</p>
                        <div><strong>Diagnosis:</strong><p><?= nl2br(htmlspecialchars($consult['DiagnosisNotes'])) ?></p></div>
                        <?php $cid = $consult['ConsultationID']; ?>
                        <?php if (!empty($all_meds[$cid])): ?>
                            <strong>Medication:</strong>
                            <ul><?php foreach($all_meds[$cid] as $med): ?><li><?= htmlspecialchars($med['OtherMedicineName'] ?: ($med['medicine_name'] ?? 'N/A')) ?> (<?= htmlspecialchars($med['Dosage']) ?>)</li><?php endforeach; ?></ul>
                        <?php endif; ?>
                        <?php if (!empty($all_injections[$cid])): ?>
                            <strong>Injections:</strong>
                            <ul><?php foreach($all_injections[$cid] as $inj): ?><li><?= htmlspecialchars($inj['InjectionName'] ?: ($inj['vaccine_name'] ?? 'N/A')) ?> (<?= htmlspecialchars($inj['Dosage']) ?>)</li><?php endforeach; ?></ul>
                        <?php endif; ?>
                        <?php if (!empty($all_surgeries[$cid])): ?>
                            <strong>Surgery:</strong>
                            <ul><?php foreach($all_surgeries[$cid] as $s): ?><li><?= htmlspecialchars($s['SurgeryName'] ?: ($s['surgery_name'] ?? 'N/A')) ?></li><?php endforeach; ?></ul>
                        <?php endif; ?>
                        <?php if (!empty($all_scans[$cid])): ?>
                            <strong>Scans:</strong>
                            <ul><?php foreach($all_scans[$cid] as $s): ?><li><?= htmlspecialchars($s['ScanName'] ?: ($s['scan_name'] ?? 'N/A')) ?></li><?php endforeach; ?></ul>
                        <?php endif; ?>

                        <?php if (!empty($all_xrays[$cid])): ?>
                            <strong>Xrays:</strong>
                            <ul><?php foreach($all_xrays[$cid] as $s): ?><li><?= htmlspecialchars($s['XrayName'] ?: ($s['xray_name'] ?? 'N/A')) ?></li><?php endforeach; ?></ul>
                        <?php endif; ?>
                        <?php if (!empty($all_lab_tests[$cid])): ?>
                            <strong>Lab Tests:</strong>
                            <ul>
                                <?php foreach($all_lab_tests[$cid] as $test): ?>
                                    <li>
                                        <strong><?= htmlspecialchars($test['CustomTestName'] ?: ($test['test_name'] ?? 'N/A')) ?></strong>
                                        <?php if (isset($lab_results_map[$test['CLT_ID']])): ?>
                                            <ul>
                                                <?php foreach ($lab_results_map[$test['CLT_ID']] as $result): ?>
                                                    <li>
                                                        <?php if ($result['SubCategoryName']): ?>
                                                            <?= htmlspecialchars($result['SubCategoryName']) ?>:
                                                        <?php endif; ?>
                                                        <?= htmlspecialchars($result['ResultValue']) ?>
                                                        <?php if ($result['Unit']): ?>
                                                            <?= htmlspecialchars($result['Unit']) ?>
                                                        <?php endif; ?>
                                                        <small>(Ref: <?= htmlspecialchars($result['ReferenceRange'] ?? 'N/A') ?>)</small>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php else: ?>
                                            <small style="color: #d9534f; font-style: italic;"> (Result Pending)</small>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div id="diagnosis" class="tab-content">
            <h3>Diagnosis & Vitals</h3>
            <div class="form-group">
                <label for="temperature">Temperature</label>
                <input type="text" id="temperature" name="temperature" class="form-control">
            </div>
            <div class="form-group">
                <label>Weight</label>
                <input type="number" step="0.01" name="weight_kg" placeholder="kg" class="form-control" style="width: 48%; display: inline-block;">
                <input type="number" step="1" name="weight_g" placeholder="g" class="form-control" style="width: 48%; display: inline-block;">
            </div>
            <div class="form-group">
                <label for="diagnosis_notes">Diagnosis Notes / Clinical Findings</label>
                <textarea id="diagnosis_notes" name="diagnosis_notes" class="form-control"></textarea>
            </div>


        </div>

        <div id="medicine" class="tab-content">
            <h3>Prescribe Medication</h3>
            <table class="dynamic-table" id="medicine-table">
                <thead><tr><th>Medicine</th><th>Dosage</th><th>Frequency</th><th>Units</th><th>Time</th><th>Type</th><th>Action</th></tr></thead>
                <tbody></tbody>
            </table>
            <button type="button" class="btn btn-primary add-row-btn" data-template-id="medicine-row-template" data-target-tbody="#medicine-table tbody" style="margin-top: 10px;">Add Medicine</button>
        </div>

        <div id="procedures" class="tab-content">
            <h3>Injections & Procedures</h3>
            <h4>Injections</h4>
            <table class="dynamic-table" id="injections-table">
                 <thead><tr><th>Injection Name</th><th>Dosage</th><th>Action</th></tr></thead>
                 <tbody></tbody>
            </table>
            <button type="button" class="btn btn-primary add-row-btn" data-template-id="injection-row-template" data-target-tbody="#injections-table tbody" style="margin-top: 10px;">Add Injection</button>
            <h4 style="margin-top: 20px;">Surgery</h4>
            <div class="form-group">
                <label for="surgery_id">Procedure</label>
                <select name="surgery_id" class="form-control"><option value="">Select Surgery</option><?php foreach ($surgeries as $surg): ?><option value="<?= $surg['surgeryID'] ?>"><?= htmlspecialchars($surg['surName']) ?></option><?php endforeach; ?></select>
                <input type="text" name="surgery_other_name" class="form-control" placeholder="Or type other surgery" style="margin-top: 5px;">
            </div>
            <div class="form-group"><label for="surgery_notes">Notes</label><textarea name="surgery_notes" class="form-control"></textarea></div>
            <h4 style="margin-top: 20px;">Scanning</h4>
            <div class="form-group">
                <label for="scan_id">Scan</label>
                <select name="scan_id" class="form-control"><option value="">Select Scan</option><?php foreach ($scans as $scan): ?><option value="<?= $scan['sID'] ?>"><?= htmlspecialchars($scan['scanName']) ?></option><?php endforeach; ?></select>
                <input type="text" name="scan_other_name" class="form-control" placeholder="Or type other scan" style="margin-top: 5px;">
            </div>
            <div class="form-group"><label for="scan_notes">Notes</label><textarea name="scan_notes" class="form-control"></textarea></div>
         <h4 style="margin-top: 20px;">Xray</h4>
            <div class="form-group">
                <label for="xray_id">Xray</label>
                <select name="xray_id" class="form-control"><option value="">Select Xray</option><?php foreach ($xrays as $xray): ?><option value="<?= $xray['xID'] ?>"><?= htmlspecialchars($xray['xrayName']) ?></option><?php endforeach; ?></select>
                <input type="text" name="xray_other_name" class="form-control" placeholder="Or type other xray" style="margin-top: 5px;">
            </div>
            <div class="form-group"><label for="xray_notes">Notes</label><textarea name="xray_notes" class="form-control"></textarea></div>

        </div>

        <div id="lab-tests" class="tab-content">
            <h3>Prescribe Lab Tests</h3>
             <table class="dynamic-table" id="lab-tests-table">
                 <thead><tr><th>Test Name</th><th>Instructions</th><th>Action</th></tr></thead>
                 <tbody></tbody>
            </table>
            <button type="button" class="btn btn-primary add-row-btn" data-template-id="lab-test-row-template" data-target-tbody="#lab-tests-table tbody" style="margin-top: 10px;">Add Lab Test</button>
        </div>

        <div id="preview" class="tab-content">
            <h3>Preview & Finalize</h3>
            <div id="preview-content" style="border: 1px solid #eee; padding: 15px; border-radius: 5px;"><p>Click the 'Preview' tab again to refresh the summary.</p></div>
            <hr>

<div class="form-group" style="display: flex; align-items: center; margin-bottom: 10px;">
    <input type="checkbox" id="review_needed_checkbox" name="review_needed" value="1" checked style="margin-right: 10px; transform: scale(1.5);">
    <label for="review_needed_checkbox" style="margin: 0; font-weight: bold;">Next Review is Needed</label>
</div>

<div class="form-group" id="review_date_container">
    <label for="review_date">Next Review Date</label>
    <input type="date" id="review_date" name="review_date" required>
</div>
            <button type="submit" name="save_finalize" class="btn btn-primary" style="font-size: 18px;">Save & Finalize</button>
        </div>




    </form>
</div>

<template id="medicine-row-template">
    <tr>
        <td>
            <select name="medicine_id[]" class="form-control"><option value="">Select Medicine</option><?php foreach ($medicines as $med): ?><option value="<?= $med['Mid'] ?>"><?= htmlspecialchars($med['name']) ?></option><?php endforeach; ?></select>
            <input type="text" name="medicine_other_name[]" class="form-control" placeholder="Or type other medicine" style="margin-top: 5px;">
        </td>
        <td><input type="text" name="dosage[]" class="form-control"></td>
        <td>
            <select name="frequency[]" class="form-control"><option>Once a day</option><option>Twice a day</option><option>Thrice a day</option><option>Every 6 hours</option><option>Every 8 hours</option><option>Every 12 hours</option><option>As needed</option></select>
        </td>
        <td><input type="text" name="total_units[]" class="form-control"></td>
        <td>
            <select name="time[]" class="form-control"><option>After Food</option><option>Before Food</option><option>With Food</option></select>
        </td>
        <td>
            <select name="type[]" class="form-control"><option>Tablet</option><option>Capsule</option><option>Syrup</option><option>Cream</option><option>Injection</option><option>Drops</option></select>
        </td>
        <td><button type="button" class="btn btn-danger remove-row">Remove</button></td>
    </tr>
</template>
<template id="injection-row-template">
    <tr>
        <td>
            <select name="injection_id[]" class="form-control"><option value="">Select Vaccine</option><?php foreach ($vaccines as $vac): ?><option value="<?= $vac['VId'] ?>"><?= htmlspecialchars($vac['name']) ?></option><?php endforeach; ?></select>
            <input type="text" name="injection_other_name[]" class="form-control" placeholder="Or type other injection" style="margin-top: 5px;">
        </td>
        <td><input type="text" name="injection_dosage[]" class="form-control"></td>
        <td><button type="button" class="btn btn-danger remove-row">Remove</button></td>
    </tr>
</template>
<template id="lab-test-row-template">
    <tr>
        <td>
            <select name="lab_test_id[]" class="form-control"><option value="">Select Test</option><?php foreach ($lab_tests as $test): ?><option value="<?= $test['Lid'] ?>"><?= htmlspecialchars($test['name']) ?></option><?php endforeach; ?></select>
            <input type="text" name="lab_test_other_name[]" class="form-control" placeholder="Or type other test" style="margin-top: 5px;">
        </td>
        <td><textarea name="lab_test_instructions[]" class="form-control"></textarea></td>
        <td><button type="button" class="btn btn-danger remove-row">Remove</button></td>
    </tr>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const consultationForm = document.getElementById('consultation-form');
    const reviewNeededCheckbox = document.getElementById('review_needed_checkbox');
    const reviewDateContainer = document.getElementById('review_date_container');
    const reviewDateInput = document.getElementById('review_date');

    // Initial state check
    if (!reviewNeededCheckbox.checked) {
        reviewDateContainer.style.display = 'none';
        reviewDateInput.removeAttribute('required');
    }

    // Add event listener to the checkbox
    reviewNeededCheckbox.addEventListener('change', function() {
        if (this.checked) {
            reviewDateContainer.style.display = 'block';
            reviewDateInput.setAttribute('required', 'required');
        } else {
            reviewDateContainer.style.display = 'none';
            reviewDateInput.removeAttribute('required');
        }
    });

    // Add event listener for form submission
    consultationForm.addEventListener('submit', function(event) {
        // If the checkbox is checked, validate that a date is entered
        if (reviewNeededCheckbox.checked && !reviewDateInput.value) {
            alert('Please select a Next Review Date.');
            event.preventDefault(); // Stop the form from submitting

            // Optional: Switch back to the diagnosis tab
            document.querySelector('.tab-link.active').classList.remove('active');
            document.querySelector('.tab-content.active').classList.remove('active');
            document.querySelector('[data-tab="#diagnosis"]').classList.add('active');
            document.getElementById('diagnosis').classList.add('active');
        }
    });

    // ... (rest of the existing JavaScript code for tabs and dynamic rows) ...
});
</script>
<script src="../js/main.js"></script>

</body>
</html>
