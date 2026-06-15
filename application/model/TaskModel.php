<?php

/**
 * TaskModel
 *
 * Handles all DB queries for tasks and task comments.
 */
class TaskModel
{
    /**
     * Create a new task in a project.
     *
     * @param int    $projectId
     * @param int    $createdBy
     * @param string $title
     * @param string $description
     * @param string $status      'todo', 'inprogress' or 'done'
     * @param int    $assignedTo  user_id or null
     * @param string $deadline    date string (Y-m-d) or null
     * @return int|false  new task ID or false on failure
     */
    public static function createTask($projectId, $createdBy, $title, $description, $status = 'todo', $assignedTo = null, $deadline = null)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "INSERT INTO tasks (project_id, created_by, title, description, status, assigned_to, deadline)
                VALUES (:project_id, :created_by, :title, :description, :status, :assigned_to, :deadline)";
        $query = $database->prepare($sql);
        $query->execute(array(
            ':project_id'  => $projectId,
            ':created_by'  => $createdBy,
            ':title'       => $title,
            ':description' => $description,
            ':status'      => $status,
            ':assigned_to' => $assignedTo ?: null,
            ':deadline'    => $deadline ?: null
        ));
        $id = $database->lastInsertId();
        return $id ? (int)$id : false;
    }

    /**
     * Get a single task by ID.
     *
     * @param int $taskId
     * @return object|false
     */
    public static function getTaskById($taskId)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "SELECT t.*,
                       u_assigned.user_name AS assigned_name,
                       u_creator.user_name  AS creator_name
                FROM tasks t
                LEFT JOIN users u_assigned ON u_assigned.user_id = t.assigned_to
                LEFT JOIN users u_creator  ON u_creator.user_id  = t.created_by
                WHERE t.id = :id
                LIMIT 1";
        $query = $database->prepare($sql);
        $query->execute(array(':id' => $taskId));
        return $query->fetch();
    }

    /**
     * Get all tasks for a project, grouped by status.
     * Returns array with keys 'todo', 'inprogress', 'done'.
     *
     * @param int $projectId
     * @return array
     */
    public static function getTasksByProject($projectId)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "SELECT t.*,
                       u_assigned.user_name AS assigned_name,
                       u_creator.user_name  AS creator_name
                FROM tasks t
                LEFT JOIN users u_assigned ON u_assigned.user_id = t.assigned_to
                LEFT JOIN users u_creator  ON u_creator.user_id  = t.created_by
                WHERE t.project_id = :project_id
                ORDER BY t.created_at ASC";
        $query = $database->prepare($sql);
        $query->execute(array(':project_id' => $projectId));
        $all = $query->fetchAll();

        // group by status for easy use in the board view
        $grouped = array('todo' => array(), 'inprogress' => array(), 'done' => array());
        foreach ($all as $task) {
            $grouped[$task->status][] = $task;
        }
        return $grouped;
    }

    /**
     * Get all tasks assigned to a user across all projects.
     * Used for the navigation badge count.
     *
     * @param int $userId
     * @return array
     */
    public static function getTasksAssignedToUser($userId)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "SELECT t.*, p.name AS project_name
                FROM tasks t
                JOIN projects p ON p.id = t.project_id
                WHERE t.assigned_to = :user_id AND t.status != 'done'
                ORDER BY t.deadline ASC, t.created_at ASC";
        $query = $database->prepare($sql);
        $query->execute(array(':user_id' => $userId));
        return $query->fetchAll();
    }

    /**
     * Update a task's fields.
     *
     * @param int    $taskId
     * @param string $title
     * @param string $description
     * @param string $status
     * @param int    $assignedTo
     * @param string $deadline
     * @return bool
     */
    public static function updateTask($taskId, $title, $description, $status, $assignedTo = null, $deadline = null)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "UPDATE tasks
                SET title = :title, description = :description, status = :status,
                    assigned_to = :assigned_to, deadline = :deadline
                WHERE id = :id
                LIMIT 1";
        $query = $database->prepare($sql);
        return $query->execute(array(
            ':title'       => $title,
            ':description' => $description,
            ':status'      => $status,
            ':assigned_to' => $assignedTo ?: null,
            ':deadline'    => $deadline ?: null,
            ':id'          => $taskId
        ));
    }

    /**
     * Change only the status of a task (used by Drag & Drop).
     *
     * @param int    $taskId
     * @param string $status  'todo', 'inprogress' or 'done'
     * @return bool
     */
    public static function changeStatus($taskId, $status)
    {
        $allowed = array('todo', 'inprogress', 'done');
        if (!in_array($status, $allowed)) {
            return false;
        }
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "UPDATE tasks SET status = :status WHERE id = :id LIMIT 1";
        $query = $database->prepare($sql);
        return $query->execute(array(':status' => $status, ':id' => $taskId));
    }

    /**
     * Delete a task.
     *
     * @param int $taskId
     * @return bool
     */
    public static function deleteTask($taskId)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "DELETE FROM tasks WHERE id = :id LIMIT 1";
        $query = $database->prepare($sql);
        return $query->execute(array(':id' => $taskId));
    }

    /**
     * Add a comment to a task.
     *
     * @param int    $taskId
     * @param int    $userId
     * @param string $commentText
     * @return bool
     */
    public static function addComment($taskId, $userId, $commentText)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "INSERT INTO task_comments (task_id, user_id, comment_text) VALUES (:task_id, :user_id, :comment_text)";
        $query = $database->prepare($sql);
        return $query->execute(array(
            ':task_id'      => $taskId,
            ':user_id'      => $userId,
            ':comment_text' => $commentText
        ));
    }

    /**
     * Get all comments for a task.
     *
     * @param int $taskId
     * @return array
     */
    public static function getComments($taskId)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "SELECT tc.*, u.user_name
                FROM task_comments tc
                JOIN users u ON u.user_id = tc.user_id
                WHERE tc.task_id = :task_id
                ORDER BY tc.created_at ASC";
        $query = $database->prepare($sql);
        $query->execute(array(':task_id' => $taskId));
        return $query->fetchAll();
    }
}
