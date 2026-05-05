<!DOCTYPE html>
<html>
<head>
    <title>Blood Donation Management System</title>

    <!-- 
        # Main stylesheet path
        # Important:
        # This index.php is inside the "auth" folder,
        # so we must go one level up first using ../
    -->
    <link rel="stylesheet" href="../dashboard/assets/style.css">
</head>

<!-- 
    # Body class used for full-page background image
-->
<body class="index-page-body">

    <!-- 
        # Full page wrapper
        # Purpose:
        # 1. Show full-page blood donation background image
        # 2. Keep the login selection card centered
    -->
    <div class="center-wrap index-page-wrap">

        <!-- 
            # Main auth box
            # Existing system structure kept
        -->
        <div class="auth-box">

            <!-- 
                # Main centered card
                # White transparent card above the background image
            -->
            <div class="card index-card-clean auth-card">

                <!-- 
                    # Branding block
                    # Logo + Title + Subtitle
                -->
                <div class="auth-brand-block">
                    <div class="logo auth-logo">🩸</div>

                    <h2 class="auth-system-title">Blood Donation Management System</h2>
                    <p class="auth-system-subtitle">Donate Blood, Save Lives</p>
                </div>

                <!-- 
                    # Page instruction title
                -->
                <h3 class="auth-page-title">Choose how you want to continue</h3>

                <!-- 
                    # Role-based login buttons
                    # Since this file is already inside auth/,
                    # link stays as login.php?role=...
                -->
                <a href="login.php?role=donor" class="btn btn-primary btn-block">
                    Login as Donor
                </a>

                <a href="login.php?role=recipient" class="btn btn-primary btn-block">
                    Login as Recipient
                </a>

                <a href="login.php?role=blood_bank" class="btn btn-primary btn-block">
                    Login as Blood Bank
                </a>

                <!-- 
                    # Admin login shortcut
                -->
                <p class="auth-text-row">
                    Admin?
                    <a href="login.php?role=admin" class="link">Login here</a>
                </p>

            </div>
        </div>
    </div>

</body>
</html>