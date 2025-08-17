<?php
session_start();
// Check if the user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'doctor') {
    header("Location: ../login.php");
    exit();
}

require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor's Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Add some basic styling for the dashboard */
        body {
            display: block;
        }
        .container {
            width: 80%;
            margin: 2em auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 0.8em;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        .status-checked {
            color: green;
            font-weight: bold;
        }
        .status-not-checked {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <header>
        <h1>Doctor's Dashboard</h1>
        <nav>
            <a href="index.php">Home</a>
            <a href="../logout.php">Logout</a>
        </nav>
    </header>
    <div class="container">
