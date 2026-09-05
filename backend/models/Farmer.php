<?php
class Farmer
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function register(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO users (full_name, phone_number, email, password_hash, role, district_id)
             VALUES (:full_name, :phone_number, :email, :password_hash, 'farmer', :district_id)"
        );
        $stmt->execute([
            ":full_name" => $data["full_name"],
            ":phone_number" => $data["phone_number"],
            ":email" => $data["email"] ?? null,
            ":password_hash" => password_hash($data["password"], PASSWORD_DEFAULT),
            ":district_id" => $data["district_id"],
        ]);
        $userId = (int) $this->db->lastInsertId();

        $stmt = $this->db->prepare(
            "INSERT INTO farmers (user_id, village, farm_size_acres) VALUES (:user_id, :village, :farm_size)"
        );
        $stmt->execute([
            ":user_id" => $userId,
            ":village" => $data["village"] ?? null,
            ":farm_size" => $data["farm_size_acres"] ?? null,
        ]);

        return $userId;
    }

    public function getFarmerIdByUserId(int $userId): ?int
    {
        $stmt = $this->db->prepare("SELECT farmer_id FROM farmers WHERE user_id = :user_id");
        $stmt->execute([":user_id" => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int) $row["farmer_id"] : null;
    }
}