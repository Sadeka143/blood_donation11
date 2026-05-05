<?php
// ======================================
// ADMIN NOTIFICATION HELPER
// Purpose:
// - Provide consistent admin notification count across all admin pages
// - Count unread admin notifications from notifications table
// - Count live workflow alerts such as urgent requests and pending reviews
// - Show recent activity logs without adding them to badge count
// ======================================

if (!function_exists('safeCountQuery')) {
    function safeCountQuery($conn, $sql){
        $result = mysqli_query($conn, $sql);

        if(!$result){
            return 0;
        }

        $row = mysqli_fetch_assoc($result);
        return isset($row['total']) ? (int)$row['total'] : 0;
    }
}

if (!function_exists('tableExists')) {
    function tableExists($conn, $tableName){
        $table_safe = mysqli_real_escape_string($conn, $tableName);
        $check = mysqli_query($conn, "SHOW TABLES LIKE '$table_safe'");
        return ($check && mysqli_num_rows($check) > 0);
    }
}

// ======================================
// Get current admin id
// If no parameter is passed, it uses session admin id
// ======================================
if (!function_exists('getCurrentAdminIdForNotifications')) {
    function getCurrentAdminIdForNotifications($admin_id = null){
        if($admin_id !== null){
            return (int)$admin_id;
        }

        if(isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin'){
            return (int)$_SESSION['user_id'];
        }

        return 0;
    }
}

// ======================================
// Count unread admin notifications stored in notifications table
// ======================================
if (!function_exists('getAdminUnreadStoredNotificationCount')) {
    function getAdminUnreadStoredNotificationCount($conn, $admin_id = null){
        $admin_id = getCurrentAdminIdForNotifications($admin_id);

        if($admin_id <= 0 || !tableExists($conn, 'notifications')){
            return 0;
        }

        $stmt = mysqli_prepare($conn, "
            SELECT COUNT(*) AS total
            FROM notifications
            WHERE user_id = ?
            AND is_read = 0
        ");

        mysqli_stmt_bind_param($stmt, "i", $admin_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        return isset($row['total']) ? (int)$row['total'] : 0;
    }
}

// ======================================
// Count live admin workflow alerts
// These are not stored unread notifications;
// they are calculated from the current system status.
// ======================================
if (!function_exists('getAdminLiveAlertCount')) {
    function getAdminLiveAlertCount($conn){
        $total = 0;

        // Urgent active requests
        if(tableExists($conn, 'blood_requests')){
            $total += safeCountQuery($conn, "
                SELECT COUNT(*) AS total
                FROM blood_requests
                WHERE urgency = 'urgent'
                AND status IN ('pending_review','approved','matched','scheduled')
            ");

            // Pending review requests
            $total += safeCountQuery($conn, "
                SELECT COUNT(*) AS total
                FROM blood_requests
                WHERE status = 'pending_review'
            ");
        }

        // Pending or scheduled stock donation requests
        if(tableExists($conn, 'stock_donation_requests')){
            $total += safeCountQuery($conn, "
                SELECT COUNT(*) AS total
                FROM stock_donation_requests
                WHERE status IN ('pending','scheduled')
            ");
        }

        // Appointment reschedule requests
        if(tableExists($conn, 'appointment_reschedule_requests')){
            $total += safeCountQuery($conn, "
                SELECT COUNT(*) AS total
                FROM appointment_reschedule_requests
                WHERE status = 'requested'
            ");
        }

        return $total;
    }
}

// ======================================
// Final admin notification count for bell badge
// Stored unread notifications + live alerts
// Recent activity logs are NOT counted here.
// ======================================
if (!function_exists('getAdminNotificationCount')) {
    function getAdminNotificationCount($conn, $admin_id = null){
        $stored_unread = getAdminUnreadStoredNotificationCount($conn, $admin_id);
        $live_alerts = getAdminLiveAlertCount($conn);

        return $stored_unread + $live_alerts;
    }
}

// ======================================
// Get notification items for admin notification page
// Includes:
// - unread stored notifications
// - live alerts
// - recent activity logs for monitoring only
// ======================================
if (!function_exists('getAdminNotificationItems')) {
    function getAdminNotificationItems($conn, $admin_id = null){
        $items = [];
        $admin_id = getCurrentAdminIdForNotifications($admin_id);

        // ------------------------------
        // Stored unread admin notifications
        // ------------------------------
        if($admin_id > 0 && tableExists($conn, 'notifications')){
            $stmt = mysqli_prepare($conn, "
                SELECT id, message, is_read, created_at
                FROM notifications
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT 20
            ");

            mysqli_stmt_bind_param($stmt, "i", $admin_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            while($row = mysqli_fetch_assoc($result)){
                $items[] = [
                    'title' => $row['is_read'] ? 'Admin Notification' : 'Unread Admin Notification',
                    'message' => $row['message'],
                    'type' => $row['is_read'] ? 'stored_read' : 'stored_unread',
                    'created_at' => $row['created_at']
                ];
            }

            mysqli_stmt_close($stmt);
        }

        // ------------------------------
        // Live urgent request alert
        // ------------------------------
        if(tableExists($conn, 'blood_requests')){
            $urgent_count = safeCountQuery($conn, "
                SELECT COUNT(*) AS total
                FROM blood_requests
                WHERE urgency = 'urgent'
                AND status IN ('pending_review','approved','matched','scheduled')
            ");

            if($urgent_count > 0){
                $items[] = [
                    'title' => 'Urgent Requests',
                    'message' => $urgent_count . " urgent request(s) currently need priority attention.",
                    'type' => 'urgent',
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }

            // Pending review alert
            $pending_review_count = safeCountQuery($conn, "
                SELECT COUNT(*) AS total
                FROM blood_requests
                WHERE status = 'pending_review'
            ");

            if($pending_review_count > 0){
                $items[] = [
                    'title' => 'Pending Review',
                    'message' => $pending_review_count . " blood request(s) are waiting for blood bank review.",
                    'type' => 'warning',
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }
        }

        // ------------------------------
        // Stock donation request alert
        // ------------------------------
        if(tableExists($conn, 'stock_donation_requests')){
            $stock_request_count = safeCountQuery($conn, "
                SELECT COUNT(*) AS total
                FROM stock_donation_requests
                WHERE status IN ('pending','scheduled')
            ");

            if($stock_request_count > 0){
                $items[] = [
                    'title' => 'Stock Donation Requests',
                    'message' => $stock_request_count . " stock donation request(s) are currently pending or scheduled.",
                    'type' => 'info',
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }
        }

        // ------------------------------
        // Appointment reschedule alert
        // ------------------------------
        if(tableExists($conn, 'appointment_reschedule_requests')){
            $reschedule_requests = safeCountQuery($conn, "
                SELECT COUNT(*) AS total
                FROM appointment_reschedule_requests
                WHERE status = 'requested'
            ");

            if($reschedule_requests > 0){
                $items[] = [
                    'title' => 'Reschedule Requests',
                    'message' => $reschedule_requests . " appointment reschedule request(s) are waiting for review.",
                    'type' => 'warning',
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }
        }

        // ------------------------------
        // Recent activity logs
        // These are shown for admin monitoring,
        // but they are NOT counted as unread notifications.
        // ------------------------------
        if(tableExists($conn, 'activity_logs')){
            $recent_logs = mysqli_query($conn, "
                SELECT action_type, description, created_at
                FROM activity_logs
                ORDER BY id DESC
                LIMIT 5
            ");

            if($recent_logs){
                while($log = mysqli_fetch_assoc($recent_logs)){
                    $items[] = [
                        'title' => 'Recent Activity',
                        'message' => $log['description'],
                        'type' => 'recent',
                        'created_at' => $log['created_at']
                    ];
                }
            }
        }

        return $items;
    }
}
?>