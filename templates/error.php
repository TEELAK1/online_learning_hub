<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Online Learning Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 3rem;
            text-align: center;
            max-width: 500px;
        }
        .error-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 1rem;
        }
        .error-code {
            font-size: 3rem;
            font-weight: bold;
            color: #dc3545;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="error-code"><?php echo $code ?? '500'; ?></div>
        <h2 class="mb-3">Oops! Something went wrong</h2>
        <p class="text-muted mb-4"><?php echo $message ?? 'An unexpected error occurred. Please try again later.'; ?></p>
        <div class="d-grid gap-2">
            <a href="../index.php" class="btn btn-primary">
                <i class="fas fa-home me-2"></i>Go Home
            </a>
            <button onclick="history.back()" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Go Back
            </button>
        </div>
        <?php if (defined('ENVIRONMENT') && ENVIRONMENT !== 'production'): ?>
            <div class="mt-4 p-3 bg-light rounded">
                <small class="text-muted">
                    Error ID: <?php echo uniqid(); ?><br>
                    Time: <?php echo date('Y-m-d H:i:s'); ?>
                </small>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
