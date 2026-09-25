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

    // Temperature Logic
    if ($cat === 'Temperature') {
        // Convert to Celsius first
        if ($from === 'Fahrenheit') $celsius = ($val - 32) * 5 / 9;
        elseif ($from === 'Kelvin') $celsius = $val - 273.15;
        else $celsius = $val;

        // Convert Celsius to Target
        if ($to === 'Fahrenheit') return ($celsius * 9 / 5) + 32;
        if ($to === 'Kelvin') return $celsius + 273.15;
        return $celsius;
    }

    // Length Rates to Meters
    $length = [
        'Meters' => 1,
        'Kilometers' => 1000,
        'Centimeters' => 0.01,
        'Millimeters' => 0.001,
        'Miles' => 1609.34,
        'Feet' => 0.3048,
        'Inches' => 0.0254
    ];

    // Weight Rates to Grams
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
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="card">
    <h1 class="title"><?php echo $tab === 'binary' ? 'Binary Converter' : 'Converter'; ?></h1>

    <!-- Mode Selector Tabs -->
    <div class="tab-container">
        <a href="?tab=unit" class="tab-btn <?php echo $tab === 'unit' ? 'active' : ''; ?>">Unit Converter</a>
        <a href="?tab=binary" class="tab-btn <?php echo $tab === 'binary' ? 'active' : ''; ?>">Binary Converter</a>
    </div>

    <div class="divider"></div>

    <?php if ($tab === 'unit'): ?>
        <!-- UNIT CONVERTER FORM -->
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
        <!-- BINARY CONVERTER FORM -->
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
            <!-- BINARY RESULTS GRID -->
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

            <!-- VISUAL BINARY REPRESENTATION -->
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

    <!-- RECENT CONVERSIONS SECTION -->
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

<script src="script.js"></script>
</body>
</html>