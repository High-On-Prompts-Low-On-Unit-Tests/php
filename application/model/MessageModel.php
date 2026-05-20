<?php
class MessageModel
{
    public static function getChatPartners($user_id)
    {
        $db = DatabaseFactory::getFactory()->getConnection();

        $sql = "SELECT user_id, user_name
                FROM users
                WHERE user_id != :user_id
                ORDER BY user_name";
        $query = $db->prepare($sql);
        $query->execute([':user_id' => $user_id]);

        return $query->fetchAll();
    }

    public static function getConversation($user_id, $partner_id)
    {
        $db = DatabaseFactory::getFactory()->getConnection();

        $sql = "SELECT m.id, m.sender_id, m.receiver_id, m.message_text, m.is_read, m.created_at,
                       s.user_name AS sender_name, r.user_name AS receiver_name
                FROM messages m
                JOIN users s ON s.user_id = m.sender_id
                JOIN users r ON r.user_id = m.receiver_id
                WHERE (m.sender_id = :user_id AND m.receiver_id = :partner_id)
                   OR (m.sender_id = :partner_id AND m.receiver_id = :user_id)
                ORDER BY m.created_at ASC";
        $query = $db->prepare($sql);
        $query->execute([':user_id' => $user_id, ':partner_id' => $partner_id]);

        return $query->fetchAll();
    }

    public static function sendMessage($sender_id, $receiver_id, $text)
    {
        $text = trim($text);
        if ($sender_id == $receiver_id || $text === '') {
            return false;
        }

        $db = DatabaseFactory::getFactory()->getConnection();

        $sql = "INSERT INTO messages (sender_id, receiver_id, message_text)
                VALUES (:sender_id, :receiver_id, :message_text)";
        $query = $db->prepare($sql);
        $query->execute([
            ':sender_id' => $sender_id,
            ':receiver_id' => $receiver_id,
            ':message_text' => $text
        ]);

        return $query->rowCount() === 1;
    }

    public static function markAsRead($partner_id, $user_id)
    {
        $db = DatabaseFactory::getFactory()->getConnection();

        $sql = "UPDATE messages
                SET is_read = 1
                WHERE sender_id = :partner_id
                  AND receiver_id = :user_id
                  AND is_read = 0";
        $query = $db->prepare($sql);
        $query->execute([':partner_id' => $partner_id, ':user_id' => $user_id]);

        return true;
    }
}
