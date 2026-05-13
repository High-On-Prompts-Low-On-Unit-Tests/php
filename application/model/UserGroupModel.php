<?php

/**
 * Handles all data related to user groups
 */
class UserGroupModel
{
    /**
     * Returns all user groups from the database
     *
     * @return array All user groups as objects with id and group_name
     */
    public static function getAllGroups()
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "SELECT id, group_name FROM user_groups ORDER BY id";
        $query = $database->prepare($sql);
        $query->execute();

        return $query->fetchAll();
    }

    /**
     * Returns the group name for a given group id
     *
     * @param int $groupId The group id (matches user_account_type)
     * @return string|null The group name or null if not found
     */
    public static function getGroupNameById($groupId)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "SELECT group_name FROM user_groups WHERE id = :id LIMIT 1";
        $query = $database->prepare($sql);
        $query->execute(array(':id' => $groupId));

        $result = $query->fetch();
        return $result ? $result->group_name : null;
    }
}
