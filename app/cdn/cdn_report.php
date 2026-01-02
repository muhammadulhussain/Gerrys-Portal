<?php
require_once __DIR__ . '/../includes/session_check.php';
require_role(['Employee', 'Admin']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Page Under Construction</title>
  <style>
    body {
      margin: 0;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      flex-direction: column;
      background-color: #e2e2e2ff;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: #333;
      text-align: center;
    }
    .container {
      max-width: 600px;
      padding: 20px;
    }
    h1 {
      font-size: 2.2rem;
      color: #ebb41e;
    }
    p {
      font-size: 1.1rem;
      margin-bottom: 20px;
      color: #555;
    }
    .loader {
      border: 6px solid #ddd;
      border-radius: 50%;
      border-top: 6px solid #ebb41e;
      width: 60px;
      height: 60px;
      animation: spin 1s linear infinite;
      margin: 20px auto;
    }
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    /* Back Button Styling */
    .back-btn {
      display: inline-block;
      margin-top: 15px;
      padding: 10px 20px;
      background-color: #ebb41e;
      color: black;
      text-decoration: none;
      font-size: 1rem;
      border-radius: 5px;
      transition: all 0.3s ease;
      cursor: pointer;
      border: 0px;
    }
    .back-btn:hover {
      background-color: #ddb23aff;
      color: white;
    }

    footer {
      position: absolute;
      bottom: 10px;
      font-size: 0.9rem;
      color: #777;
    }
  </style>
</head>
<body>

  <div class="container">
    <h1>Work in Progress</h1>
    <p>CDN Report page is currently under development.<br>
       Please check back soon.</p>
    <div class="loader"></div>
    
    <!-- Back Button -->
    <button class="back-btn" onclick="history.back()">← Go Back</button>
  </div>

  <footer>
    © 2025 | CDN Report System
  </footer>

</body>
</html>
