<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database configuration file with a relative path
require_once __DIR__ . '/../config.php';

// Get the database connection
$conn = getDBConnection();

// Function to fetch records with prepared statements
function fetchRecords($conn, $condition = '', $params = [], $types = '') {
    $sql = "SELECT v.first_name, v.last_name, v.checkin_time, v.checkout_time, vr.reason_description
            FROM visitors v
            LEFT JOIN visit_reasons vr ON v.visit_reason_id = vr.reason_id";
    if ($condition) {
        $sql .= " " . $condition;
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error in prepare statement: " . $conn->error);
    }

    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    $stmt->close();
    return $records;
}

// Fetch records based on user actions
if (isset($_POST['report_today'])) {
    $today = date('Y-m-d');
    $_SESSION['records'] = fetchRecords($conn, "WHERE DATE(v.checkin_time) = ?", [$today], 's');
} elseif (isset($_POST['get_report'])) {
    $selected_month = $_POST['month'];
    $selected_year = $_POST['year'];

    $start_date = sprintf('%04d-%02d-01', $selected_year, $selected_month);
    $end_date = date('Y-m-t', strtotime($start_date));

    $_SESSION['records'] = fetchRecords($conn, "WHERE DATE(v.checkin_time) BETWEEN ? AND ?", [$start_date, $end_date], 'ss');
} elseif (isset($_POST['search_submit']) && !empty($_POST['search'])) {
    $search_query = '%' . $_POST['search'] . '%';
    $_SESSION['records'] = fetchRecords($conn, "WHERE CONCAT(v.first_name, ' ', v.last_name) LIKE ?", [$search_query], 's');
}

// CSV generation function
function generateCSV($records) {
    $delimiter = ",";
    $newline = "\r\n";
    $csvContent = "First Name,Last Name,Reason,Check-In Time,Check-Out Time" . $newline;
    foreach ($records as $row) {
        $csvContent .= implode($delimiter, [
            $row['first_name'],
            $row['last_name'],
            $row['reason_description'],
            $row['checkin_time'],
            $row['checkout_time']
        ]) . $newline;
    }

    return $csvContent;
}

// Handle CSV download request
if (isset($_POST['download_report']) && !empty($_SESSION['records'])) {
    $csvContent = generateCSV($_SESSION['records']);
    header("Content-Type: application/csv");
    header("Content-Disposition: attachment; filename=\"report.csv\"");
    echo $csvContent;
    exit;
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Visitor Reports</title>
<link rel="stylesheet" type="text/css" href="theme.php"></head>
<body>
    <img src="../img/dnd-project-sm-logo.png">

    <h1>Visitor Reports</h1>
    <h2><a href="/admin/" class="button">Return to Admin Dashboard</a></h2>

    <form method="POST">
        <input type="submit" name="report_today" value="Get Reports for Today" class="button">

        <label for="month">Month:</label>
        <select id="month" name="month" required class="select-style">
            <?php for ($i = 1; $i <= 12; $i++): ?>
                <option value="<?php echo $i; ?>" <?php echo $i == date('m') ? 'selected' : ''; ?>>
                    <?php echo date('F', mktime(0, 0, 0, $i, 10)); ?>
                </option>
            <?php endfor; ?>
        </select>

        <label for="year">Year:</label>
        <select id="year" name="year" required class="select-style">
            <?php
            $currentYear = date('Y');
            for ($i = $currentYear; $i <= $currentYear + 5; $i++): ?>
                <option value="<?php echo $i; ?>" <?php echo $i == $currentYear ? 'selected' : ''; ?>>
                    <?php echo $i; ?>
                </option>
            <?php endfor; ?>
        </select>

        <input type="submit" name="get_report" value="Get Report" class="button">

        <input type="text" name="search" placeholder="Search by Name">
        <input type="submit" name="search_submit" value="Search" class="button">

        <!-- Button for CSV download -->
        <input type="submit" name="download_report" value="Download CSV" class="delete-button">
    </form>

    <!-- Report Table -->
    <?php if (!empty($_SESSION['records'])): ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Reason for Visit</th>
                    <th>Check-In Time</th>
                    <th>Check-Out Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($_SESSION['records'] as $record): ?>
                    <tr>
                        <td><?= htmlspecialchars($record['first_name']) . ' ' . htmlspecialchars($record['last_name']) ?></td>
                        <td><?= htmlspecialchars($record['reason_description']) ?></td>
                        <td><?= htmlspecialchars($record['checkin_time']) ?></td>
                        <td><?= htmlspecialchars($record['checkout_time']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No records to display.</p>
    <?php endif; ?>
</body>
</html>
