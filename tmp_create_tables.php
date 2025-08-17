<?php
require_once 'includes/db.php';

$queries = [
    "CREATE TABLE `lab_test_subcategories` (
      `SubCategoryID` INT(11) NOT NULL AUTO_INCREMENT,
      `LabTestID` INT(11) NOT NULL,
      `SubCategoryName` VARCHAR(255) NOT NULL,
      `Unit` VARCHAR(50) DEFAULT NULL,
      `ReferenceRange` VARCHAR(100) DEFAULT NULL,
      PRIMARY KEY (`SubCategoryID`),
      FOREIGN KEY (`LabTestID`) REFERENCES `laboratory`(`Lid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;",

    "CREATE TABLE `lab_test_results` (
      `ResultID` INT(11) NOT NULL AUTO_INCREMENT,
      `CLT_ID` INT(11) NOT NULL,
      `SubCategoryID` INT(11) DEFAULT NULL,
      `ResultValue` VARCHAR(255) NOT NULL,
      `Remarks` TEXT DEFAULT NULL,
      PRIMARY KEY (`ResultID`),
      FOREIGN KEY (`CLT_ID`) REFERENCES `consultation_lab_tests`(`CLT_ID`),
      FOREIGN KEY (`SubCategoryID`) REFERENCES `lab_test_subcategories`(`SubCategoryID`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;"
];

foreach ($queries as $index => $query) {
    if ($conn->query($query) === TRUE) {
        echo "Table " . ($index + 1) . " created successfully.\n";
    } else {
        echo "Error creating table " . ($index + 1) . ": " . $conn->error . "\n";
    }
}

$conn->close();
?>
