<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database configuration file with a relative path
require_once __DIR__ . '/../config.php';

// Get the database connection
$conn = getDBConnection();

$search = $_GET['search'] ?? '';

function fetchRecords($conn, $search) {
    $searchTerm = '%' . $search . '%';
    $sql = $search ?
           "SELECT visitors.*, visit_reasons.reason_description
            FROM visitors
            LEFT JOIN visit_reasons ON visitors.visit_reason_id = visit_reasons.reason_id
            WHERE CONCAT(visitors.first_name, ' ', visitors.last_name) LIKE ?"
           :
           "SELECT visitors.*, visit_reasons.reason_description
            FROM visitors
            LEFT JOIN visit_reasons ON visitors.visit_reason_id = visit_reasons.reason_id";

    $stmt = $conn->prepare($sql);
    if ($search) {
        $stmt->bind_param("s", $searchTerm);
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

$editRecord = null;

// Handle Update/Delete/Check-in/Check-out requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;

    if ($id && isset($_POST['delete'])) {
        $stmt = $conn->prepare("DELETE FROM visitors WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    } elseif ($id && isset($_POST['edit'])) {
        $stmt = $conn->prepare("SELECT visitors.*, visit_reasons.reason_description FROM visitors LEFT JOIN visit_reasons ON visitors.visit_reason_id = visit_reasons.reason_id WHERE visitors.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $editRecord = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } elseif ($id && isset($_POST['save'])) {
        // Validate the "Reason for Visit" field
        if (empty($_POST['visit_reason_id']) || $_POST['visit_reason_id'] === "Select a reason") {
            $errorMessage = "Please select a reason for the visit.";
            // Redirect back to the edit form with an error message
            header("Location: edit.php?id=$id&error=" . urlencode($errorMessage));
            exit;
        } else {
            // Directly update the visitors table
            $stmt = $conn->prepare("UPDATE visitors SET first_name = ?, last_name = ?, visit_reason_id = ?, visiting_person_id = ?, checkin_time = ?, checkout_time = ? WHERE id = ?");
            $stmt->bind_param("ssiissi", $_POST['first_name'], $_POST['last_name'], $_POST['visit_reason_id'], $_POST['visiting_person_id'], $_POST['checkin_time'], $_POST['checkout_time'], $id);
            $stmt->execute();
            $stmt->close();
        }
    } elseif ($id && isset($_POST['checkin_now'])) {
        $current_time = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("UPDATE visitors SET checkin_time = ? WHERE id = ?");
        $stmt->bind_param("si", $current_time, $id);
        $stmt->execute();
        $stmt->close();
    } elseif ($id && isset($_POST['checkout_now'])) {
        $current_time = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("UPDATE visitors SET checkout_time = ? WHERE id = ?");
        $stmt->bind_param("si", $current_time, $id);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch pre-defined reasons from the database
$reasonsSql = "SELECT * FROM visit_reasons";
$reasonsResult = $conn->query($reasonsSql);

// Fetch pre-defined persons from the database
$personsSql = "SELECT * FROM visiting_persons";
$personsResult = $conn->query($personsSql);

// Fetch records after processing POST requests
$records = fetchRecords($conn, $search);

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Visitor Records</title>
<link rel="stylesheet" type="text/css" href="theme.php"><body>
    <img src="../img/dnd-project-sm-logo.png">
    <h2><a href="/admin/" class="button">Return to Admin Dashboard</a></h2>
    <h2>Edit Visitor Records</h2>
    <!-- Search Form -->
    <form method="GET">
        <input type="text" name="search" placeholder="Search by Name" value="<?= htmlspecialchars($search) ?>">
        <input type="submit" value="Search">
    </form>
    <?php if (isset($editRecord)): ?>
        <div class="edit-form">
            <h3>Edit Record</h3>
            <form method="post">
                <table style="width: 60%; margin: 0 auto;">
                    <tr>
                        <td>First Name:</td>
                        <td><input type="text" name="first_name" value="<?= htmlspecialchars($editRecord['first_name']) ?>"></td>
                    </tr>
                    <tr>
                        <td>Last Name:</td>
                        <td><input type="text" name="last_name" value="<?= htmlspecialchars($editRecord['last_name']) ?>"></td>
                    </tr>
<tr>
    <td>Person Visited:</td>
    <td>
        <select name="visiting_person_id">
            <option value="Select a person">Select a person</option>
            <?php foreach ($personsResult as $personRow): ?>
                <option value="<?= htmlspecialchars($personRow['person_id']) ?>" <?= (isset($editRecord) && $editRecord['visiting_person_id'] == $personRow['person_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($personRow['person_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </td>
</tr>
                    <tr>
                        <td>Reason for Visit:</td>
                        <td>
                            <select name="visit_reason_id">
                                <option value="Select a reason">Select a reason</option>
                                <?php foreach ($reasonsResult as $reasonRow): ?>
                                    <option value="<?= htmlspecialchars($reasonRow['reason_id']) ?>" <?= ($editRecord['visit_reason_id'] == $reasonRow['reason_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($reasonRow['reason_description']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>Check-In Time:</td>
                        <td><input type="text" name="checkin_time" value="<?= htmlspecialchars($editRecord['checkin_time']) ?>"></td>
                    </tr>
                    <tr>
                        <td>Check-Out Time:</td>
                        <td><input type="text" name="checkout_time" value="<?= htmlspecialchars($editRecord['checkout_time']) ?>"></td>
                    </tr>
                    <tr>
                        <td colspan="2" style="text-align: center;">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($editRecord['id']) ?>">
                            <input type="submit" name="save" value="Save Changes">
                            <input type="submit" name="checkin_now" value="Check-In Now">
                            <input type="submit" name="checkout_now" value="Check-Out Now">
                        </td>
                    </tr>
                </table>
            </form>
        </div>
    <?php endif; ?>
    
<!-- START Table to display records -->

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Person Visited</th> <!-- Add this column -->
            <th>Reason for Visit</th>
            <th>Check-In Time</th>
            <th>Check-Out Time</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($records as $record): ?>
            <tr>
                <td><?= htmlspecialchars($record['id']) ?></td>
                <td><?= htmlspecialchars($record['first_name']) . ' ' . htmlspecialchars($record['last_name']) ?></td>
                <td>
                    <?php
                    // Loop through the persons visited data and find the matching person
                    $visitedPerson = '';
                    foreach ($personsResult as $personRow) {
                        if ($record['visiting_person_id'] == $personRow['person_id']) {
                            $visitedPerson = htmlspecialchars($personRow['person_name']);
                            break;
                        }
                    }
                    echo $visitedPerson;
                    ?>
                </td>
                <td><?= isset($record['reason_description']) ? htmlspecialchars($record['reason_description']) : 'N/A' ?></td>
                <td><?= htmlspecialchars($record['checkin_time']) ?></td>
                <td><?= htmlspecialchars($record['checkout_time']) ?></td>
                <td>
                    <form method="post">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($record['id']) ?>">
                        <button type="submit" name="edit">Edit</button>
                        <button type="submit" name="delete" class="delete-button">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>


<!-- END Table to display records -->

</body>
</html>
