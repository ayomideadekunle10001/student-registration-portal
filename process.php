<?php
include('db_config.php');

// 1. REQUIREMENT: SEPARATE STUDENT VALIDATION & PROCESSING FUNCTIONS
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatStudentName($n) {
    return ucwords(strtolower(trim($n)));
}

function validateMatric($m) {
    return !empty($m) && (strlen($m) >= 5);
}

function validateLibraryServices($services_arr) {
    return is_array($services_arr) && (count($services_arr) >= 2);
}

function generateBorrowId($matric, $category) {
    $clean_mat = str_replace("/", "-", trim($matric));
    $cat_prefix = strtoupper(substr($category, 0, 4));
    return "LIB-" . $clean_mat . "-" . $cat_prefix;
}

function determineBorrowingStatus($days) {
    if ($days < 1) return "Invalid Borrow Duration";
    if ($days <= 7) return "Short-Term Borrowing Approved";
    if ($days <= 14) return "Standard Borrowing Approved";
    return "Extended Borrowing Requires Librarian Approval";
}

function displayServicesUsingLoop($services_arr) {
    foreach ($services_arr as $srv) {
        echo sanitizeInput($srv) . ", ";
    }
}

// 2. DISPATCH ROUTING FOR CREATE, UPDATE, DELETE, AND SEARCH OPERATIONS
echo "<link rel='stylesheet' href='style.css'><div class='container'>";

// OPERATION A: DELETE RECORD
if (isset($_GET['delete_id'])) {
    $del_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    
    $check = mysqli_query($conn, "SELECT * FROM borrow_requests WHERE borrowID = '$del_id'");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "DELETE FROM borrow_requests WHERE borrowID = '$del_id'");
        echo "<div class='success-box'>Request with ID $del_id has been completely deleted.</div>";
    } else {
        echo "<div class='error-box'>Error: Borrow ID does not exist.</div>";
    }
    echo "<a href='index.html'>Back to Main Form</a>";
    exit;
}

// OPERATION B: PROCESS EDITED UPDATE RECORD
if (isset($_POST['action']) && $_POST['action'] == "update") {
    $b_id = mysqli_real_escape_string($conn, $_POST['borrow_id']);
    $title = sanitizeInput($_POST['book_title']);
    $duration = (int)$_POST['duration'];
    $status = determineBorrowingStatus($duration);

    if (empty($title) || $duration == 0) {
        echo "<div class='error-box'>All update fields are required!</div>";
    } else {
        $q = "UPDATE borrow_requests SET bookTitle='$title', borrowDuration='$duration', status='$status' WHERE borrowID='$b_id'";
        if (mysqli_query($conn, $q)) {
            echo "<div class='success-box'>Record Updated successfully! New Status: $status</div>";
        } else {
            echo "Update Error: " . mysqli_error($conn);
        }
    }
    echo "<a href='index.html'>Back to Main Portal</a>";
    exit;
}

// OPERATION C: SEARCH REQUEST BY BORROW ID
if (isset($_GET['search_id'])) {
    $search = mysqli_real_escape_string($conn, trim($_GET['search_id']));
    $res = mysqli_query($conn, "SELECT * FROM borrow_requests WHERE borrowID = '$search'");

    if (mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        echo "<h2>Search Result for: " . $row['borrowID'] . "</h2>";
        echo "<b>Name:</b> " . $row['name'] . "<br>";
        echo "<b>Matric No:</b> " . $row['matric'] . "<br>";
        echo "<b>Department:</b> " . $row['department'] . "<br>";
        echo "<b>Level:</b> " . $row['level'] . "<br>";
        echo "<b>Email:</b> " . $row['email'] . "<br>";
        echo "<b>Phone:</b> " . $row['phone'] . "<br>";
        echo "<b>Book:</b> " . $row['bookTitle'] . " (" . $row['bookCode'] . ")<br>";
        echo "<b>Category:</b> " . $row['bookCategory'] . "<br>";
        echo "<b>Duration:</b> " . $row['borrowDuration'] . " Days<br>";
        echo "<b>Pickup Mode:</b> " . $row['pickupMode'] . "<br>";
        echo "<b>Services:</b> " . $row['services'] . "<br>";
        echo "<b>Status:</b> [" . $row['status'] . "]<br>";
        echo "<b>Date Submitted:</b> " . $row['created_at'] . "<br><br>";
        
        // Inline Modification UI triggers
        echo "<div style='background:#f0f0f0; padding:10px;'>";
        echo "<h3>Modify / Delete this Request</h3>";
        echo "<form action='process.php' method='POST'>";
        echo "<input type='hidden' name='action' value='update'>";
        echo "<input type='hidden' name='borrow_id' value='".$row['borrowID']."'>";
        echo "Update Book Title: <input type='text' name='book_title' value='".$row['bookTitle']."'><br><br>";
        echo "Update Duration (Days): <input type='text' name='duration' value='".$row['borrowDuration']."'><br><br>";
        echo "<input type='submit' value='Save Changes' style='background:blue; color:white;'> ";
        echo " <a href='process.php?delete_id=".$row['borrowID']."' style='color:red; font-weight:bold;'>Delete Request</a>";
        echo "</form></div>";
    } else {
        echo "<div class='error-box'>No record found matching Borrow ID: $search</div>";
    }
    echo "<br><a href='index.html'>Back to Form</a>";
    exit;
}

// OPERATION D: CREATE NEW ENTRY SUBMISSION
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == "create") {
    $errors = array();

    $name = sanitizeInput($_POST['name']);
    $matric = sanitizeInput($_POST['matric']);
    $dept = sanitizeInput($_POST['department']);
    $level = sanitizeInput($_POST['level']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $b_title = sanitizeInput($_POST['book_title']);
    $b_code = sanitizeInput($_POST['book_code']);
    $b_cat = sanitizeInput($_POST['book_category']);
    $duration = (int)sanitizeInput($_POST['duration']);
    $pickup = isset($_POST['pickup_mode']) ? sanitizeInput($_POST['pickup_mode']) : "";
    $srv_list = isset($_POST['services']) ? $_POST['services'] : array();

    // Validations
    if (empty($name) || empty($matric) || empty($dept) || empty($level) || empty($email) || empty($phone) || empty($b_title) || empty($b_code) || empty($b_cat) || empty($pickup)) {
        $errors[] = "All entry form fields are mandatory.";
    }

    $name = formatStudentName($name);
    if (strpos($name, " ") === false && !empty($name)) {
        $errors[] = "Full student name must consist of at least two words.";
    }

    if (!validateMatric($matric)) {
        $errors[] = "Your Matriculation identification format is invalid.";
    }

    if (!validateLibraryServices($srv_list)) {
        $errors[] = "You are required to select at least two specific library services.";
    }

    if (count($errors) > 0) {
        echo "<div class='error-box'><h3>Form Submission Faults:</h3>";
        foreach ($errors as $e) echo "- " . $e . "<br>";
        echo "</div><a href='index.html'>Go Back and Fix</a>";
    } else {
        // Prepare calculation metrics
        $borrow_id = generateBorrowId($matric, $b_cat);
        $borrow_status = determineBorrowingStatus($duration);
        $services_string = implode(", ", $srv_list);
        
        $m_name = mysqli_real_escape_string($conn, $name);
        $m_matric = mysqli_real_escape_string($conn, $matric);
        $m_dept = mysqli_real_escape_string($conn, $dept);
        $m_level = mysqli_real_escape_string($conn, $level);
        $m_email = mysqli_real_escape_string($conn, $email);
        $m_phone = mysqli_real_escape_string($conn, $phone);
        $m_title = mysqli_real_escape_string($conn, $b_title);
        $m_code = mysqli_real_escape_string($conn, $b_code);
        $m_cat = mysqli_real_escape_string($conn, $b_cat);
        $m_pickup = mysqli_real_escape_string($conn, $pickup);
        $m_srv = mysqli_real_escape_string($conn, $services_string);
        
        // Database Write Command
        $sql = "INSERT INTO borrow_requests (name, matric, department, level, email, phone, bookTitle, bookCode, bookCategory, borrowDuration, pickupMode, services, borrowID, status) 
                VALUES ('$m_name', '$m_matric', '$m_dept', '$m_level', '$m_email', '$m_phone', '$m_title', '$m_code', '$m_cat', $duration, '$m_pickup', '$m_srv', '$borrow_id', '$borrow_status')";
        
        if (mysqli_query($conn, $sql)) {
            echo "<div class='success-box'><h2>Request Successfully Registered!</h2></div>";
            echo "<h3>Borrowing Summary</h3>";
            echo "Student Full Name: " . $name . "<br>";
            echo "Matric Number: " . $matric . "<br>";
            echo "Department: " . $dept . "<br>";
            echo "Level: " . $level . "<br>";
            echo "Email Address: " . $email . "<br>";
            echo "Phone Number: " . $phone . "<br>";
            echo "Book Title: " . $b_title . "<br>";
            echo "Book Code: " . $b_code . "<br>";
            echo "Book Category: " . $b_cat . "<br>";
            echo "Borrow Duration: " . $duration . " Days<br>";
            echo "Preferred Pickup Mode: " . $pickup . "<br>";
            
            echo "Selected Library Services: ";
            displayServicesUsingLoop($srv_list);
            echo "<br>";
            
            echo "Total Number of Selected Services: " . count($srv_list) . "<br>";
            echo "<b>Generated Borrow ID: " . $borrow_id . "</b><br>";
            echo "Borrowing Status: " . $borrow_status . "<br>";
            echo "<i>Personalized Message: Welcome " . $name . ", your request for '" . $b_title . "' has been recorded successfully.</i><br><br>";
            echo "<a href='index.html'>Create Another Request</a>";
        } else {
            echo "<div class='error-box'>Database Error: " . mysqli_error($conn) . "</div>";
        }
    }
}
echo "</div>";
?>