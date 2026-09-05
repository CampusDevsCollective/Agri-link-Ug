<?php
class Aggregator
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Greedily combines available listings for a commodity until the
     * requirement's quantity is met, or all candidates are exhausted.
     */
    public function proposeAggregation(int $requirementId): ?int
    {
        $stmt = $this->db->prepare("SELECT * FROM buyer_requirements WHERE requirement_id = :id");
        $stmt->execute([":id" => $requirementId]);
        $requirement = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$requirement) {
            return null;
        }

        // Candidate listings, largest first (fewer contributors to coordinate).
        $stmt = $this->db->prepare(
            "SELECT * FROM produce_listings
             WHERE commodity_id = :commodity_id AND status = 'available'
             ORDER BY quantity_available DESC"
        );
        $stmt->execute([":commodity_id" => $requirement["commodity_id"]]);
        $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $target = (float) $requirement["quantity_required"];
        $total = 0.0;
        $selected = [];

        foreach ($listings as $listing) {
            if ($total >= $target)
                break;
            $selected[] = $listing;
            $total += (float) $listing["quantity_available"];
        }

        if ($total < $target) {
            return null; // not enough combined supply exists yet
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO aggregations
                    (requirement_id, commodity_id, target_quantity, total_pledged_quantity, status)
                 VALUES (:requirement_id, :commodity_id, :target, :pledged, 'proposed')"
            );
            $stmt->execute([
                ":requirement_id" => $requirementId,
                ":commodity_id" => $requirement["commodity_id"],
                ":target" => $target,
                ":pledged" => $total,
            ]);
            $aggregationId = (int) $this->db->lastInsertId();

            $memberStmt = $this->db->prepare(
                "INSERT INTO aggregation_members (aggregation_id, listing_id, farmer_id, quantity_committed, status)
                 VALUES (:aggregation_id, :listing_id, :farmer_id, :quantity, 'proposed')"
            );
            foreach ($selected as $listing) {
                $memberStmt->execute([
                    ":aggregation_id" => $aggregationId,
                    ":listing_id" => $listing["listing_id"],
                    ":farmer_id" => $listing["farmer_id"],
                    ":quantity" => $listing["quantity_available"],
                ]);
                $this->db->prepare("UPDATE produce_listings SET status = 'aggregating' WHERE listing_id = :id")
                    ->execute([":id" => $listing["listing_id"]]);
            }

            $this->db->prepare("UPDATE buyer_requirements SET status = 'aggregating' WHERE requirement_id = :id")
                ->execute([":id" => $requirementId]);

            $this->db->commit();
            return $aggregationId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}