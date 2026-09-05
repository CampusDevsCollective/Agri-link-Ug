<?php
class BuyerRequirement
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO buyer_requirements
                (buyer_id, commodity_id, quantity_required, unit, offered_price_per_unit,
                 collection_market_id, collection_deadline, status)
             VALUES
                (:buyer_id, :commodity_id, :quantity_required, :unit, :offered_price_per_unit,
                 :collection_market_id, :collection_deadline, 'open')"
        );
        $stmt->execute([
            ":buyer_id" => $data["buyer_id"],
            ":commodity_id" => $data["commodity_id"],
            ":quantity_required" => $data["quantity_required"],
            ":unit" => $data["unit"] ?? "kg",
            ":offered_price_per_unit" => $data["offered_price_per_unit"] ?? null,
            ":collection_market_id" => $data["collection_market_id"] ?? null,
            ":collection_deadline" => $data["collection_deadline"] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }
}