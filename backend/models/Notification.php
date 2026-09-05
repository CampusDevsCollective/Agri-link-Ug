<?php
class Notification
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function create(int $userId, string $type, string $message, string $channel = "in_app"): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO notifications (user_id, type, channel, message, status)
             VALUES (:user_id, :type, :channel, :message, 'pending')"
        );
        $stmt->execute([
            ":user_id" => $userId,
            ":type" => $type,
            ":channel" => $channel,
            ":message" => $message,
        ]);
        $id = (int) $this->db->lastInsertId();

        // Placeholder dispatch — wire up an SMS gateway here later.
        $this->db->prepare("UPDATE notifications SET status = 'sent', sent_at = NOW() WHERE notification_id = :id")
            ->execute([":id" => $id]);

        return $id;
    }
}