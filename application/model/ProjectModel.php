<?php

/**
 * ProjectModel
 *
 * Handles all DB queries for projects and project members.
 */
class ProjectModel
{
    /**
     * Create a new project and add the creator as owner.
     *
     * @param int    $ownerId
     * @param string $name
     * @param string $description
     * @return int|false  new project ID or false on failure
     */
    public static function createProject($ownerId, $name, $description)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "INSERT INTO projects (name, description, owner_id) VALUES (:name, :description, :owner_id)";
        $query = $database->prepare($sql);
        $query->execute(array(
            ':name'        => $name,
            ':description' => $description,
            ':owner_id'    => $ownerId
        ));

        $projectId = $database->lastInsertId();

        if (!$projectId) {
            return false;
        }

        // add creator as owner in project_members
        self::addMember($projectId, $ownerId, 'owner');

        return (int)$projectId;
    }

    /**
     * Get a single project by ID.
     *
     * @param int $projectId
     * @return object|false
     */
    public static function getProjectById($projectId)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "SELECT p.*, u.user_name AS owner_name
                FROM projects p
                JOIN users u ON u.user_id = p.owner_id
                WHERE p.id = :id
                LIMIT 1";
        $query = $database->prepare($sql);
        $query->execute(array(':id' => $projectId));
        return $query->fetch();
    }

    /**
     * Get all projects a user is a member of (owner or member).
     *
     * @param int $userId
     * @return array
     */
    public static function getProjectsForUser($userId)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "SELECT p.*, u.user_name AS owner_name, pm.role AS user_role,
                       (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) AS task_count
                FROM projects p
                JOIN project_members pm ON pm.project_id = p.id
                JOIN users u ON u.user_id = p.owner_id
                WHERE pm.user_id = :user_id
                ORDER BY p.created_at DESC";
        $query = $database->prepare($sql);
        $query->execute(array(':user_id' => $userId));
        return $query->fetchAll();
    }

    /**
     * Update project name and description (owner only).
     *
     * @param int    $projectId
     * @param string $name
     * @param string $description
     * @return bool
     */
    public static function updateProject($projectId, $name, $description)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "UPDATE projects SET name = :name, description = :description WHERE id = :id LIMIT 1";
        $query = $database->prepare($sql);
        return $query->execute(array(
            ':name'        => $name,
            ':description' => $description,
            ':id'          => $projectId
        ));
    }

    /**
     * Delete a project (cascades to members, tasks, comments).
     *
     * @param int $projectId
     * @return bool
     */
    public static function deleteProject($projectId)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "DELETE FROM projects WHERE id = :id LIMIT 1";
        $query = $database->prepare($sql);
        return $query->execute(array(':id' => $projectId));
    }

    /**
     * Check if a user is a member of a project.
     *
     * @param int $projectId
     * @param int $userId
     * @return bool
     */
    public static function isMember($projectId, $userId)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "SELECT id FROM project_members WHERE project_id = :project_id AND user_id = :user_id LIMIT 1";
        $query = $database->prepare($sql);
        $query->execute(array(':project_id' => $projectId, ':user_id' => $userId));
        return (bool)$query->fetch();
    }

    /**
     * Check if a user is the owner of a project.
     *
     * @param int $projectId
     * @param int $userId
     * @return bool
     */
    public static function isOwner($projectId, $userId)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "SELECT id FROM project_members WHERE project_id = :project_id AND user_id = :user_id AND role = 'owner' LIMIT 1";
        $query = $database->prepare($sql);
        $query->execute(array(':project_id' => $projectId, ':user_id' => $userId));
        return (bool)$query->fetch();
    }

    /**
     * Add a user as a member to a project.
     *
     * @param int    $projectId
     * @param int    $userId
     * @param string $role  'owner' or 'member'
     * @return bool
     */
    public static function addMember($projectId, $userId, $role = 'member')
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "INSERT IGNORE INTO project_members (project_id, user_id, role) VALUES (:project_id, :user_id, :role)";
        $query = $database->prepare($sql);
        return $query->execute(array(
            ':project_id' => $projectId,
            ':user_id'    => $userId,
            ':role'       => $role
        ));
    }

    /**
     * Remove a member from a project (cannot remove owner).
     *
     * @param int $projectId
     * @param int $userId
     * @return bool
     */
    public static function removeMember($projectId, $userId)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "DELETE FROM project_members WHERE project_id = :project_id AND user_id = :user_id AND role != 'owner' LIMIT 1";
        $query = $database->prepare($sql);
        return $query->execute(array(':project_id' => $projectId, ':user_id' => $userId));
    }

    /**
     * Get all members of a project with their user info.
     *
     * @param int $projectId
     * @return array
     */
    public static function getMembers($projectId)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "SELECT u.user_id, u.user_name, pm.role, pm.joined_at
                FROM project_members pm
                JOIN users u ON u.user_id = pm.user_id
                WHERE pm.project_id = :project_id
                ORDER BY pm.role DESC, u.user_name ASC";
        $query = $database->prepare($sql);
        $query->execute(array(':project_id' => $projectId));
        return $query->fetchAll();
    }

    /**
     * Find a user by username (used for invite by username).
     *
     * @param string $username
     * @return object|false
     */
    public static function findUserByName($username)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "SELECT user_id, user_name FROM users WHERE user_name = :user_name AND user_active = 1 LIMIT 1";
        $query = $database->prepare($sql);
        $query->execute(array(':user_name' => $username));
        return $query->fetch();
    }

    /**
     * Search active users by partial username, excluding existing project members.
     * Used for the invite autocomplete AJAX endpoint.
     *
     * @param int    $projectId
     * @param string $search  partial username
     * @return array  objects with user_id and user_name
     */
    public static function searchUsers($projectId, $search)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "SELECT u.user_id, u.user_name
                FROM users u
                WHERE u.user_name LIKE :search
                  AND u.user_active = 1
                  AND u.user_id NOT IN (
                      SELECT pm.user_id FROM project_members pm WHERE pm.project_id = :project_id
                  )
                ORDER BY u.user_name ASC
                LIMIT 10";
        $query = $database->prepare($sql);
        $query->execute(array(
            ':search'     => '%' . $search . '%',
            ':project_id' => $projectId
        ));
        return $query->fetchAll();
    }
}
