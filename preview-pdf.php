<?php
if (!isset($_GET['file']) || !file_exists($_GET['file'])) {
    die('Invalid file: ' . htmlspecialchars($_GET['file']));
}

$file = $_GET['file'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Preview</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        .container { text-align: center; margin: 20px; }
        iframe { width: 100%; height: 90vh; border: none; }
        .download-btn {padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 20px; font-size: 9px;float: right;}
        .download-btn:hover { background-color: #45a049; }
    </style>
</head>
<body>
    <div class="container">
        <iframe src="<?php echo htmlspecialchars($file); ?>"></iframe>
        <a href="<?php echo htmlspecialchars($file); ?>" class="download-btn btn-small" download>Download PDF</a>
    </div>
</body>
</html>
