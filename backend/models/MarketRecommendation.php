<?php
class MarketRecommendation
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function recommend(int $commodityId, int $farmerDistrictId, int $limit = 3): array
    {
        $stmt = $this->db->prepare(
            "SELECT m.market_id, m.name AS market_name, mp.price_per_unit,
                    COALESCE(mde.estimated_transport_cost_per_unit, 0) AS transport_cost,
                    (mp.price_per_unit - COALESCE(mde.estimated_transport_cost_per_unit, 0)) AS net_value
             FROM market_prices mp
             JOIN markets m ON m.market_id = mp.market_id
             LEFT JOIN market_distance_estimates mde
                    ON mde.market_id = m.market_id AND mde.origin_district_id = :district_id
             WHERE mp.commodity_id = :commodity_id
               AND mp.date_recorded = (
                    SELECT MAX(date_recorded) FROM market_prices
                    WHERE commodity_id = :commodity_id AND market_id = mp.market_id
               )
             ORDER BY net_value DESC
             LIMIT :limit"
        );
        $stmt->bindValue(":commodity_id", $commodityId, PDO::PARAM_INT);
        $stmt->bindValue(":district_id", $farmerDistrictId, PDO::PARAM_INT);
        $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}