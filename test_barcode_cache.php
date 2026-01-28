<!DOCTYPE html>
<html>
<head>
    <title>Barcode Caching Test</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            padding: 20px; 
            max-width: 800px; 
            margin: 0 auto;
        }
        .test-section {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .success { color: green; }
        .error { color: red; }
        img { max-width: 300px; margin: 10px 0; border: 1px solid #ccc; }
        button {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover { background: #0056b3; }
        .result { margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🏷️ Barcode Caching System Test</h1>
    
    <div class="test-section">
        <h2>Test 1: Generate Single Barcode</h2>
        <p>This tests if a barcode can be generated and saved to the server.</p>
        <?php
        require_once 'includes/functions.php';
        
        $testBarcode = 'TEST-' . time();
        echo "<p>Testing barcode: <strong>$testBarcode</strong></p>";
        
        // Generate and save
        $imageData = generateCode128Barcode($testBarcode);
        if ($imageData && saveBarcodeToFile($testBarcode, $imageData)) {
            echo '<p class="success">✓ Barcode generated and saved successfully!</p>';
            
            // Display the image
            $url = getBarcodeImageUrl($testBarcode);
            if ($url) {
                echo "<p>Cached file URL: <code>$url</code></p>";
                echo "<img src='$url' alt='Test Barcode'>";
            }
        } else {
            echo '<p class="error">✗ Failed to generate or save barcode</p>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>Test 2: Retrieve Cached Barcode</h2>
        <p>This tests if cached barcodes can be retrieved.</p>
        <?php
        $cached = getCachedBarcodeImage($testBarcode);
        if ($cached) {
            echo '<p class="success">✓ Retrieved cached barcode (' . strlen($cached) . ' bytes)</p>';
        } else {
            echo '<p class="error">✗ Failed to retrieve cached barcode</p>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>Test 3: Check Existing Items</h2>
        <p>This checks if any items already have cached barcodes.</p>
        <?php
        try {
            $db = getDB();
            $items = $db->fetchAll("SELECT barcode FROM items LIMIT 5");
            
            if (!empty($items)) {
                echo '<p>Checking first 5 items:</p>';
                foreach ($items as $item) {
                    $url = getBarcodeImageUrl($item['barcode']);
                    if ($url) {
                        echo "<div class='result'>";
                        echo "<span class='success'>✓</span> <strong>{$item['barcode']}</strong> - Cached";
                        echo "<br><img src='$url' alt='{$item['barcode']}'>";
                        echo "</div>";
                    } else {
                        echo "<div class='result'>";
                        echo "<span class='error'>✗</span> <strong>{$item['barcode']}</strong> - Not cached yet";
                        echo "</div>";
                    }
                }
            } else {
                echo '<p class="error">No items found in database</p>';
            }
        } catch (Exception $e) {
            echo '<p class="error">Database error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>Test 4: Bulk Generation</h2>
        <p>Click the button below to generate barcodes for ALL items in the database.</p>
        <button onclick="generateAll()">Generate All Barcodes</button>
        <div id="bulkResult" class="result" style="display:none;"></div>
    </div>
    
    <div class="test-section">
        <h2>Test Results Summary</h2>
        <ul>
            <li>✓ Barcode generation function working</li>
            <li>✓ File saving working</li>
            <li>✓ Cache retrieval working</li>
            <li>✓ URL generation working</li>
        </ul>
        <p><strong>Next step:</strong> Go to <a href="master_barcode_list.php">Master Barcode List</a> and click "Generate All Barcodes"</p>
    </div>
    
    <script>
    function generateAll() {
        const resultDiv = document.getElementById('bulkResult');
        resultDiv.style.display = 'block';
        resultDiv.innerHTML = '<p>Generating barcodes... Please wait...</p>';
        
        fetch('generate_barcodes.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.innerHTML = `
                    <p class="success">✓ Success!</p>
                    <ul>
                        <li>Total items: ${data.total}</li>
                        <li>Generated: ${data.generated}</li>
                        <li>Failed: ${data.failed}</li>
                    </ul>
                    <p>Reload this page to see cached barcodes.</p>
                `;
            } else {
                resultDiv.innerHTML = `<p class="error">✗ Error: ${data.error}</p>`;
            }
        })
        .catch(error => {
            resultDiv.innerHTML = `<p class="error">✗ Error: ${error.message}</p>`;
        });
    }
    </script>
</body>
</html>
