<?php
// Error reporting and PHP settings
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database configuration file
require_once './config.php';

// Get the database connection
$conn = getDBConnection();

// Define the function to fetch visitor data
function fetchVisitorData($conn, $phone) {
    $query = "SELECT first_name, last_name, phone, email, checkin_time, checkout_time FROM visitors WHERE phone = ? AND checkout_time IS NULL";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("s", $phone);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                return $result->fetch_assoc();
            }
        }
        $stmt->close();
    }
    return null;
}

$checkedOutVisitor = null;
$successMessage = '';
$errorMessage = '';

// Handle Check-Out
if (isset($_POST['checkout'])) {
    $checkout_phone = $conn->real_escape_string($_POST['checkout_phone']);
    $checkout_time = date('Y-m-d H:i:s');

    // Fetch visitor data before updating
    $checkedOutVisitor = fetchVisitorData($conn, $checkout_phone);

    if ($checkedOutVisitor) {
        // Update the checkout_time in the database
        $updateStmt = $conn->prepare("UPDATE visitors SET checkout_time = ? WHERE phone = ? AND checkout_time IS NULL");
        $updateStmt->bind_param("ss", $checkout_time, $checkout_phone);

        // Execute the update query
        if ($updateStmt->execute()) {
            // Check if the update was successful
            if ($updateStmt->affected_rows > 0) {
                // Update the checkout_time in the checkedOutVisitor array
                $checkedOutVisitor['checkout_time'] = $checkout_time;
                $successMessage = "Visitor checked out successfully!";
            } else {
                $errorMessage = "Error updating visitor data or no update needed.";
            }
        } else {
            echo "Error updating checkout_time: " . $updateStmt->error;
        }
        $updateStmt->close();
    } else {
        $errorMessage = "No visitor found with that phone number.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Visitor Check-In and Check-Out</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <meta charset="UTF-8">
<style>
        /* CSS for centering elements in the table cell */
        .center-cell {
            text-align: center;
            vertical-align: middle;
            display: flex;
            justify-content: center;
            align-items: center;
        }
    </style>

</head>
<body>

    <img src="../img/dnd-project-sm-logo.png">

    <h1>Visitor Management</h1>

    <!-- Separate Form for Visitor Check-Out -->
    <form method="POST">
        <table style="width:40%; margin: 0 auto;" border="1">
            <tr>
                <td><label for="checkout_phone">Check-Out (Phone):</label></td>
                <td class="center-cell">
                    <input type="text" name="checkout_phone" id="checkout_phone" placeholder="Phone Number" required>
                    <button type="submit" name="checkout">Log Out</button>
                </td>
            </tr>
        </table>
    </form>

<!-- Success message for Log Out -->
<?php if (!empty($successMessage)): ?>
    <p><?= $successMessage ?></p>
    <a href="./" class="button">Click Here to Finish Checking Out!</a>
<?php endif; ?>

<!-- Display "No visitor found with that phone number." message -->
<?php if (!empty($errorMessage)): ?>
    <p><?= $errorMessage ?></p>
<?php endif; ?>

<!-- Display Visitor Information -->
<?php if ($checkedOutVisitor): ?>
    <h2>Checked-Out Visitor Information</h2>
    <table border="1">
        <tr>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Check-Out Time</th>
        </tr>
        <tr>
            <td><?= htmlspecialchars($checkedOutVisitor['first_name']) ?></td>
            <td><?= htmlspecialchars($checkedOutVisitor['last_name']) ?></td>
            <td><?= htmlspecialchars($checkedOutVisitor['phone']) ?></td>
            <td><?= htmlspecialchars($checkedOutVisitor['email']) ?></td>
            <td><?= htmlspecialchars($checkedOutVisitor['checkout_time']) ?></td>
        </tr>
    </table>
<?php endif; ?>

</body>
</html>
