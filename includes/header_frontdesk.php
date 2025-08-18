<?php
session_start();
// This is a placeholder for user authentication.
// In a real app, you'd have a proper role check for 'frontdesk'.
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
require_once 'db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Front Desk Dashboard</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        body { display: block; font-family: sans-serif; }
        .container { width: 90%; margin: 2em auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.8em; text-align: left; border-bottom: 1px solid #ddd; }
        header { background: #333; color: #fff; padding: 1em; }
        header h1 { margin: 0; }
        header a { color: #fff; }
    </style>
</head>
<body>
    <header>
        <h1>Front Desk</h1>
        <nav><a href="../logout.php">Logout</a></nav>
    </header>
    <div class="container">
