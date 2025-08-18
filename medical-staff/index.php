<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
require_once '../includes/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Staff Dashboard</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body { display: block; }
        .container { width: 80%; margin: 2em auto; }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        .dashboard-card {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        .dashboard-card h3 {
            margin-top: 0;
        }
        .dashboard-card a {
            display: block;
            background: #5cb85c;
            color: white;
            padding: 10px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }
        .dashboard-card a:hover {
            background: #4cae4c;
        }
    </style>
</head>
<body>
    <header>
        <h1>Medical Staff Dashboard</h1>
        <nav><a href="../logout.php">Logout</a></nav>
    </header>
    <div class="container">
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3>Lab Results</h3>
                <p>Manage and view laboratory test results.</p>
                <a href="pending_by_consultation.php">Pending Work by Consultation</a>
                <a href="lab_status_overview.php">Complete Lab Status List</a>
            </div>
            <div class="dashboard-card">
                <h3>Prescriptions</h3>
                <p>View and manage medication fulfillment.</p>
                <a href="#" style="background: #aaa; cursor: not-allowed;">Fulfill Prescriptions (Coming Soon)</a>
            </div>
            <div class="dashboard-card">
                <h3>Injections</h3>
                <p>View and manage vaccine/injection administration.</p>
                <a href="#" style="background: #aaa; cursor: not-allowed;">Administer Injections (Coming Soon)</a>
            </div>
        </div>
    </div>
</body>
</html>
