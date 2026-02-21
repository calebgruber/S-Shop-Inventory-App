<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode</title>
    <?php
    // Load minimal dependencies to show configured favicon
    if (file_exists(__DIR__ . '/includes/functions.php')) {
        require_once __DIR__ . '/includes/functions.php';
        require_once __DIR__ . '/includes/favicon.php';
    }
    ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .maintenance-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 3rem;
            max-width: 600px;
            text-align: center;
        }
        .maintenance-icon {
            font-size: 80px;
            color: #667eea;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="maintenance-card">
        <div class="maintenance-icon">🔧</div>
        <h1 class="mb-3">We'll Be Right Back!</h1>
        <p class="lead text-muted mb-4">
            We're currently performing scheduled maintenance to improve your experience.
        </p>
        <p class="text-muted">
            We apologize for any inconvenience. Please check back in a few minutes.
        </p>
        <div class="mt-4">
            <a href="/auth/login" class="btn btn-primary">Back to Login</a>
        </div>
    </div>
</body>
</html>
