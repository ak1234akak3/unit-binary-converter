<?php
session_start();

if (!isset($_SESSION['recent'])) {
    $_SESSION['recent'] = [];
}

$tab = isset($_GET['tab']) ? $_GET['tab'] : (isset($_POST['tab']) ? $_POST['tab'] : 'unit');

// Form default values & states
$unit_category = $_POST['category'] ?? 'Length';
$unit_value = $_POST['value'] ?? '';
$from_unit = $_POST['from_unit'] ?? 'Meters';
$to_unit = $_POST['to_unit'] ?? 'Meters';
$unit_result = null;

$binary_type = $_POST['binary_type'] ?? 'Binary to Decimal';
$binary_value = $_POST['binary_value'] ?? '';
$binary_results = null;

// Handle Unit Conversion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'convert_unit') {
    if (is_numeric($unit_value)) {
        $val = floatval($unit_value);
        $res = convert_units($val, $from_unit, $to_unit, $unit_category);
        $unit_result = "$val $from_unit = " . round($res, 4) . " $to_unit";

        // Add to recent conversions
        $time = date('H:i');
        array_unshift($_SESSION['recent'], "$time: $unit_result");
        $_SESSION['recent'] = array_slice($_SESSION['recent'], 0, 5); // Keep last 5
    }
}

// Handle Binary Conversion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'convert_binary') {
    $val = trim($binary_value);
    if ($val !== '') {
        $dec = null;
        if (strpos($binary_type, 'Binary') === 0) {
            if (preg_match('/^[01]+$/', $val)) {
                $dec = bindec($val);
            }
        } elseif (strpos($binary_type, 'Decimal') === 0 && is_numeric($val)) {
            $dec = intval($val);
        }

        if ($dec !== null) {
            $binary_results = [
                'binary' => decbin($dec),
                'decimal' => (string)$dec,
                'octal' => decoct($dec),
                'hexadecimal' => strtoupper(dechex($dec))
            ];

            $time = date('H:i');
            $rec_str = "$time: Binary " . $binary_results['binary'] . " = " . $binary_results['decimal'] . " Dec";
            array_unshift($_SESSION['recent'], $rec_str);
            $_SESSION['recent'] = array_slice($_SESSION['recent'], 0, 5);
        }
    }
}

function convert_units($val, $from, $to, $cat) {
    if ($from === $to) return $val;

    if ($cat === 'Temperature') {
        if ($from === 'Fahrenheit') $celsius = ($val - 32) * 5 / 9;
        elseif ($from === 'Kelvin') $celsius = $val - 273.15;
        else $celsius = $val;

        if ($to === 'Fahrenheit') return ($celsius * 9 / 5) + 32;
        if ($to === 'Kelvin') return $celsius + 273.15;
        return $celsius;
    }

    $length = [
        'Meters' => 1,
        'Kilometers' => 1000,
        'Centimeters' => 0.01,
        'Millimeters' => 0.001,
        'Miles' => 1609.34,
        'Feet' => 0.3048,
        'Inches' => 0.0254
    ];

    $weight = [
        'Grams' => 1,
        'Kilograms' => 1000,
        'Pounds' => 453.592,
        'Ounces' => 28.3495
    ];

    $rates = ($cat === 'Weight') ? $weight : $length;
    if (isset($rates[$from]) && isset($rates[$to])) {
        $base = $val * $rates[$from];
        return $base / $rates[$to];
    }

    return $val;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $tab === 'binary' ? 'Binary Converter' : 'Converter'; ?></title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        body {
            background-color: #f3f4f6;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .card {
            background: #ffffff;
            border-radius: 16px;
            width: 100%;
            max-width: 480px;
            padding: 32px 28px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .title {
            text-align: center;
            font-size: 24px;
            font-weight: 700;
            color: #222222;
            margin-bottom: 24px;
        }

        .tab-container {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
        }

        .tab-btn {
            flex: 1;
            text-align: center;
            padding: 12px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            border: 1px solid #e2e8f0;
            color: #333333;
            background-color: #f8fafc;
            transition: all 0.2s ease;
        }

        .tab-btn.active {
            background-color: #007bfb;
            color: #ffffff;
            border-color: #007bfb;
        }

        .divider {
            height: 1px;
            background-color: #f1f5f9;
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 18px;
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 14px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
        }

        .form-row {
            display: flex;
            gap: 12px;
        }

        .form-group.half {
            flex: 1;
        }

        input[type="text"], select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 15px;
            color: #1f2937;
            outline: none;
            background-color: #ffffff;
            transition: border-color 0.2s ease;
        }

        input[type="text"]:focus, select:focus {
            border-color: #007bfb;
        }

        .monospace-select, .monospace-input {
            font-family: "Courier New", Courier, monospace;
            font-size: 14px;
        }

        .hint-text {
            font-size: 12px;
            color: #6b7280;
            margin-top: 6px;
        }

        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 8px;
        }

        .btn {
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .btn-primary {
            background-color: #007bfb;
            color: #ffffff;
            flex: 1;
        }

        .btn-primary:hover {
            background-color: #0066d6;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: #ffffff;
            width: 110px;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .full-width {
            width: 100%;
        }

        .result-box {
            background-color: #eef2f6;
            border-radius: 8px;
            padding: 18px;
            text-align: center;
            font-size: 20px;
            font-weight: 700;
            color: #007bfb;
            margin-top: 20px;
        }

        .binary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 20px;
        }

        .grid-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
        }

        .grid-title {
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 4px;
        }

        .grid-value {
            font-family: "Courier New", Courier, monospace;
            font-size: 16px;
            color: #007bfb;
            word-break: break-all;
        }

        .visual-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px;
            margin-top: 12px;
        }

        .visual-title {
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 10px;
        }

        .bit-container {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .bit-box {
            width: 26px;
            height: 28px;
            border-radius: 4px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: "Courier New", Courier, monospace;
            font-weight: 700;
            font-size: 13px;
            color: #ffffff;
        }

        .bit-one {
            background-color: #007bfb;
        }

        .bit-zero {
            background-color: #6c757d;
        }

        .recent-section {
            margin-top: 24px;
            border-top: 1px solid #f1f5f9;
            padding-top: 16px;
        }

        .recent-section h3 {
            font-size: 14px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
        }

        .recent-item {
            font-size: 13px;
            color: #64748b;
            margin-top: 4px;
        }
    </style>
</head>
<body>

<div class="card">
    <h1 class="title"><?php echo $tab === 'binary' ? 'Binary Converter' : 'Converter'; ?></h1>

    <div class="tab-container">
        <a href="?tab=unit" class="tab-btn <?php echo $tab === 'unit' ? 'active' : ''; ?>">Unit Converter</a>
        <a href="?tab=binary" class="tab-btn <?php echo $tab === 'binary' ? 'active' : ''; ?>">Binary Converter</a>
    </div>

    <div class="divider"></div>

    <?php if ($tab === 'unit'): ?>
        <form method="POST" action="?tab=unit">
            <input type="hidden" name="tab" value="unit">
            <input type="hidden" name="action" value="convert_unit">

            <div class="form-group">
                <label>Category</label>
                <select name="category" id="categorySelect">
                    <option value="Length" <?php echo $unit_category === 'Length' ? 'selected' : ''; ?>>Length</option>
                    <option value="Temperature" <?php echo $unit_category === 'Temperature' ? 'selected' : ''; ?>>Temperature</option>
                    <option value="Weight" <?php echo $unit_category === 'Weight' ? 'selected' : ''; ?>>Weight</option>
                </select>
            </div>

            <div class="form-group">
                <label>Value</label>
                <input type="text" name="value" placeholder="Enter value..." value="<?php echo htmlspecialchars($unit_value); ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label>From</label>
                    <select name="from_unit" id="fromSelect" data-selected="<?php echo htmlspecialchars($from_unit); ?>"></select>
                </div>
                <div class="form-group half">
                    <label>To</label>
                    <select name="to_unit" id="toSelect" data-selected="<?php echo htmlspecialchars($to_unit); ?>"></select>
                </div>
            </div>

            <div class="btn-group">
                <button type="submit" class="btn btn-primary">Convert</button>
                <?php if ($unit_result !== null): ?>
                    <button type="button" class="btn btn-secondary" onclick="alert('Formula breakdown for calculation')">Explain</button>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($unit_result !== null): ?>
            <div class="result-box">
                <?php echo htmlspecialchars($unit_result); ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <form method="POST" action="?tab=binary">
            <input type="hidden" name="tab" value="binary">
            <input type="hidden" name="action" value="convert_binary">

            <div class="form-group">
                <label>Select Conversion Type</label>
                <select name="binary_type" class="monospace-select">
                    <option value="Binary to Decimal" <?php echo $binary_type === 'Binary to Decimal' ? 'selected' : ''; ?>>Binary to Decimal</option>
                    <option value="Binary to Octal" <?php echo $binary_type === 'Binary to Octal' ? 'selected' : ''; ?>>Binary to Octal</option>
                    <option value="Binary to Hexadecimal" <?php echo $binary_type === 'Binary to Hexadecimal' ? 'selected' : ''; ?>>Binary to Hexadecimal</option>
                    <option value="Decimal to Binary" <?php echo $binary_type === 'Decimal to Binary' ? 'selected' : ''; ?>>Decimal to Binary</option>
                </select>
            </div>

            <div class="form-group">
                <label>Enter Binary Value</label>
                <input type="text" name="binary_value" class="monospace-input" placeholder="Enter value to convert..." value="<?php echo htmlspecialchars($binary_value); ?>" required>
                <span class="hint-text">Enter a valid binary number (e.g., 1010, 11001)</span>
            </div>

            <button type="submit" class="btn btn-primary full-width">Convert</button>
        </form>

        <?php if ($binary_results !== null): ?>
            <div class="binary-grid">
                <div class="grid-card">
                    <span class="grid-title">Binary</span>
                    <span class="grid-value"><?php echo $binary_results['binary']; ?></span>
                </div>
                <div class="grid-card">
                    <span class="grid-title">Decimal</span>
                    <span class="grid-value"><?php echo $binary_results['decimal']; ?></span>
                </div>
                <div class="grid-card">
                    <span class="grid-title">Octal</span>
                    <span class="grid-value"><?php echo $binary_results['octal']; ?></span>
                </div>
                <div class="grid-card">
                    <span class="grid-title">Hexadecimal</span>
                    <span class="grid-value"><?php echo $binary_results['hexadecimal']; ?></span>
                </div>
            </div>

            <div class="visual-card">
                <div class="visual-title">Binary Representation (Visual)</div>
                <div class="bit-container">
                    <?php 
                    $bits = str_split($binary_results['binary']);
                    foreach ($bits as $bit): 
                    ?>
                        <span class="bit-box <?php echo $bit === '1' ? 'bit-one' : 'bit-zero'; ?>">
                            <?php echo $bit; ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>

    <div class="recent-section">
        <h3>Recent Conversions</h3>
        <?php if (!empty($_SESSION['recent'])): ?>
            <div class="recent-list">
                <?php foreach ($_SESSION['recent'] as $item): ?>
                    <p class="recent-item"><?php echo htmlspecialchars($item); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const categorySelect = document.getElementById("categorySelect");
    const fromSelect = document.getElementById("fromSelect");
    const toSelect = document.getElementById("toSelect");

    if (!categorySelect || !fromSelect || !toSelect) return;

    const units = {
        Length: ["Meters", "Kilometers", "Centimeters", "Millimeters", "Miles", "Feet", "Inches"],
        Temperature: ["Celsius", "Fahrenheit", "Kelvin"],
        Weight: ["Grams", "Kilograms", "Pounds", "Ounces"]
    };

    function populateDropdowns() {
        const cat = categorySelect.value;
        const availableUnits = units[cat] || [];

        const currentFrom = fromSelect.getAttribute("data-selected");
        const currentTo = toSelect.getAttribute("data-selected");

        fromSelect.innerHTML = "";
        toSelect.innerHTML = "";

        availableUnits.forEach(unit => {
            const opt1 = document.createElement("option");
            opt1.value = unit;
            opt1.textContent = unit;
            if (unit === currentFrom) opt1.selected = true;
            fromSelect.appendChild(opt1);

            const opt2 = document.createElement("option");
            opt2.value = unit;
            opt2.textContent = unit;
            if (unit === currentTo) opt2.selected = true;
            toSelect.appendChild(opt2);
        });

        fromSelect.removeAttribute("data-selected");
        toSelect.removeAttribute("data-selected");
    }

    categorySelect.addEventListener("change", populateDropdowns);
    populateDropdowns();
});
</script>
</body>
</html>
