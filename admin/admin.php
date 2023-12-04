<?php
// Error reporting and PHP settings
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database configuration file
require_once '../config.php';

// Get the database connection
$conn = getDBConnection();

// Initialize variables
$successMessage = '';
$errorMessage = '';
$editPersonMode = false;
$editReasonMode = false;
$editPersonId = 0;
$editReasonId = 0;
$editPersonRow = [];
$editReasonRow = [];

// Handle Delete Reason
if (isset($_POST['delete_reason'])) {
    $idToDelete = $_POST['reason_id'];

    // Check if there are any visitors associated with this reason
    $stmtCheckVisitors = $conn->prepare("SELECT COUNT(*) as count FROM visitors WHERE visit_reason_id = ?");
    $stmtCheckVisitors->bind_param("i", $idToDelete);
    $stmtCheckVisitors->execute();
    $result = $stmtCheckVisitors->get_result();
    $row = $result->fetch_assoc();
    $count = $row['count'];
    $stmtCheckVisitors->close();

    if ($count === 0) {
        // No visitors associated, proceed with deletion
        $stmtDelete = $conn->prepare("DELETE FROM visit_reasons WHERE reason_id = ?");
        $stmtDelete->bind_param("i", $idToDelete);
        if ($stmtDelete->execute()) {
            $successMessage .= " Reason deleted successfully.";
        } else {
            $errorMessage .= " Error deleting reason: " . $stmtDelete->error;
        }
        $stmtDelete->close();
    } else {
        $errorMessage .= " There are visitors associated with this reason. ";
        $errorMessage .= "<form method='POST'>";
        $errorMessage .= "<input type='hidden' name='reason_id' value='$idToDelete'>";
        $errorMessage .= "<button type='submit' name='delete_reason_anyway'>Delete Anyway</button>";
        $errorMessage .= "</form>";
    }
}

// Handle Delete Reason Anyway
if (isset($_POST['delete_reason_anyway'])) {
    $idToDelete = $_POST['reason_id'];

    // Proceed with deletion even if visitors are associated
    $stmtDelete = $conn->prepare("DELETE FROM visit_reasons WHERE reason_id = ?");
    $stmtDelete->bind_param("i", $idToDelete);
    if ($stmtDelete->execute()) {
        $successMessage .= " Reason deleted successfully (even with associated visitors).";
    } else {
        $errorMessage .= " Error deleting reason: " . $stmtDelete->error;
    }
    $stmtDelete->close();
}

// Handle Edit Reason
if (isset($_POST['edit_reason'])) {
    $editReasonMode = true;
    $editReasonId = $_POST['reason_id'];
    $stmt = $conn->prepare("SELECT reason_description FROM visit_reasons WHERE reason_id = ?");
    $stmt->bind_param("i", $editReasonId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $editReasonRow = $result->fetch_assoc();
    }
    $stmt->close();
}

// Handle Update Reason
if (isset($_POST['update_reason'])) {
    $reasonIdToUpdate = $_POST['reason_id'];
    $updatedDescription = $conn->real_escape_string($_POST['updated_reason_description']);
    $stmt = $conn->prepare("UPDATE visit_reasons SET reason_description = ? WHERE reason_id = ?");
    $stmt->bind_param("si", $updatedDescription, $reasonIdToUpdate);
    if ($stmt->execute()) {
        $successMessage .= " Reason updated successfully.";
    } else {
        $errorMessage .= " Error updating reason: " . $stmt->error;
    }
    $stmt->close();
    $editReasonMode = false;
}

// Handle Delete Person
if (isset($_POST['delete_person'])) {
    $idToDelete = $_POST['person_id'];
    $stmt = $conn->prepare("DELETE FROM visiting_persons WHERE person_id = ?");
    $stmt->bind_param("i", $idToDelete);
    if ($stmt->execute()) {
        $successMessage .= " Person deleted successfully.";
    } else {
        $errorMessage .= " Error deleting person: " . $stmt->error;
    }
    $stmt->close();
}

// Handle Edit Person
if (isset($_POST['edit_person'])) {
    $editPersonMode = true;
    $editPersonId = $_POST['person_id'];
    $stmt = $conn->prepare("SELECT person_name FROM visiting_persons WHERE person_id = ?");
    $stmt->bind_param("i", $editPersonId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $editPersonRow = $result->fetch_assoc();
    }
    $stmt->close();
}

// Handle Update Person
if (isset($_POST['update_person'])) {
    $personIdToUpdate = $_POST['person_id'];
    $updatedName = $conn->real_escape_string($_POST['updated_person_name']);
    $stmt = $conn->prepare("UPDATE visiting_persons SET person_name = ? WHERE person_id = ?");
    $stmt->bind_param("si", $updatedName, $personIdToUpdate);
    if ($stmt->execute()) {
        $successMessage .= " Person updated successfully.";
    } else {
        $errorMessage .= " Error updating person: " . $stmt->error;
    }
    $stmt->close();
    $editPersonMode = false;
}

// Handle adding new visiting person and new visit reason
if (isset($_POST['add_new'])) {
    if (!empty($_POST['new_person_name'])) {
        $newPersonName = $conn->real_escape_string($_POST['new_person_name']);
        $stmt = $conn->prepare("INSERT INTO visiting_persons (person_name) VALUES (?)");
        $stmt->bind_param("s", $newPersonName);
        if ($stmt->execute()) {
            $successMessage .= " New person added successfully.";
        } else {
            $errorMessage .= " Error adding new person: " . $stmt->error;
        }
        $stmt->close();
    }

    if (!empty($_POST['new_reason_description'])) {
        $newReasonDescription = $conn->real_escape_string($_POST['new_reason_description']);
        $stmt = $conn->prepare("INSERT INTO visit_reasons (reason_description) VALUES (?)");
        $stmt->bind_param("s", $newReasonDescription);
        if ($stmt->execute()) {
            $successMessage .= " New reason added successfully.";
        } else {
            $errorMessage .= " Error adding new reason: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetching existing visiting persons and reasons for visit
$visitingPersonsQuery = "SELECT * FROM visiting_persons";
$visitingPersonsResult = $conn->query($visitingPersonsQuery);

$visitReasonsQuery = "SELECT * FROM visit_reasons";
$visitReasonsResult = $conn->query($visitReasonsQuery);

// Close the database connection
$conn->close();

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Management System</title>
<link rel="stylesheet" type="text/css" href="theme.php">    <style>
        /* Center-align the edit tables */
        table.edit-table {
            width: 40%;
            margin: 0 auto;
        }

        /* Adjust table styles for existing visiting persons and visit reasons */
        table.existing-table {
            width: 50%; /* Set both tables to be 50% of the page */
            border-collapse: collapse;
            margin: 0 auto; /* Center-align the tables */
        }

        table.existing-table th,
        table.existing-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        /* Ensure uniform width for action buttons */
        table.existing-table .actions {
            width: 150px;
        }
    </style>
</head>
<body>
    <h1>Visitor Management System</h1>
    <img src="../img/dnd-project-sm-logo.png">
    <h2><a href="/admin/" class="button">Return to Admin Dashboard</a></h2>

    <!-- Edit existing reason -->
    <?php if ($editReasonMode) : ?>
        <h2><b>Edit Reason</b></h2>
        <table class="centered-table edit-table">
            <tr>
                <th>Edit Reason</th>
            </tr>
            <tr>
                <td>
                    <form method="POST">
                        <input type="hidden" name="reason_id" value="<?php echo $editReasonId; ?>">
                        <label for="updated_reason_description">Updated Description:</label>
                        <input type="text" name="updated_reason_description" value="<?php echo $editReasonRow['reason_description']; ?>" required>
                        <button type="submit" name="update_reason">Update</button>
                    </form>
                </td>
            </tr>
        </table>
    <?php endif; ?>

    <!-- Edit existing person -->
    <?php if ($editPersonMode) : ?>
        <h2><b>Edit Person</b></h2>
        <table class="centered-table edit-table">
            <tr>
                <th>Edit Person</th>
            </tr>
            <tr>
                <td>
                    <form method="POST">
                        <input type="hidden" name="person_id" value="<?php echo $editPersonId; ?>">
                        <label for="updated_person_name">Updated Name:</label>
                        <input type="text" name="updated_person_name" value="<?php echo $editPersonRow['person_name']; ?>" required>
                        <button type="submit" name="update_person">Update</button>
                    </form>
                </td>
            </tr>
        </table>
    <?php endif; ?>

    <!-- Display success and error messages -->
    <?php if (!empty($successMessage)) : ?>
        <p style="color: green;"><?php echo $successMessage; ?></p>
    <?php endif; ?>
    <?php if (!empty($errorMessage)) : ?>
        <p style="color: red;"><?php echo $errorMessage; ?></p>
    <?php endif; ?>

    <!-- Add new visitor and reason form -->
    <h2><b>Add New Visiting Person or Reason for Visit</b></h2>
    <form method="POST">
        <input type="text" name="new_person_name" placeholder="Enter new person name">
        <input type="text" name="new_reason_description" placeholder="Enter new reason for visit">
        <input type="submit" name="add_new" value="Add New">
    </form>

    <!-- List of existing visiting persons -->
    <h2>Existing Visiting Persons</h2>
    <table class="existing-table">
        <tr>
            <th>Name</th>
            <th class="actions">Actions</th>
        </tr>
        <?php while ($row = $visitingPersonsResult->fetch_assoc()) : ?>
            <tr>
                <td><?php echo $row['person_name']; ?></td>
                <td class="actions">
                    <form method="POST">
                        <input type="hidden" name="person_id" value="<?php echo $row['person_id']; ?>">
                        <button type="submit" name="edit_person">Edit</button>
                        <button type="submit" name="delete_person" class="delete-button">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <!-- List of existing visit reasons -->
    <h2>Existing Visit Reasons</h2>
    <table class="existing-table">
        <tr>
            <th>Description</th>
            <th class="actions">Actions</th>
        </tr>
        <?php while ($row = $visitReasonsResult->fetch_assoc()) : ?>
            <tr>
                <td><?php echo $row['reason_description']; ?></td>
                <td class="actions">
                    <form method="POST">
                        <input type="hidden" name="reason_id" value="<?php echo $row['reason_id']; ?>">
                        <button type="submit" name="edit_reason">Edit</button>
                        <button type="submit" name="delete_reason" class="delete-button">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
