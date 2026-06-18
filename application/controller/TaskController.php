<?php

/**
 * TaskController
 *
 * Handles task creation, editing, deletion, status changes and comments.
 * All actions require login and project membership.
 */
class TaskController extends Controller
{
    /**
     * Auth check in constructor — all actions require login.
     */
    public function __construct()
    {
        parent::__construct();
        Auth::checkAuthentication();
    }

    /**
     * Show create task form (GET) or handle submit (POST).
     * Requires project membership.
     *
     * @param int $projectId
     */
    public function create($projectId)
    {
        $userId = Session::get('user_id');

        if (!ProjectModel::isMember($projectId, $userId)) {
            Session::add('feedback_negative', 'Kein Zugriff auf dieses Projekt.');
            Redirect::to('project');
            return;
        }

        $project = ProjectModel::getProjectById($projectId);
        $members = ProjectModel::getMembers($projectId);

        if (Request::post('submit')) {
            $title       = Request::post('title');
            $description = Request::post('description');
            $status      = Request::post('status') ?: 'todo';
            $assignedTo  = Request::post('assigned_to') ?: null;
            $deadline    = Request::post('deadline') ?: null;
            $priority    = Request::post('priority') ?: 'medium';

            if (empty($title)) {
                Session::add('feedback_negative', 'Titel darf nicht leer sein.');
                $this->View->render('task/create', array('project' => $project, 'members' => $members));
                return;
            }

            $taskId = TaskModel::createTask($projectId, $userId, $title, $description, $status, $assignedTo, $deadline, $priority);

            if ($taskId) {
                Session::add('feedback_positive', 'Task erstellt.');
            } else {
                Session::add('feedback_negative', 'Fehler beim Erstellen des Tasks.');
            }

            Redirect::to('project/board/' . $projectId);
            return;
        }

        // pre-select status from URL param (e.g. ?status=todo)
        $preStatus = Request::get('status') ?: 'todo';

        $this->View->render('task/create', array(
            'project'    => $project,
            'members'    => $members,
            'pre_status' => $preStatus
        ));
    }

    /**
     * Show edit task form (GET) or handle submit (POST).
     * Requires project membership.
     *
     * @param int $taskId
     */
    public function edit($taskId)
    {
        $userId = Session::get('user_id');
        $task   = TaskModel::getTaskById($taskId);

        if (!$task) {
            Redirect::to('project');
            return;
        }

        if (!ProjectModel::isMember($task->project_id, $userId)) {
            Session::add('feedback_negative', 'Kein Zugriff auf diesen Task.');
            Redirect::to('project');
            return;
        }

        $project  = ProjectModel::getProjectById($task->project_id);
        $members  = ProjectModel::getMembers($task->project_id);
        $comments = TaskModel::getComments($taskId);

        if (Request::post('submit')) {
            $title       = Request::post('title');
            $description = Request::post('description');
            $status      = Request::post('status') ?: 'todo';
            $assignedTo  = Request::post('assigned_to') ?: null;
            $deadline    = Request::post('deadline') ?: null;
            $priority    = Request::post('priority') ?: 'medium';

            if (empty($title)) {
                Session::add('feedback_negative', 'Titel darf nicht leer sein.');
                $this->View->render('task/edit', array(
                    'task'     => $task,
                    'project'  => $project,
                    'members'  => $members,
                    'comments' => $comments
                ));
                return;
            }

            TaskModel::updateTask($taskId, $title, $description, $status, $assignedTo, $deadline, $priority);
            Session::add('feedback_positive', 'Task aktualisiert.');
            Redirect::to('project/board/' . $task->project_id);
            return;
        }

        $this->View->render('task/edit', array(
            'task'     => $task,
            'project'  => $project,
            'members'  => $members,
            'comments' => $comments
        ));
    }

    /**
     * Delete a task. Only task creator or project owner.
     *
     * @param int $taskId
     */
    public function delete($taskId)
    {
        $userId = Session::get('user_id');
        $task   = TaskModel::getTaskById($taskId);

        if (!$task) {
            Redirect::to('project');
            return;
        }

        $projectId = $task->project_id;

        // only task creator or project owner can delete
        if ($task->created_by != $userId && !ProjectModel::isOwner($projectId, $userId)) {
            Session::add('feedback_negative', 'Keine Berechtigung zum Löschen.');
            Redirect::to('project/board/' . $projectId);
            return;
        }

        TaskModel::deleteTask($taskId);
        Session::add('feedback_positive', 'Task gelöscht.');
        Redirect::to('project/board/' . $projectId);
    }

    /**
     * Change task status via POST (used by Drag & Drop AJAX and status dropdown).
     * Requires project membership. Expects JSON response.
     *
     * @param int $taskId
     */
    public function changeStatus($taskId)
    {
        $userId = Session::get('user_id');
        $task   = TaskModel::getTaskById($taskId);

        if (!$task || !ProjectModel::isMember($task->project_id, $userId)) {
            $this->View->renderJSON(array('success' => false, 'error' => 'Kein Zugriff.'));
            return;
        }

        $status = Request::post('status');
        $result = TaskModel::changeStatus($taskId, $status);

        $this->View->renderJSON(array('success' => $result));
    }

    /**
     * Add a comment to a task. POST. Requires project membership.
     *
     * @param int $taskId
     */
    public function addComment($taskId)
    {
        $userId = Session::get('user_id');
        $task   = TaskModel::getTaskById($taskId);

        if (!$task || !ProjectModel::isMember($task->project_id, $userId)) {
            Session::add('feedback_negative', 'Kein Zugriff.');
            Redirect::to('project');
            return;
        }

        $text = Request::post('comment_text');
        if (!empty($text)) {
            TaskModel::addComment($taskId, $userId, $text);
        }

        Redirect::to('task/edit/' . $taskId);
    }
}
