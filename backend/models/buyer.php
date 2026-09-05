<?php
class Buyer
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
             VALUES (:full_name, :phone_number, :email, :password_hash, 'buyer', :district_id)"
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
            "INSERT INTO buyers (user_id, business_name, buyer_type) VALUES (:user_id, :business_name, :buyer_type)"
        );
        $stmt->execute([
            ":user_id" => $userId,
            ":business_name" => $data["business_name"] ?? null,
            ":buyer_type" => $data["buyer_type"] ?? "trader",
        ]);

        return $userId;
    }

    public function getBuyerIdByUserId(int $userId): ?int
    {
        $stmt = $this->db->prepare("SELECT buyer_id FROM buyers WHERE user_id = :user_id");
        $stmt->execute([":user_id" => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int) $row["buyer_id"] : null;
    }
}