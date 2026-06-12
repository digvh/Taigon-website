<?php
session_start();
$errorCode = isset($_GET['code']) ? (int)$_GET['code'] : 404;
$errorMessages = [
    400 => ['Bad Request', 'The request could not be understood by the server.'],
    401 => ['Unauthorized', 'You need to log in to access this page.'],
    403 => ['Forbidden', 'You don\'t have permission to access this page.'],
    404 => ['Page Not Found', 'The page you are looking for does not exist.'],
    500 => ['Internal Server Error', 'Something went wrong on our end. Please try again later.']
];
$error = $errorMessages[$errorCode] ?? $errorMessages[404];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $error[0]; ?> - Taigon Investments</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .error-container {
            text-align: center;
            max-width: 600px;
            background: white;
            border-radius: 28px;
            padding: 3rem;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        }
        .error-code { font-size: 8rem; font-weight: 800; background: linear-gradient(135deg, #0ea5e9, #0369a1); -webkit-background-clip: text; -webkit-text-fill-color: transparent; line-height: 1; }
        .error-title { font-size: 1.5rem; color: #0f172a; margin: 1rem 0; }
        .error-message { color: #475569; margin-bottom: 2rem; }
        .btn-home { display: inline-flex; align-items: center; gap: 0.5rem; background: linear-gradient(135deg, #0ea5e9, #0369a1); color: white; padding: 0.8rem 2rem; border-radius: 2rem; text-decoration: none; font-weight: 600; transition: all 0.3s; }
        .btn-home:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(14,165,233,0.3); }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code"><?php echo $errorCode; ?></div>
        <h1 class="error-title"><?php echo $error[0]; ?></h1>
        <p class="error-message"><?php echo $error[1]; ?></