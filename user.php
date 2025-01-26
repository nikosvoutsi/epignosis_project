<?php
class User {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($email, $password) {
        $query = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            return $user; 
        }
        return false; 
    }

    public function fetchUsers() {
        $query = "SELECT id, name, email, employee_code FROM users WHERE usertype_id = 2";
        $stmt = $this->conn->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createUser($name, $email, $employee_code, $hashed_password) {
        $query = "INSERT INTO users (name, email, password, usertype_id, employee_code) 
                  VALUES (:name, :email, :password, 2, :employee_code)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":employee_code", $employee_code);
        $stmt->execute();
    }

    public function checkEmailExists($email) {
        $query = "SELECT id FROM users WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function checkEmployeeCodeExists($employee_code, $exclude_id = null) {
        $query = "SELECT id FROM users WHERE employee_code = :employee_code";
        if ($exclude_id) {
            $query .= " AND id != :id";
        }
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":employee_code", $employee_code);
        if ($exclude_id) {
            $stmt->bindParam(":id", $exclude_id);
        }
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function updateUser($id, $name, $email, $employee_code) {
        $query = "UPDATE users SET name = :name, email = :email, employee_code = :employee_code WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":employee_code", $employee_code);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
    }

    public function updatePassword($id, $hashed_password) {
        $query = "UPDATE users SET password = :password WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
    }

    public function fetchPassword($id) {
        $query = "SELECT password FROM users WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['password'];
    }

    public function deleteUser($id) {
        $query = "DELETE FROM users WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
    }
}
