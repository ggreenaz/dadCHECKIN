<?php
// Error reporting and PHP settings
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database configuration file
require_once './config.php';

// Get the database connection
$conn = getDBConnection();

$checkedInVisitor = null;
$successMessage = '';
$errorMessage = '';

// Fetch visiting persons
$visitingPersonsQuery = "SELECT person_id, person_name FROM visiting_persons";
$visitingPersonsResult = $conn->query($visitingPersonsQuery);

$visitingPersons = [];
if ($visitingPersonsResult->num_rows > 0) {
    while ($row = $visitingPersonsResult->fetch_assoc()) {
        $visitingPersons[] = $row;
    }
}

// Fetch reasons for visit
$reasonsQuery = "SELECT reason_id, reason_description FROM visit_reasons";
$reasonsResult = $conn->query($reasonsQuery);

$visitReasons = [];
if ($reasonsResult->num_rows > 0) {
    while ($row = $reasonsResult->fetch_assoc()) {
        $visitReasons[] = $row;
    }
}

// Handle Check-In for New Visitor or Returning Student
$first_name = isset($_POST['first_name']) ? $conn->real_escape_string($_POST['first_name']) : '';
$last_name = isset($_POST['last_name']) ? $conn->real_escape_string($_POST['last_name']) : '';
$email = isset($_POST['email']) ? $conn->real_escape_string($_POST['email']) : '';

if (isset($_POST['checkin']) || isset($_POST['returning_checkin'])) {
    $phone = $conn->real_escape_string(isset($_POST['checkin']) ? $_POST['phone'] : $_POST['returning_phone']);
    $visiting_person_id = $conn->real_escape_string(isset($_POST['checkin']) ? $_POST['name'] : $_POST['returning_name']);
    $visit_reason_id = $conn->real_escape_string(isset($_POST['checkin']) ? $_POST['reason'] : $_POST['returning_reason']);
    $checkin_time = date('Y-m-d H:i:s');

    // Check if the student exists in the database based on their phone number
    $checkStudentQuery = "SELECT first_name, last_name, email FROM visitors WHERE phone = ?";
    $stmt = $conn->prepare($checkStudentQuery);
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($db_first_name, $db_last_name, $db_email);
        $stmt->fetch();
        // Use the database values for first name, last name, and email
        $first_name = $db_first_name;
        $last_name = $db_last_name;
        $email = $db_email;
    }

    $insertStmt = $conn->prepare("INSERT INTO visitors (first_name, last_name, phone, email, visiting_person_id, visit_reason_id, checkin_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $insertStmt->bind_param("sssssis", $first_name, $last_name, $phone, $email, $visiting_person_id, $visit_reason_id, $checkin_time);

    if ($insertStmt->execute()) {
        $successMessage = "New visitor checked in successfully!";
        $lastInsertedId = $conn->insert_id;
        $stmt = $conn->prepare("SELECT visitors.first_name, visitors.last_name, visitors.phone, visitors.email, visiting_persons.person_name AS visiting_name, visit_reasons.reason_description AS visiting_reason, visitors.checkin_time FROM visitors LEFT JOIN visiting_persons ON visitors.visiting_person_id = visiting_persons.person_id LEFT JOIN visit_reasons ON visitors.visit_reason_id = visit_reasons.reason_id WHERE visitors.id = ?");
        $stmt->bind_param("i", $lastInsertedId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $checkedInVisitor = $result->fetch_assoc();
        }
        $stmt->close();
    } else {
        $errorMessage = "Error: " . $insertStmt->error;
    }
    $insertStmt->close();
}

// Rest of your HTML code...

$conn->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <title>Visitor Check-In and Check-Out</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <meta charset="UTF-8">
</head>
<body>
    <img src="../img/dnd-project-sm-logo.png">

    <h1>Visitor Management</h1>


<!-- Red Button for Visitor Check-Out -->
<form method="POST" action="depart.php">
    <center>
        <button type="submit" class="red-button">VISITOR CHECK-OUT</button>
    </center>
</form>


    <!-- Display Visitor Information -->
    <?php if ($checkedInVisitor): ?>
        <h2>Visitor Information</h2>
        <center><table style="width:80%" border="1"><center>
            <tr>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Visiting</th>
                <th>Reason</th>
                <th>Check-In Time</th>
            </tr>
            <tr>
                <td><?= htmlspecialchars($checkedInVisitor['first_name']) ?></td>
                <td><?= htmlspecialchars($checkedInVisitor['last_name']) ?></td>
                <td><?= htmlspecialchars($checkedInVisitor['phone']) ?></td>
                <td><?= htmlspecialchars($checkedInVisitor['email']) ?></td>
                <td><?= htmlspecialchars($checkedInVisitor['visiting_name']) ?></td>
                <td><?= htmlspecialchars($checkedInVisitor['visiting_reason']) ?></td>
                <td><?= htmlspecialchars($checkedInVisitor['checkin_time']) ?></td>
            </tr>
        </table>
        <p><?= $successMessage ?></p>
        <a href="./" class="button">Click here to finish registration</a>
    <?php endif; ?>

    <!-- Display Error Message -->
    <?php if (!empty($errorMessage)): ?>
        <p><?= $errorMessage ?></p>
    <?php endif; ?>

<!-- Form for New Visitor Check-In -->
<form method="POST" action="">
    <center>
       <table style="width:80%" border="1">

            <tr>
                <td><label for="first_name">First Name:</label></td>
                <td><input type="text" name="first_name" id="first_name" placeholder="First Name" required></td>
            </tr>
            <tr>
                <td><label for="last_name">Last Name:</label></td>
                <td><input type="text" name="last_name" id="last_name" placeholder="Last Name" required></td>
            </tr>
            <tr>
                <td><label for="phone">Phone Number:</label></td>
                <td><input type="text" name="phone" id="phone" placeholder="Phone Number" required></td>
            </tr>
            <tr>
                <td><label for="email">Email:</label></td>
                <td><input type="email" name="email" id="email" placeholder="Email" required></td>
            </tr>
            <tr>
                <td><label for="name">Visiting (Name):</label></td>
                <td>
                    <select name="name" id="name" required style="width: auto;">
                        <option value="" disabled selected>Select Person</option>
                        <?php foreach ($visitingPersons as $person): ?>
                            <option value="<?= htmlspecialchars($person['person_id']); ?>">
                                <?= htmlspecialchars($person['person_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td><label for="reason">Reason for Visit:</label></td>
                <td>
                    <select name="reason" id="reason" required style="width: auto;">
                        <option value="" disabled selected>Select Reason</option>
                        <?php foreach ($visitReasons as $reason): ?>
                            <option value="<?= htmlspecialchars($reason['reason_id']); ?>">
                                <?= htmlspecialchars($reason['reason_description']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td colspan="2"><center>
		 <center><button type="submit" name="checkin">Submit New Visitor</button></center>
                </td>
            </tr>
        </table>
    </center>
</form>

<!-- Form for Returning Student Check-In -->
<form method="POST" action="">
    <center>
        <table style="width:80%" border="1">

            <tr>
                <td colspan="2" style="text-align: center; background-color: #585e6c; color: white;">
                    <h1>RETURNING STUDENTS</h1>
                </td>
            </tr>
            <tr>
                <td><label for="returning_phone">Returning Student (Phone):</label></td>
                <td><br><br>
                    <input type="text" name="returning_phone" id="returning_phone" placeholder="Phone Number" required>
                    <select name="returning_name" id="returning_name" required style="width: auto;">
                        <option value="" disabled selected>Select Person</option>
                        <?php foreach ($visitingPersons as $person): ?>
                            <option value="<?= htmlspecialchars($person['person_id']); ?>">
                                <?= htmlspecialchars($person['person_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="returning_reason" id="returning_reason" required style="width: auto;">
                        <option value="" disabled selected>Select Reason</option>
                        <?php foreach ($visitReasons as $reason): ?>
                            <option value="<?= htmlspecialchars($reason['reason_id']); ?>">
                                <?= htmlspecialchars($reason['reason_description']); ?>
                            </option>
                       <?php endforeach; ?>
                    </select>
                    <p>
		<center><button type="submit" name="returning_checkin">Check-in Returning Students</button></center>
		</center>
		</p>
                </td>
            </tr>
            <!-- Hidden fields for First Name, Last Name, and Email Address -->
            <input type="hidden" name="returning_first_name" id="returning_first_name">
            <input type="hidden" name="returning_last_name" id="returning_last_name">
            <input type="hidden" name="returning_email" id="returning_email">
        </table>
    </center>
</form>


</body>
</html>
