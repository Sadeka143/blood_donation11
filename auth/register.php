<?php
// ======================================
// REGISTER PAGE
// Purpose:
// - Donor and recipient registration
// - Donor eligibility validation: age 18-65 and minimum 50kg
// - Secure insert using prepared statement
// - Uses existing database columns: address_line and weight_kg
// ======================================

include '../config/db.php';

$error = "";
$success = "";

// ======================================
// Get role from URL: register.php?role=donor / recipient
// ======================================
$selected_role = isset($_GET['role']) ? trim($_GET['role']) : "";

if(!in_array($selected_role, ['donor', 'recipient'])){
    $selected_role = "";
}

// ======================================
// Function: calculate age from date of birth
// ======================================
function calculateAgeFromDob($dateOfBirth){
    if(empty($dateOfBirth)){
        return null;
    }

    try{
        $dob = new DateTime($dateOfBirth);
        $today = new DateTime();
        return $today->diff($dob)->y;
    } catch(Exception $e){
        return null;
    }
}

// ======================================
// Handle registration form submission
// ======================================
if(isset($_POST['register'])){

    // Basic user details
    $name = trim($_POST['name'] ?? "");
    $email = trim($_POST['email'] ?? "");
    $contact_number = trim($_POST['contact_number'] ?? "");
    $plainPassword = $_POST['password'] ?? "";
    $role = trim($_POST['role'] ?? "");
    $blood_group = trim($_POST['blood_group'] ?? "");

    // Address details
    $address_line = trim($_POST['address_line'] ?? "");
    $city = trim($_POST['city'] ?? "");
    $zipcode = trim($_POST['zipcode'] ?? "");

    // Donor-only details
    $date_of_birth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
    $weight_kg = (isset($_POST['weight_kg']) && $_POST['weight_kg'] !== "") ? (float)$_POST['weight_kg'] : null;

    // System default values
    $availability = "available";
    $account_status = "active";
    $status = "active";
    $next_eligible_date = null;

    // Combined location used by dashboard/search pages
    $location_parts = array_filter([$address_line, $city, $zipcode], function($value){
        return trim($value) !== "";
    });
    $location = implode(", ", $location_parts);

    // Calculate donor age only when donor selected
    $age = null;
    if($role === "donor" && $date_of_birth !== null){
        $age = calculateAgeFromDob($date_of_birth);
    }

    // ======================================
    // Server-side validation
    // ======================================
    if($name == "" || $email == "" || $plainPassword == "" || $role == ""){
        $error = "Please fill in all required fields.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $error = "Invalid email format.";
    } elseif(!in_array($role, ['donor', 'recipient'])){
        $error = "Invalid role selected.";
    } elseif($blood_group == ""){
        $error = "Please select blood group.";
    } elseif($address_line == "" || $city == "" || $zipcode == ""){
        $error = "Address, city, and zipcode are required.";
    } elseif($role === "donor" && $date_of_birth === null){
        $error = "Date of birth is required for donors.";
    } elseif($role === "donor" && $age === null){
        $error = "Invalid date of birth.";
    } elseif($role === "donor" && ($age < 18 || $age > 65)){
        $error = "Donor age must be 18-65.";
    } elseif($role === "donor" && ($weight_kg === null || $weight_kg < 50)){
        $error = "Donor weight must be at least 50 kg.";
    } else {

        // ======================================
        // Check duplicate email
        // ======================================
        $checkStmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($checkStmt, "s", $email);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);

        if(mysqli_num_rows($checkResult) > 0){
            $error = "This email is already registered. Please login.";
        } else {

            // ======================================
            // Insert new user
            // Important:
            // - Database uses weight_kg, not weight
            // - Database uses address_line, not only address
            // ======================================
            $password = password_hash($plainPassword, PASSWORD_DEFAULT);

            $insertSql = "
                INSERT INTO users
                (
                    name,
                    email,
                    contact_number,
                    password,
                    role,
                    blood_group,
                    weight_kg,
                    location,
                    address_line,
                    city,
                    zipcode,
                    date_of_birth,
                    availability,
                    account_status,
                    status,
                    next_eligible_date
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";

            $stmt = mysqli_prepare($conn, $insertSql);

            mysqli_stmt_bind_param(
                $stmt,
                "ssssssdsssssssss",
                $name,
                $email,
                $contact_number,
                $password,
                $role,
                $blood_group,
                $weight_kg,
                $location,
                $address_line,
                $city,
                $zipcode,
                $date_of_birth,
                $availability,
                $account_status,
                $status,
                $next_eligible_date
            );

            if(mysqli_stmt_execute($stmt)){
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Registration failed: " . mysqli_error($conn);
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($checkStmt);
    }
}

// ======================================
// Dynamic register heading
// ======================================
$role_title = "Create Account";
$role_subtitle = "Register to use the system";

if($selected_role == "donor"){
    $role_title = "Donor Registration";
    $role_subtitle = "Create a donor account to donate blood";
} elseif($selected_role == "recipient"){
    $role_title = "Recipient Registration";
    $role_subtitle = "Create a recipient account to request blood";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - Blood Donation Management System</title>

    <!-- Main shared stylesheet -->
    <link rel="stylesheet" href="../dashboard/assets/style.css">
</head>

<body class="register-page-body">

<div class="register-page-wrap">

    <div class="register-card">

        <!-- ==============================
             BRAND SECTION
        ============================== -->
        <div class="auth-brand-block">
            <div class="logo auth-logo">🩸</div>
            <h2 class="auth-system-title">Blood Donation Management System</h2>
            <p class="auth-system-subtitle">Donate Blood, Save Lives</p>
        </div>

        <!-- ==============================
             REGISTER HEADER
        ============================== -->
        <div class="login-header-box">
            <h2><?php echo htmlspecialchars($role_title); ?></h2>
            <p><?php echo htmlspecialchars($role_subtitle); ?></p>
        </div>

        <!-- ==============================
             ERROR / SUCCESS MESSAGE
        ============================== -->
        <?php if($error != ""){ ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php } ?>

        <?php if($success != ""){ ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php } ?>

        <!-- ==============================
             REGISTER FORM
        ============================== -->
        <form method="POST" class="form" id="registerForm">

            <!-- Full name -->
            <label>Full Name</label>
            <input
                class="input"
                type="text"
                name="name"
                required
                value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
            >

            <!-- Email -->
            <label>Email</label>
            <input
                class="input"
                type="email"
                name="email"
                required
                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
            >

            <!-- Contact number -->
            <label>Contact Number</label>
            <input
                class="input"
                type="text"
                name="contact_number"
                placeholder="e.g., 01700000000"
                value="<?php echo htmlspecialchars($_POST['contact_number'] ?? ''); ?>"
            >

            <!-- Password -->
            <label>Password</label>
            <input
                class="input"
                type="password"
                name="password"
                required
            >

            <!-- Role -->
            <label>Role</label>
            <select class="select" name="role" required id="roleSelect">
                <option value="">Select Role</option>
                <option value="donor" <?php if(($_POST['role'] ?? $selected_role) == 'donor') echo 'selected'; ?>>Donor</option>
                <option value="recipient" <?php if(($_POST['role'] ?? $selected_role) == 'recipient') echo 'selected'; ?>>Recipient</option>
            </select>

            <!-- Blood group -->
            <label>Blood Group</label>
            <select class="select" name="blood_group" required>
                <option value="">Select Blood Group</option>
                <?php
                $groups = ['A+','A-','B+','B-','O+','O-','AB+','AB-'];
                foreach($groups as $group){
                    $selected = (($_POST['blood_group'] ?? '') == $group) ? 'selected' : '';
                    echo "<option value='".htmlspecialchars($group)."' $selected>".htmlspecialchars($group)."</option>";
                }
                ?>
            </select>

            <!-- Address line -->
            <label>Address</label>
            <input
                class="input"
                type="text"
                name="address_line"
                required
                placeholder="e.g., Gammel Køge Landevej 501"
                value="<?php echo htmlspecialchars($_POST['address_line'] ?? ''); ?>"
            >

            <!-- City -->
            <label>City</label>
            <input
                class="input"
                type="text"
                name="city"
                required
                placeholder="e.g., Brøndby Strand"
                value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>"
            >

            <!-- Zipcode -->
            <label>Zipcode</label>
            <input
                class="input"
                type="text"
                name="zipcode"
                required
                placeholder="e.g., 2660"
                value="<?php echo htmlspecialchars($_POST['zipcode'] ?? ''); ?>"
            >

            <!-- ==============================
                 DONOR ELIGIBILITY SECTION
                 Only visible when role = donor
            ============================== -->
            <div id="donorFields" class="<?php echo (($_POST['role'] ?? $selected_role) == 'donor') ? '' : 'hidden-block'; ?>">

                <!-- Donor date of birth -->
                <label>Date of Birth</label>
                <input
                    class="input"
                    type="date"
                    name="date_of_birth"
                    id="date_of_birth"
                    value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>"
                >

                <!-- Donor weight -->
                <label>Weight (kg)</label>
                <input
                    class="input"
                    type="number"
                    step="0.1"
                    name="weight_kg"
                    id="weight_kg"
                    placeholder="e.g., 55"
                    value="<?php echo htmlspecialchars($_POST['weight_kg'] ?? ''); ?>"
                >

                <p class="small-note">
                    Donor must be 18-65 years old and at least 50 kg.
                </p>
            </div>

            <!-- Submit button -->
            <button class="btn btn-primary" type="submit" name="register">Register</button>
        </form>

        <br>

        <!-- Login link -->
        <p class="auth-text-row">
            Already registered?
            <?php if($selected_role == 'donor' || $selected_role == 'recipient') { ?>
                <a class="link" href="login.php?role=<?php echo urlencode($selected_role); ?>">Login here</a>
            <?php } else { ?>
                <a class="link" href="login.php">Login here</a>
            <?php } ?>
        </p>

        <!-- Back link -->
        <p class="auth-text-row">
            <a class="link" href="index.php">← Back to role selection</a>
        </p>

    </div>
</div>

<!-- ==============================
     FRONTEND DONOR VALIDATION
     Purpose:
     - Show donor fields only for donor
     - Validate donor age and weight before submission
============================== -->
<script>
const roleSelect = document.getElementById('roleSelect');
const donorFields = document.getElementById('donorFields');
const registerForm = document.getElementById('registerForm');
const dobInput = document.getElementById('date_of_birth');
const weightInput = document.getElementById('weight_kg');

function toggleDonorFields(){
    if(roleSelect.value === 'donor'){
        donorFields.classList.remove('hidden-block');
    } else {
        donorFields.classList.add('hidden-block');
    }
}

roleSelect.addEventListener('change', toggleDonorFields);
toggleDonorFields();

registerForm.addEventListener('submit', function(e){
    if(roleSelect.value === 'donor'){

        const dobValue = dobInput.value;
        const weightValue = parseFloat(weightInput.value);

        if(!dobValue){
            alert('Date of birth is required for donors.');
            e.preventDefault();
            return;
        }

        const today = new Date();
        const dob = new Date(dobValue);

        let age = today.getFullYear() - dob.getFullYear();
        const monthDiff = today.getMonth() - dob.getMonth();

        if(monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())){
            age--;
        }

        if(age < 18 || age > 65){
            alert('Donor age must be 18-65.');
            e.preventDefault();
            return;
        }

        if(isNaN(weightValue) || weightValue < 50){
            alert('Donor weight must be at least 50 kg.');
            e.preventDefault();
            return;
        }
    }
});
</script>

</body>
</html>