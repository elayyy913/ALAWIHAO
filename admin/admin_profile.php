<?php
session_start();
include '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$message = "";
$message_type = "success";

/*
| PROFILE EDIT VERIFICATION
*/
if (!isset($_SESSION['profile_edit_verified'])) {
    $_SESSION['profile_edit_verified'] = false;
}

if (
    isset($_SESSION['profile_edit_verified_time']) &&
    time() - $_SESSION['profile_edit_verified_time'] > 600
) {
    $_SESSION['profile_edit_verified'] = false;
    unset($_SESSION['profile_edit_verified_time']);
}

/*
| VERIFY PASSWORD
*/
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['verify_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    if ($stmt) {

        $stmt->bind_param("i", $admin_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        $stmt->close();
        $password_valid = false;
        if ($user) {

            /*
             * Normal / recommended:
             * password_hash() + password_verify()
             */
            if (password_verify($current_password, $user['password'])) {
                $password_valid = true;
            }

            /*
             * Fallback kung may existing account na plain text
             * ang dating password.
             *
             * Kapag tama ang plain-text password,
             * automatically ire-rehash natin ito.
             */
            elseif (hash_equals((string)$user['password'], $current_password)) {
                $new_hash = password_hash(
                    $current_password,
                    PASSWORD_DEFAULT
                );
                $update_password = $conn->prepare(
                    "UPDATE users SET password = ? WHERE id = ?"
                );
                if ($update_password) {

                    $update_password->bind_param(
                        "si",
                        $new_hash,
                        $admin_id
                    );
                    $update_password->execute();
                    $update_password->close();

                    $password_valid = true;
                }
            }
        }

        if ($password_valid) {

            $_SESSION['profile_edit_verified'] = true;
            $_SESSION['profile_edit_verified_time'] = time();

            $message = "Password verified. You can now edit your profile.";
            $message_type = "success";

        } else {
            $message = "Incorrect password. You cannot edit your profile.";
            $message_type = "error";
        }
    } else {
        $message = "Unable to verify password.";
        $message_type = "error";
    }
}


/*
SAVE PROFILE
*/
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_profile'])) {

    /*
     * SERVER-SIDE SECURITY CHECK
     */
    if (
        !isset($_SESSION['profile_edit_verified']) ||
        $_SESSION['profile_edit_verified'] !== true
    ) {

        $message = "Please verify your password before editing your profile.";
        $message_type = "error";

    } else {

        $fname = trim($_POST['first_name'] ?? '');
        $lname = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contact = trim($_POST['contact_number'] ?? '');
        $birthday = !empty($_POST['birthday']) ? $_POST['birthday'] : null;
        $gender = trim($_POST['gender'] ?? '');
        $address = trim($_POST['address'] ?? '');

        /*
         * Basic validation
         */
        if ($fname === '' || $lname === '' || $email === '') {

            $message = "Please fill in all required fields.";
            $message_type = "error";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $message = "Please enter a valid email address.";
            $message_type = "error";

        } else {

            /*
             * Get old profile picture
             */
            $old_picture = "";

            $old_stmt = $conn->prepare(
                "SELECT profile_picture FROM users WHERE id = ?"
            );

            if ($old_stmt) {

                $old_stmt->bind_param("i", $admin_id);
                $old_stmt->execute();

                $old_result = $old_stmt->get_result();
                $old_user = $old_result->fetch_assoc();

                $old_picture = $old_user['profile_picture'] ?? '';

                $old_stmt->close();
            }
            /*
             * PROFILE PICTURE
             */
            $profile_picture = $old_picture;

            if (
                isset($_FILES['profile_picture']) &&
                $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE
            ) {

                if ($_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {

                    /*
                     * Maximum 5MB
                     */
                    if ($_FILES['profile_picture']['size'] > 5 * 1024 * 1024) {

                        $message = "Profile picture must not exceed 5MB.";
                        $message_type = "error";

                    } else {

                        $tmp_file = $_FILES['profile_picture']['tmp_name'];

                        /*
                         * Check if actual images
                         */
                        $images_info = @getimagesize($tmp_file);

                        if ($images_info === false) {

                            $message = "Please upload a valid images.";
                            $message_type = "error";

                        } else {

                            $allowed_types = [
                                imagesTYPE_JPEG => 'jpg',
                                imagesTYPE_PNG  => 'png',
                                imagesTYPE_WEBP => 'webp'
                            ];

                            $images_type = $images_info[2];

                            if (!isset($allowed_types[$images_type])) {

                                $message = "Only JPG, PNG, and WEBP images are allowed.";
                                $message_type = "error";

                            } else {

                                /*
                                 * Create folder if it doesn't exist
                                 */
                                $upload_dir = __DIR__ . "/uploads/profile/";

                                if (!is_dir($upload_dir)) {
                                    mkdir($upload_dir, 0755, true);
                                }

                                /*
                                 * Unique filename
                                 */
                                $extension = $allowed_types[$images_type];

                                $new_filename =
                                    "profile_" .
                                    $admin_id .
                                    "_" .
                                    time() .
                                    "_" .
                                    bin2hex(random_bytes(4)) .
                                    "." .
                                    $extension;

                                $destination = $upload_dir . $new_filename;

                                if (move_uploaded_file($tmp_file, $destination)) {

                                    $profile_picture =
                                        "uploads/profile/" . $new_filename;

                                    /*
                                     * Delete old picture
                                     */
                                    if (!empty($old_picture)) {

                                        $old_file = __DIR__ . "/" . $old_picture;

                                        if (
                                            file_exists($old_file) &&
                                            is_file($old_file)
                                        ) {
                                            @unlink($old_file);
                                        }
                                    }

                                } else {

                                    $message = "Failed to upload profile picture.";
                                    $message_type = "error";
                                }
                            }
                        }
                    }

                } else {

                    $message = "There was a problem uploading the profile picture.";
                    $message_type = "error";
                }
            }

            /*
             * SAVE TO DATABASE
             */
            if ($message_type === "success") {

                $update_sql = "UPDATE users SET
                                first_name = ?,
                                last_name = ?,
                                email = ?,
                                contact_number = ?,
                                birthday = ?,
                                gender = ?,
                                address = ?,
                                profile_picture = ?
                               WHERE id = ?";

                $stmt = $conn->prepare($update_sql);

                if ($stmt) {

                    $stmt->bind_param(
                        "ssssssssi",
                        $fname,
                        $lname,
                        $email,
                        $contact,
                        $birthday,
                        $gender,
                        $address,
                        $profile_picture,
                        $admin_id
                    );

                    if ($stmt->execute()) {

                        $message = "Profile updated successfully!";
                        $message_type = "success";

                        /*
                         * Lock editing again after save
                         */
                        $_SESSION['profile_edit_verified'] = false;
                        unset($_SESSION['profile_edit_verified_time']);

                    } else {

                        $message = "Error updating profile: " . $stmt->error;
                        $message_type = "error";
                    }

                    $stmt->close();

                } else {

                    $message = "Error preparing update: " . $conn->error;
                    $message_type = "error";
                }
            }
        }
    }
}

/*
GET ADMIN PROFILE
*/
$sql = "SELECT *
        FROM users
        WHERE id = ?";

$stmt = $conn->prepare($sql);

if ($stmt) {

    $stmt->bind_param("i", $admin_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    $stmt->close();

} else {

    $admin = [];
}

/*
| PROFILE PICTURE DISPLAY
*/
$profile_picture = $admin['profile_picture'] ?? '';
if (!empty($profile_picture)) {
    $profile_images = $profile_picture;
} else {
    $profile_images = "";
}
$is_verified =
    isset($_SESSION['profile_edit_verified']) &&
    $_SESSION['profile_edit_verified'] === true;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Admin Profile | Alawihao Health</title>
    <style>
        :root {
            --sage: #8DAE74;
            --dark-sage: #6B8E55;
            --light-bg: #F8FAFC;
            --card-bg: #FFFFFF;
            --text-main: #1E293B;
            --text-muted: #64748B;
            --border-color: #E2E8F0;
            --danger: #DC2626;
            --success: #16A34A;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            background-color: var(--light-bg);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
        }

        #main {
            flex: 1;
            padding: 40px;
            overflow-y: auto;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }
        .profile-card {
            background: var(--card-bg);
            width: 100%;
            max-width: 700px;
            padding: 35px;
            border-radius: 14px;
            box-shadow:
                0 4px 12px rgba(0,0,0,0.06);
            border: 1px solid var(--border-color);
            border-top: 4px solid var(--sage);
        }
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .profile-header h2 {
            color: var(--dark-sage);
            margin: 0 0 8px;
            font-size: 1.5rem;
        }
        .profile-header p {
            margin: 0;
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        /*
        PROFILE PICTURE
        */

        .profile-picture-container {
            text-align: center;
            margin-bottom: 30px;
        }
        .profile-picture {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--sage);
            background: #F1F5F9;
        }
        .default-profile {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            background: #E2E8F0;
            border: 4px solid var(--sage);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 45px;
            color: var(--text-muted);
        }
        .picture-label {
            display: none;
            margin-top: 12px;
            cursor: pointer;
            color: var(--dark-sage);
            font-weight: 600;
            font-size: 0.85rem;
        }
        .picture-label:hover {
            text-decoration: underline;
        }

        /*
        FORM
        */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }
        .form-group {
            margin-bottom: 4px;
        }
        .form-group.full {
            grid-column: 1 / -1;
        }
        .form-group label {
            display: block;
            color: var(--text-muted);
            margin-bottom: 7px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            font-weight: 700;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 11px 13px;
            border: 1px solid var(--border-color);
            border-radius: 7px;
            background-color: #F8FAFC;
            font-family: inherit;
            font-size: 0.875rem;
            color: var(--text-main);
            outline: none;
            transition: 0.2s;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .form-group input[readonly],
        .form-group textarea[readonly],
        .form-group select:disabled {
            cursor: default;
            background-color: #F8FAFC;
        }
        .editing .form-group input:not([readonly]),
        .editing .form-group textarea:not([readonly]),
        .editing .form-group select:not(:disabled) {
            background-color: #FFFFFF;
            border-color: var(--sage);
            box-shadow:
                0 0 0 3px rgba(141,174,116,0.12);
        }

        /*
        BUTTONS
        */
        .btn-container {
            display: flex;
            gap: 12px;
            margin-top: 28px;
        }
        .edit-btn,
        .save-btn,
        .cancel-btn {
            flex: 1;
            border: none;
            padding: 12px 16px;
            border-radius: 7px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.875rem;
            transition: 0.2s;
        }
        .edit-btn,
        .save-btn {
            background-color: var(--dark-sage);
            color: white;
        }
        .edit-btn:hover,
        .save-btn:hover {
            background-color: #587645;
        }
        .cancel-btn {
            background-color: #E2E8F0;
            color: #475569;
            display: none;
        }
        .cancel-btn:hover {
            background-color: #CBD5E1;
        }
        .save-btn {
            display: none;
        }

        /*
        | ALERT
        */
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .success-alert {
            background: #F0FDF4;
            color: var(--success);
            border: 1px solid #DCFCE7;
        }
        .error-alert {
            background: #FEF2F2;
            color: var(--danger);
            border: 1px solid #FECACA;
        }

        /*
        | PASSWORD MODAL
        */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(15,23,42,0.55);
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-box {
            width: 100%;
            max-width: 420px;
            background: white;
            border-radius: 14px;
            padding: 30px;
            box-shadow:
                0 15px 40px rgba(0,0,0,0.2);
        }
        .modal-box h3 {
            margin: 0 0 8px;
            color: var(--dark-sage);
        }
        .modal-box p {
            margin: 0 0 20px;
            color: var(--text-muted);
            font-size: 0.85rem;
            line-height: 1.5;
        }
        .modal-box input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 7px;
            outline: none;
            margin-bottom: 18px;
        }
        .modal-box input:focus {
            border-color: var(--sage);
            box-shadow:
                0 0 0 3px rgba(141,174,116,0.12);
        }
        .modal-buttons {
            display: flex;
            gap: 10px;
        }
        .modal-buttons button {
            flex: 1;
            padding: 11px;
            border: none;
            border-radius: 7px;
            cursor: pointer;
            font-weight: 600;
        }
        .verify-btn {
            background: var(--dark-sage);
            color: white;
        }
        .modal-cancel {
            background: #E2E8F0;
            color: #475569;
        }

        /*
        RESPONSIVE
        */
        @media (max-width: 650px) {
            #main {
                padding: 20px;
            }
            .profile-card {
                padding: 25px 20px;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
            .form-group.full {
                grid-column: auto;
            }
            .btn-container {
                flex-direction: column;

            }
        }
    </style>
</head>
<body>

<?php include 'admin_sidebar.php'; ?>
<div id="main">
    <div class="profile-card" id="profileCard">
        <div class="profile-header">
            <h2>Admin Profile</h2>
            <p>Manage your personal information and profile picture.</p>
        </div>


        <!-- ALERT -->

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $message_type === 'error'
                ? 'error-alert'
                : 'success-alert'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>


        <!-- PROFILE PICTURE -->

        <div class="profile-picture-container">
            <?php if (!empty($profile_images)): ?>
                <img
                    src="<?php echo htmlspecialchars($profile_images); ?>"
                    class="profile-picture"
                    id="profilePreview"
                    alt="Profile Picture"
                >

            <?php else: ?>
                <div
                    class="default-profile"
                    id="defaultProfile"
                >
                    👤
                </div>

                <img
                    src=""
                    class="profile-picture"
                    id="profilePreview"
                    style="display:none;"
                    alt="Profile Picture"
                >
            <?php endif; ?>

            <label
                for="profile_picture"
                class="picture-label"
                id="pictureLabel"
            >
                Change Profile Picture
            </label>

        </div>


        <!-- PROFILE FORM -->

        <form
            id="profileForm"
            method="POST"
            enctype="multipart/form-data"
        >
            <div class="form-grid">

                <!-- FIRST NAME -->

                <div class="form-group">
                    <label>First Name</label>

                    <input
                        type="text"
                        name="first_name"
                        id="fname"
                        value="<?php echo htmlspecialchars(
                            $admin['first_name'] ?? ''
                        ); ?>"
                        readonly
                        required
                    >

                </div>

                <!-- LAST NAME -->

                <div class="form-group">
                    <label>Last Name</label>

                    <input
                        type="text"
                        name="last_name"
                        id="lname"
                        value="<?php echo htmlspecialchars(
                            $admin['last_name'] ?? ''
                        ); ?>"
                        readonly
                        required
                    >
                </div>

                <!-- EMAIL -->

                <div class="form-group">
                    <label>Email Address</label>

                    <input
                        type="email"
                        name="email"
                        id="email"
                        value="<?php echo htmlspecialchars(
                            $admin['email'] ?? ''
                        ); ?>"
                        readonly
                        required
                    >
                </div>

                <!-- CONTACT -->

                <div class="form-group">
                    <label>Contact Number</label>

                    <input
                        type="text"
                        name="contact_number"
                        id="contact"
                        value="<?php echo htmlspecialchars(
                            $admin['contact_number'] ?? ''
                        ); ?>"
                        placeholder="09123456789"
                        maxlength="11"
                        pattern="^09\d{9}$"
                        title="Format: 09XXXXXXXXX"
                        readonly
                        required
                        oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                    >
                </div>

                <!-- BIRTHDAY -->

                <div class="form-group">
                    <label>Birthday</label>

                    <input
                        type="date"
                        name="birthday"
                        id="birthday"
                        value="<?php echo htmlspecialchars(
                            $admin['birthday'] ?? ''
                        ); ?>"
                        readonly
                    >
                </div>

                <!-- GENDER -->

                <div class="form-group">
                    <label>Gender</label>

                    <select
                        name="gender"
                        id="gender"
                        disabled
                    >
                        <option value="">
                            Select Gender
                        </option>

                        <option
                            value="Male"
                            <?php echo (($admin['gender'] ?? '') === 'Male')
                                ? 'selected'
                                : ''; ?>
                        >
                            Male
                        </option>

                        <option
                            value="Female"
                            <?php echo (($admin['gender'] ?? '') === 'Female')
                                ? 'selected'
                                : ''; ?>
                        >
                            Female
                        </option>

                        <option
                            value="Other"
                            <?php echo (($admin['gender'] ?? '') === 'Other')
                                ? 'selected'
                                : ''; ?>
                        >
                            Other
                        </option>
                    </select>
                </div>

                <!-- ADDRESS -->

                <div class="form-group full">
                    <label>Address</label>
                    <textarea
                        name="address"
                        id="address"
                        readonly
                        placeholder="Enter your complete address"
                    ><?php echo htmlspecialchars(
                        $admin['address'] ?? ''
                    ); ?></textarea>
                </div>

                <!-- POSITION -->

                <div class="form-group full">
                    <label>Position</label>
                    <input
                        type="text"
                        value="<?php echo htmlspecialchars(
                            $admin['position'] ?? 'Administrator'
                        ); ?>"
                        readonly
                    >
                </div>

                <!-- HIDDEN PROFILE PICTURE -->

                <input
                    type="file"
                    name="profile_picture"
                    id="profile_picture"
                    accept="images/jpeg,images/png,images/webp"
                    style="display:none;"
                >
            </div>

            <!-- BUTTONS -->

            <div class="btn-container">

                <button
                    type="button"
                    id="editBtn"
                    class="edit-btn"
                    onclick="openPasswordModal()"
                >
                    Edit Profile
                </button>

                <button
                    type="submit"
                    name="save_profile"
                    id="saveBtn"
                    class="save-btn"
                >
                    Save Changes
                </button>

                <button
                    type="button"
                    id="cancelBtn"
                    class="cancel-btn"
                    onclick="disableEdit()"
                >
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- PASSWORD VERIFICATION MODAL -->

<div
    class="modal"
    id="passwordModal"
>
    <div class="modal-box">
        <h3>Verify Your Password</h3>
        <p>
            For your security, please enter your current password
            before editing your profile.
        </p>

        <form method="POST">
            <input
                type="password"
                name="current_password"
                placeholder="Enter your current password"
                required
                autocomplete="current-password"
            >
            <div class="modal-buttons">
                <button
                    type="button"
                    class="modal-cancel"
                    onclick="closePasswordModal()"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    name="verify_password"
                    class="verify-btn"
                >
                    Verify Password
                </button>
            </div>
        </form>
    </div>
</div>
<script>

/*
|OPEN PASSWORD MODAL
*/

function openPasswordModal() {
    document.getElementById("passwordModal").style.display = "flex";

}

/*
CLOSE PASSWORD MODAL
*/

function closePasswordModal() {
    document.getElementById("passwordModal").style.display = "none";

}

/*
ENABLE EDIT MODE
*/

function enableEdit() {
    document.getElementById("fname").readOnly = false;
    document.getElementById("lname").readOnly = false;
    document.getElementById("email").readOnly = false;
    document.getElementById("contact").readOnly = false;
    document.getElementById("birthday").readOnly = false;
    document.getElementById("gender").disabled = false;
    document.getElementById("address").readOnly = false;
    document.getElementById("profile_picture").style.display = "none";
    document.getElementById("pictureLabel").style.display = "inline-block";
    document.getElementById("profileCard").classList.add("editing");
    document.getElementById("editBtn").style.display = "none";
    document.getElementById("saveBtn").style.display = "block";
    document.getElementById("cancelBtn").style.display = "block";
    document.getElementById("fname").focus();

}

/*
DISABLE EDIT MODE
*/

function disableEdit() {
    document.getElementById("fname").readOnly = true;
    document.getElementById("lname").readOnly = true;
    document.getElementById("email").readOnly = true;
    document.getElementById("contact").readOnly = true;
    document.getElementById("birthday").readOnly = true;
    document.getElementById("gender").disabled = true;
    document.getElementById("address").readOnly = true;
    document.getElementById("pictureLabel").style.display = "none";
    document.getElementById("profileCard").classList.remove("editing");
    document.getElementById("editBtn").style.display = "block";
    document.getElementById("saveBtn").style.display = "none";
    document.getElementById("cancelBtn").style.display = "none";
    /*
    Reload page to restore original values
     */
    window.location.reload();

}

/*
PROFILE PICTURE PREVIEW
*/

document
    .getElementById("profile_picture")
    .addEventListener("change", function(event) {

        const file = event.target.files[0];
        if (!file) {
            return;
        }

        /*
        Maximum 5MB
         */
        if (file.size > 5 * 1024 * 1024) {
            alert("Profile picture must not exceed 5MB.");
            this.value = "";
            return;
        }

        /*
        Check images type
         */
        const allowedTypes = [
            "images/jpeg",
            "images/png",
            "images/webp"
        ];

        if (!allowedTypes.includes(file.type)) {
            alert("Only JPG, PNG, and WEBP images are allowed.");
            this.value = "";
            return;
        }

        /*
         Preview
         */
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview =
                document.getElementById("profilePreview");

            const defaultProfile =
                document.getElementById("defaultProfile");

            preview.src = e.target.result;
            preview.style.display = "inline-block";
            if (defaultProfile) {
                defaultProfile.style.display = "none";

            }
        };
        reader.readAsDataURL(file);
    });

/*
IF PASSWORD WAS VERIFIED
*/

<?php if ($is_verified): ?>
enableEdit();

<?php endif; ?>
window.addEventListener("click", function(event) {
    const modal =
        document.getElementById("passwordModal");
    if (event.target === modal) {
        closePasswordModal();
    }
});

</script>

</body>
</html>