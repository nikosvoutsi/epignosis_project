<?php
class Vacation {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Fetch all vacation requests
    public function fetchVacations() {
        $query = $this->conn->prepare("
            SELECT vr.id, vr.created_at, vr.date_from, vr.date_to, vr.days, vr.reason, s.title AS status_name, vr.status_id as status_id
            FROM vacation_requests vr
            INNER JOIN statuses s ON vr.status_id = s.id
        ");
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    

    // Update the status of a vacation request
    public function updateStatus($vacation_id, $new_status) {
        $query = $this->conn->prepare("UPDATE vacation_requests SET status_id = :status_id WHERE id = :id");
        $query->bindParam(":status_id", $new_status);
        $query->bindParam(":id", $vacation_id);
        $query->execute();
    }

    public function createVacation($date_from, $date_to, $reason, $user_id) {
        $start_date = new DateTime($date_from);
        $end_date = new DateTime($date_to);
        $days = 0;

        while ($start_date <= $end_date) {
            if ($start_date->format('N') < 6) { // Exclude weekends
                $days++;
            }
            $start_date->modify('+1 day');
        }

        try {
            $this->conn->beginTransaction();

            // Insert vacation request
            $query = $this->conn->prepare("
                INSERT INTO vacation_requests (date_from, date_to, reason, days, user_id, status_id, created_at)
                VALUES (:date_from, :date_to, :reason, :days, :user_id, 1, NOW())
            ");
            $query->bindParam(":date_from", $date_from);
            $query->bindParam(":date_to", $date_to);
            $query->bindParam(":reason", $reason);
            $query->bindParam(":days", $days);
            $query->bindParam(":user_id", $user_id);
            $query->execute();

            // Get the ID of the new vacation request
            $vacation_request_id = $this->conn->lastInsertId();

            $this->conn->commit();

            return $vacation_request_id;
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    // Delete a vacation request
    public function deleteVacation($vacation_id, $user_id) {
        $query = $this->conn->prepare("
            DELETE FROM vacation_requests 
            WHERE id = :id AND user_id = :user_id
        ");
        $query->bindParam(":id", $vacation_id);
        $query->bindParam(":user_id", $user_id);
        $query->execute();
    }

    // Fetch all vacations for a user
    public function fetchVacationsByUser($user_id) {
        $query = $this->conn->prepare("
            SELECT v.id, v.user_id, v.date_from, v.date_to, v.days, v.reason, v.created_at, s.title as status_name, v.status_id
            FROM vacation_requests v
            INNER JOIN statuses s ON v.status_id = s.id
            WHERE v.user_id = :user_id
            ORDER BY created_at DESC
        ");
        $query->bindParam(":user_id", $user_id);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}
