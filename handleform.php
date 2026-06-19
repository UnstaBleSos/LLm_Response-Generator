<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';

use GeminiAPI\Client;
use GeminiAPI\Resources\ModelName;
use GeminiAPI\Resources\Parts\TextPart;

include("./dbconnect.php");

function loadEnv($path)
{
    if (!file_exists($path)) return;

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;

        [$key, $value] = array_map('trim', explode('=', $line, 2));

        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

loadEnv(__DIR__ . '/.env');
// Initialize the Gemini client
$client = new Client($_ENV['ANOTHER_API_KEY']);
echo $_ENV['GEMINI_API_KEY'];

// Retrieve form data
$complaint = $_POST['complaint'];
$category = $_POST['category'];
$subcategory = $_POST['subcategory'];

// Define hotlines for subcategories
$hotlines = [
    'Electricity' => [
        'Transmitter burst' => 'Electricity Hotline: 9841355875',
        'Cut-off wire' => 'Electricity Hotline: 9841355899',
        'Pole fall' => 'Electricity Hotline: 9841355456',
        'Half-cut electricity' => 'Electricity Hotline: 9841312495'
    ],
    'Water' => [
        'Dirty water' => 'Water Hotline: 9875663212',
        'No schedule time water' => 'Water Hotline: 9875663265',
        'Pipe burst' => 'Water Hotline: 9875663288'
    ],
    'Garbage' => [
        'No schedule time' => 'Garbage Hotline: 9841565789',
        'Overload garbage' => 'Garbage Hotline: 9841565755'
    ]
];

// Fetch the hotline based on category and subcategory
$hotline = $hotlines[$category][$subcategory] ?? 'General Emergency: 984512285';

// Send complaint to Gemini for structured analysis
$response = $client->generativeModel("gemini-2.5-flash")->generateContent(
    new TextPart(
        "Analyze the following complaint and return a structured response.

        Format exactly as:

        Complaint Summary:
        [summary]

        Possible Causes:
        • cause 1
        • cause 2

        User Impact:
        • impact 1
        • impact 2

        Suggested Actions:
        • action 1
        • action 2

        Complaint Details:
        Category: $category
        Subcategory: $subcategory
        Complaint: $complaint"
    )
);

// Retrieve AI response
$ai_response = $response->text();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint Details</title>
    <style>
    .ai-box {
        margin-top: 25px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 12px;
        border-left: 6px solid #007bff;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    }

    .ai-box h3 {
        margin: 0 0 15px 0;
        color: #007bff;
        font-size: 22px;
    }

    .ai-response {
        line-height: 1.8;
        font-size: 15px;
        color: #333;
        white-space: normal;
    }

    /* Complaint information cards */

    .info-card {
        background: #f7f7f7;
        padding: 12px;
        margin-bottom: 10px;
        border-radius: 8px;
    }

    .info-card strong {
        color: #007bff;
    }

    /* Popup styling */
    .popup {
        display: block;
        /* Show popup by default */
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .popup-content {
        background-color: #fff;
        margin: 10% auto;
        /* Adjust centering */
        padding: 30px;
        border: 1px solid #888;
        width: 90%;
        /* Increase width */
        max-width: 800px;
        /* Adjust max width */
        height: auto;
        /* Allow height to grow as needed */
        max-height: 80%;
        /* Prevent overflowing */
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        overflow-y: auto;
        /* Add scrolling for long content */
    }

    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }

    button {
        margin-top: 20px;
        padding: 10px 20px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    button:hover {
        background-color: #0056b3;
    }
    </style>
    <script>
    function closePopup() {
        document.getElementById("complaint-popup").style.display = "none";
    }
    </script>
</head>

<body>
    <h1>Complaint Submitted</h1>

    <!-- Popup structure -->
    <div id="complaint-popup" class="popup">
        <div class="popup-content">
            <span class="close" onclick="closePopup()">&times;</span>
            <div class="info-card">
                <strong>Complaint:</strong><br>
                <?php echo htmlspecialchars($complaint); ?>
            </div>

            <div class="info-card">
                <strong>Category:</strong>
                <?php echo htmlspecialchars($category); ?>
            </div>

            <div class="info-card">
                <strong>Subcategory:</strong>
                <?php echo htmlspecialchars($subcategory); ?>
            </div>

            <div class="info-card">
                <strong>Hotline:</strong>
                <?php echo htmlspecialchars($hotline); ?>
            </div>

            <div class="ai-box">
                <h3>AI Analysis</h3>

                <div class="ai-response">
                    <?php echo nl2br(htmlspecialchars($ai_response)); ?>
                </div>
            </div>
        </div>
    </div>
</body>

</html>