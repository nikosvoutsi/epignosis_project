<?php
class Notification {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create a new notification
    public function createNotification($title, $user_id, $vacation_request_id) {
        $query = $this->conn->prepare("
            INSERT INTO notifications (title, user_id, vacation_request_id, created_at)
            VALUES (:title, :user_id, :vacation_request_id, NOW())
        ");
        $query->bindParam(":title", $title);
        $query->bindParam(":user_id", $user_id);
        $query->bindParam(":vacation_request_id", $vacation_request_id);
        $query->execute();
    }

    // Mark all unread notifications as read
    public function markNotificationsAsRead() {
        $query = $this->conn->prepare("UPDATE notifications SET isRead = 1 WHERE isRead = 0");
        $query->execute();
    }

    public function getUnreadCount() {
        $query = $this->conn->prepare("
            SELECT COUNT(*) AS unread_count 
            FROM notifications 
            WHERE isRead = 0
        ");
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC)['unread_count'];
    }

    // Fetch unread notifications
    public function getUnreadNotifications() {
        $query = $this->conn->prepare("
            SELECT n.title, u.name AS user_name, n.created_at 
            FROM notifications n
            INNER JOIN users u ON n.user_id = u.id
            WHERE n.isRead = 0
            ORDER BY n.created_at DESC
        ");
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}
