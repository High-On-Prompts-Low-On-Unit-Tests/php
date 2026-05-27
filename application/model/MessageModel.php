<?php
class MessageModel
{
    public static function getUserGroups($user_id)
    {
        $db = DatabaseFactory::getFactory()->getConnection();

        $sql = "SELECT g.id, g.name, g.type, g.created_by,
                       CASE
                           WHEN g.type = 'direct' THEN (
                               SELECT u.user_name FROM group_members gm2
                               JOIN users u ON u.user_id = gm2.user_id
                               WHERE gm2.group_id = g.id AND gm2.user_id != :user_id
                               LIMIT 1
                           )
                           ELSE g.name
                       END AS display_name,
                       (SELECT gm3.created_at FROM group_messages gm3
                        WHERE gm3.group_id = g.id ORDER BY gm3.created_at DESC LIMIT 1
                       ) AS last_message_at
                FROM chat_groups g
                JOIN group_members gm ON gm.group_id = g.id AND gm.user_id = :user_id
                ORDER BY last_message_at DESC, g.created_at DESC";
        $query = $db->prepare($sql);
        $query->execute([':user_id' => $user_id]);

        return $query->fetchAll();
    }

    public static function getGroupMessages($group_id)
    {
        $db = DatabaseFactory::getFactory()->getConnection();

        $sql = "SELECT m.id, m.group_id, m.sender_id, m.message_text, m.created_at,
                       u.user_name AS sender_name
                FROM group_messages m
                JOIN users u ON u.user_id = m.sender_id
                WHERE m.group_id = :group_id
                ORDER BY m.created_at ASC";
        $query = $db->prepare($sql);
        $query->execute([':group_id' => $group_id]);

        return $query->fetchAll();
    }

    public static function sendMessage($group_id, $sender_id, $text)
    {
        $text = trim($text);
        if ($text === '') {
            return false;
        }

        if (!self::isGroupMember($group_id, $sender_id)) {
            return false;
        }

        $db = DatabaseFactory::getFactory()->getConnection();

        $sql = "INSERT INTO group_messages (group_id, sender_id, message_text)
                VALUES (:group_id, :sender_id, :message_text)";
        $query = $db->prepare($sql);
        $query->execute([
            ':group_id' => $group_id,
            ':sender_id' => $sender_id,
            ':message_text' => $text
        ]);

        return $query->rowCount() === 1;
    }

    public static function createGroup($name, $creator_id, $member_ids)
    {
        $db = DatabaseFactory::getFactory()->getConnection();

        $db->beginTransaction();

        $sql = "INSERT INTO chat_groups (name, type, created_by) VALUES (:name, 'group', :created_by)";
        $query = $db->prepare($sql);
        $query->execute([':name' => trim($name), ':created_by' => $creator_id]);
        $group_id = $db->lastInsertId();

        self::insertMember($db, $group_id, $creator_id, 'admin');

        foreach ($member_ids as $mid) {
            if ($mid != $creator_id) {
                self::insertMember($db, $group_id, $mid, 'member');
            }
        }

        $db->commit();

        return $group_id;
    }

    public static function getOrCreateDirectChat($user_id, $partner_id)
    {
        if ($user_id == $partner_id) {
            return false;
        }

        $db = DatabaseFactory::getFactory()->getConnection();

        $sql = "SELECT g.id FROM chat_groups g
                JOIN group_members gm1 ON gm1.group_id = g.id AND gm1.user_id = :user_id
                JOIN group_members gm2 ON gm2.group_id = g.id AND gm2.user_id = :partner_id
                WHERE g.type = 'direct'
                LIMIT 1";
        $query = $db->prepare($sql);
        $query->execute([':user_id' => $user_id, ':partner_id' => $partner_id]);
        $row = $query->fetch();

        if ($row) {
            return $row->id;
        }

        $db->beginTransaction();

        $sql = "INSERT INTO chat_groups (name, type, created_by) VALUES (NULL, 'direct', :created_by)";
        $query = $db->prepare($sql);
        $query->execute([':created_by' => $user_id]);
        $group_id = $db->lastInsertId();

        self::insertMember($db, $group_id, $user_id, 'member');
        self::insertMember($db, $group_id, $partner_id, 'member');

        $db->commit();

        return $group_id;
    }

    public static function addMember($group_id, $user_id)
    {
        $group = self::getGroup($group_id);
        if (!$group || $group->type === 'direct') {
            return false;
        }

        if (self::isGroupMember($group_id, $user_id)) {
            return false;
        }

        $db = DatabaseFactory::getFactory()->getConnection();
        self::insertMember($db, $group_id, $user_id, 'member');

        return true;
    }

    public static function removeMember($group_id, $user_id, $removed_by)
    {
        $group = self::getGroup($group_id);
        if (!$group || $group->type === 'direct') {
            return false;
        }

        if (!self::isGroupAdmin($group_id, $removed_by)) {
            return false;
        }

        if ($user_id == $removed_by) {
            return false;
        }

        $db = DatabaseFactory::getFactory()->getConnection();
        $sql = "DELETE FROM group_members WHERE group_id = :group_id AND user_id = :user_id";
        $query = $db->prepare($sql);
        $query->execute([':group_id' => $group_id, ':user_id' => $user_id]);

        return $query->rowCount() === 1;
    }

    public static function leaveGroup($group_id, $user_id)
    {
        $group = self::getGroup($group_id);
        if (!$group || $group->type === 'direct') {
            return false;
        }

        $db = DatabaseFactory::getFactory()->getConnection();
        $sql = "DELETE FROM group_members WHERE group_id = :group_id AND user_id = :user_id";
        $query = $db->prepare($sql);
        $query->execute([':group_id' => $group_id, ':user_id' => $user_id]);

        return $query->rowCount() === 1;
    }

    public static function renameGroup($group_id, $name, $user_id)
    {
        $group = self::getGroup($group_id);
        if (!$group || $group->type === 'direct') {
            return false;
        }

        if (!self::isGroupAdmin($group_id, $user_id)) {
            return false;
        }

        $db = DatabaseFactory::getFactory()->getConnection();
        $sql = "UPDATE chat_groups SET name = :name WHERE id = :id";
        $query = $db->prepare($sql);
        $query->execute([':name' => trim($name), ':id' => $group_id]);

        return true;
    }

    public static function getGroup($group_id)
    {
        $db = DatabaseFactory::getFactory()->getConnection();
        $sql = "SELECT * FROM chat_groups WHERE id = :id";
        $query = $db->prepare($sql);
        $query->execute([':id' => $group_id]);

        return $query->fetch();
    }

    public static function getGroupMembers($group_id)
    {
        $db = DatabaseFactory::getFactory()->getConnection();
        $sql = "SELECT gm.user_id, gm.role, gm.joined_at, u.user_name
                FROM group_members gm
                JOIN users u ON u.user_id = gm.user_id
                WHERE gm.group_id = :group_id
                ORDER BY gm.role ASC, u.user_name ASC";
        $query = $db->prepare($sql);
        $query->execute([':group_id' => $group_id]);

        return $query->fetchAll();
    }

    public static function isGroupMember($group_id, $user_id)
    {
        $db = DatabaseFactory::getFactory()->getConnection();
        $sql = "SELECT 1 FROM group_members WHERE group_id = :group_id AND user_id = :user_id";
        $query = $db->prepare($sql);
        $query->execute([':group_id' => $group_id, ':user_id' => $user_id]);

        return (bool) $query->fetch();
    }

    public static function isGroupAdmin($group_id, $user_id)
    {
        $db = DatabaseFactory::getFactory()->getConnection();
        $sql = "SELECT 1 FROM group_members WHERE group_id = :group_id AND user_id = :user_id AND role = 'admin'";
        $query = $db->prepare($sql);
        $query->execute([':group_id' => $group_id, ':user_id' => $user_id]);

        return (bool) $query->fetch();
    }

    public static function getUnreadCounts($user_id)
    {
        $db = DatabaseFactory::getFactory()->getConnection();

        $sql = "SELECT gm.group_id,
                       COUNT(msg.id) AS cnt
                FROM group_members gm
                LEFT JOIN group_messages msg
                    ON msg.group_id = gm.group_id
                    AND msg.sender_id != :user_id
                    AND (gm.last_read_at IS NULL OR msg.created_at > gm.last_read_at)
                WHERE gm.user_id = :user_id
                GROUP BY gm.group_id";
        $query = $db->prepare($sql);
        $query->execute([':user_id' => $user_id]);

        $counts = [];
        foreach ($query->fetchAll() as $row) {
            $counts[$row->group_id] = (int) $row->cnt;
        }
        return $counts;
    }

    public static function markAsRead($group_id, $user_id)
    {
        $db = DatabaseFactory::getFactory()->getConnection();

        $sql = "UPDATE group_members SET last_read_at = NOW()
                WHERE group_id = :group_id AND user_id = :user_id";
        $query = $db->prepare($sql);
        $query->execute([':group_id' => $group_id, ':user_id' => $user_id]);

        return true;
    }

    public static function getAllUsers($exclude_user_id)
    {
        $db = DatabaseFactory::getFactory()->getConnection();

        $sql = "SELECT user_id, user_name FROM users WHERE user_id != :user_id ORDER BY user_name";
        $query = $db->prepare($sql);
        $query->execute([':user_id' => $exclude_user_id]);

        return $query->fetchAll();
    }

    private static function insertMember($db, $group_id, $user_id, $role)
    {
        $sql = "INSERT INTO group_members (group_id, user_id, role) VALUES (:group_id, :user_id, :role)";
        $query = $db->prepare($sql);
        $query->execute([':group_id' => $group_id, ':user_id' => $user_id, ':role' => $role]);
    }
}
