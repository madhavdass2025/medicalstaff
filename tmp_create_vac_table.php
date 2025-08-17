<?php
require_once 'includes/db.php';

$query = "CREATE TABLE `casesheetbillvaccination` (
  `vacBillId` int NOT NULL AUTO_INCREMENT,
  `billID` varchar(100) NOT NULL,
  `regID` varchar(100) NOT NULL,
  `vacID` varchar(100) NOT NULL,
  `vacName` varchar(100) NOT NULL,
  `vacAmount` varchar(100) NOT NULL,
  `nextVacDate` varchar(10) NOT NULL,
  `submittedBy` varchar(100) NOT NULL,
  `submittedDate` timestamp(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `cancel` varchar(255) NOT NULL DEFAULT '0',
  PRIMARY KEY (`vacBillId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if ($conn->query($query) === TRUE) {
    echo "Table casesheetbillvaccination created successfully.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

$conn->close();
?>
