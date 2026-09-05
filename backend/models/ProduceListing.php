<?php
class ProduceListing
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO produce_listings
                (farmer_id, commodity_id, quantity_available, unit, harvest_date,
                 availability_date, asking_price_per_unit, pickup_location_note, status)
             VALUES
                (:farmer_id, :commodity_id, :quantity_available, :unit, :harvest_date,
                 :availability_date, :asking_price_per_unit, :pickup_location_note, 'available')"
        );
        $stmt->execute([
            ":farmer_id" => $data["farmer_id"],
            ":commodity_id" => $data["commodity_id"],
            ":quantity_available" => $data["quantity_available"],
            ":unit" => $data["unit"] ?? "kg",
            ":harvest_date" => $data["harvest_date"] ?? null,
            ":availability_date" => $data["availability_date"],
            ":asking_price_per_unit" => $data["asking_price_per_unit"] ?? null,
            ":pickup_location_note" => $data["pickup_location_note"] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function getByFarmer(int $farmerId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM produce_listings WHERE farmer_id = :farmer_id ORDER BY created_at DESC"
        );
        $stmt->execute([":farmer_id" => $farmerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}