<?php
class MessageModel
{
    public static function getUserGroups($user_id)
    {
        $db = DatabaseFactory::getFactory()->getConnection();

        $query = $db->prepare("CALL sp_get_user_groups(:user_id)");
        $query->execute([':user_id' => $user_id]);

        return $query->fetchAll();
    }

    public static function getGroupMessages($group_id)
    {
        $db = DatabaseFactory::getFactory()->getConnection();

        $query = $db->prepare("CALL sp_get_group_messages(:group_id)");
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

        $query = $db->prepare("CALL sp_send_message(:group_id, :sender_id, :message_text)");
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

        $query = $db->prepare("CALL sp_create_group(:name, 'group', :created_by, @group_id)");
        $query->execute([':name' => trim($name), ':created_by' => $creator_id]);
        $query->closeCursor();

        $group_id = $db->query("SELECT @group_id AS id")->fetch()->id;

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

        $query = $db->prepare("CALL sp_find_direct_chat(:user_id, :partner_id)");
        $query->execute([':user_id' => $user_id, ':partner_id' => $partner_id]);
        $row = $query->fetch();
        $query->closeCursor();

        if ($row) {
            return $row->id;
        }

        $db->beginTransaction();

        $query = $db->prepare("CALL sp_create_group(NULL, 'direct', :created_by, @group_id)");
        $query->execute([':created_by' => $user_id]);
        $query->closeCursor();

        $group_id = $db->query("SELECT @group_id AS id")->fetch()->id;

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

        $query = $db->prepare("CALL sp_remove_member(:group_id, :user_id)");
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

        $query = $db->prepare("CALL sp_remove_member(:group_id, :user_id)");
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

        $query = $db->prepare("CALL sp_rename_group(:id, :name)");
        $query->execute([':id' => $group_id, ':name' => trim($name)]);

        return true;
    }

    public static function getGroup($group_id)
    {
        $db = DatabaseFactory::getFactory()->getConnection();

        $query = $db->prepare("CALL sp_get_group(:id)");
        $query->execute([':id' => $group_id]);
        $result = $query->fetch();
        $query->closeCursor();

        return $result;
    }

    public static function getGroupMembers($group_id)
    {
        $db = DatabaseFactory::getFactory()->getConnection();

        $query = $db->prepare("CALL sp_get_group_members(:group_id)");
        $query->execute([':group_id' => $group_id]);
        $result = $query->fetchAll();
        $query->closeCursor();

        return $result;
    }

    public static function isGroupMember($group_id, $user_id)
    {
        $db = DatabaseFactory::getFactory()->getConnection();

        $query = $db->prepare("CALL sp_is_group_member(:group_id, :user_id)");
        $query->execute([':group_id' => $group_id, ':user_id' => $user_id]);
        $result = (bool) $query->fetch();
        $query->closeCursor();

        return $result;
    }

    public static function isGroupAdmin($group_id, $user_id)
    {
        $db = DatabaseFactory::getFactory()->getConnection();

        $query = $db->prepare("CALL sp_is_group_admin(:group_id, :user_id)");
        $query->execute([':group_id' => $group_id, ':user_id' => $user_id]);
        $result = (bool) $query->fetch();
        $query->closeCursor();

        return $result;
    }

    public static function getUnreadCounts($user_id)
    {
        $db = DatabaseFactory::getFactory()->getConnection();

        $query = $db->prepare("CALL sp_get_unread_counts(:user_id)");
        $query->execute([':user_id' => $user_id]);

        $counts = [];
        foreach ($query->fetchAll() as $row) {
            $counts[$row->group_id] = (int) $row->cnt;
        }
        $query->closeCursor();

        return $counts;
    }

    public static function markAsRead($group_id, $user_id)
    {
        $db = DatabaseFactory::getFactory()->getConnection();

        $query = $db->prepare("CALL sp_mark_as_read(:group_id, :user_id)");
        $query->execute([':group_id' => $group_id, ':user_id' => $user_id]);
        $query->closeCursor();

        return true;
    }

    public static function getAllUsers($exclude_user_id)
    {
        $db = DatabaseFactory::getFactory()->getConnection();

        $query = $db->prepare("CALL sp_get_all_users(:user_id)");
        $query->execute([':user_id' => $exclude_user_id]);

        return $query->fetchAll();
    }

    private static function insertMember($db, $group_id, $user_id, $role)
    {
        $query = $db->prepare("CALL sp_insert_member(:group_id, :user_id, :role)");
        $query->execute([':group_id' => $group_id, ':user_id' => $user_id, ':role' => $role]);
        $query->closeCursor();
    }
}
