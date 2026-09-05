<?php
class Matcher
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Attempts a direct match: one listing that fully covers the requirement.
     * Returns match_id on success, or null if no single listing qualifies.
     */
    public function attemptDirectMatch(int $requirementId): ?int
    {
        $stmt = $this->db->prepare("SELECT * FROM buyer_requirements WHERE requirement_id = :id");
        $stmt->execute([":id" => $requirementId]);
        $requirement = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$requirement || $requirement["status"] !== "open") {
            return null;
        }

        // Find one available listing, same commodity, enough quantity.
        $stmt = $this->db->prepare(
            "SELECT * FROM produce_listings
             WHERE commodity_id = :commodity_id
               AND status = 'available'
               AND quantity_available >= :quantity_required
             ORDER BY quantity_available ASC
             LIMIT 1"
        );
        $stmt->execute([
            ":commodity_id" => $requirement["commodity_id"],
            ":quantity_required" => $requirement["quantity_required"],
        ]);
        $listing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$listing) {
            return null; // caller should fall through to aggregation
        }

        // Create the match.
        $stmt = $this->db->prepare(
            "INSERT INTO matches (requirement_id, listing_id, match_type, matched_quantity, match_status)
             VALUES (:requirement_id, :listing_id, 'direct', :quantity, 'proposed')"
        );
        $stmt->execute([
            ":requirement_id" => $requirementId,
            ":listing_id" => $listing["listing_id"],
            ":quantity" => $requirement["quantity_required"],
        ]);
        $matchId = (int) $this->db->lastInsertId();

        // Update statuses.
        $this->db->prepare("UPDATE buyer_requirements SET status = 'partially_matched' WHERE requirement_id = :id")
            ->execute([":id" => $requirementId]);
        $this->db->prepare("UPDATE produce_listings SET status = 'matched' WHERE listing_id = :id")
            ->execute([":id" => $listing["listing_id"]]);

        return $matchId;
    }
}