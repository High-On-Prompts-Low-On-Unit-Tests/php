<?php

/**
 * ProjectController
 *
 * Handles project listing, creation, editing, deletion and member management.
 * All actions require login.
 */
class ProjectController extends Controller
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
     * List all projects the current user is a member of.
     */
    public function index()
    {
        $this->View->render('project/index', array(
            'projects' => ProjectModel::getProjectsForUser(Session::get('user_id'))
        ));
    }

    /**
     * Show the Kanban board for a project.
     *
     * @param int $projectId
     */
    public function board($projectId)
    {
        $userId = Session::get('user_id');

        if (!ProjectModel::isMember($projectId, $userId)) {
            Session::add('feedback_negative', 'Kein Zugriff auf dieses Projekt.');
            Redirect::to('project');
            return;
        }

        $project = ProjectModel::getProjectById($projectId);
        $tasks   = TaskModel::getTasksByProject($projectId);
        $members = ProjectModel::getMembers($projectId);

        $this->View->render('project/board', array(
            'project'   => $project,
            'tasks'     => $tasks,
            'members'   => $members,
            'is_owner'  => ProjectModel::isOwner($projectId, $userId)
        ));
    }

    /**
     * Show create project form (GET) or handle form submit (POST).
     */
    public function create()
    {
        if (Request::post('submit')) {
            $name        = Request::post('name');
            $description = Request::post('description');

            if (empty($name)) {
                Session::add('feedback_negative', 'Projektname darf nicht leer sein.');
                $this->View->render('project/create');
                return;
            }

            $projectId = ProjectModel::createProject(Session::get('user_id'), $name, $description);

            if ($projectId) {
                Session::add('feedback_positive', 'Projekt erstellt.');
                Redirect::to('project/board/' . $projectId);
            } else {
                Session::add('feedback_negative', 'Fehler beim Erstellen des Projekts.');
                $this->View->render('project/create');
            }
            return;
        }

        $this->View->render('project/create');
    }

    /**
     * Show edit project form (GET) or handle form submit (POST).
     * Owner only.
     *
     * @param int $projectId
     */
    public function edit($projectId)
    {
        $userId = Session::get('user_id');

        if (!ProjectModel::isOwner($projectId, $userId)) {
            Session::add('feedback_negative', 'Nur der Owner kann das Projekt bearbeiten.');
            Redirect::to('project/board/' . $projectId);
            return;
        }

        $project = ProjectModel::getProjectById($projectId);

        if (Request::post('submit')) {
            $name        = Request::post('name');
            $description = Request::post('description');

            if (empty($name)) {
                Session::add('feedback_negative', 'Projektname darf nicht leer sein.');
                $this->View->render('project/edit', array('project' => $project));
                return;
            }

            ProjectModel::updateProject($projectId, $name, $description);
            Session::add('feedback_positive', 'Projekt aktualisiert.');
            Redirect::to('project/board/' . $projectId);
            return;
        }

        $this->View->render('project/edit', array('project' => $project));
    }

    /**
     * Delete a project. Owner only.
     *
     * @param int $projectId
     */
    public function delete($projectId)
    {
        $userId = Session::get('user_id');

        if (!ProjectModel::isOwner($projectId, $userId)) {
            Session::add('feedback_negative', 'Nur der Owner kann das Projekt löschen.');
            Redirect::to('project');
            return;
        }

        ProjectModel::deleteProject($projectId);
        Session::add('feedback_positive', 'Projekt gelöscht.');
        Redirect::to('project');
    }

    /**
     * Add a member to a project by username. Owner only. POST.
     *
     * @param int $projectId
     */
    public function addMember($projectId)
    {
        $userId = Session::get('user_id');

        if (!ProjectModel::isOwner($projectId, $userId)) {
            Session::add('feedback_negative', 'Nur der Owner kann Mitglieder hinzufügen.');
            Redirect::to('project/board/' . $projectId);
            return;
        }

        $username = Request::post('username');
        $user     = ProjectModel::findUserByName($username);

        if (!$user) {
            Session::add('feedback_negative', 'Benutzer "' . htmlspecialchars($username) . '" nicht gefunden.');
            Redirect::to('project/board/' . $projectId);
            return;
        }

        if (ProjectModel::isMember($projectId, $user->user_id)) {
            Session::add('feedback_negative', 'Benutzer ist bereits Mitglied.');
            Redirect::to('project/board/' . $projectId);
            return;
        }

        ProjectModel::addMember($projectId, $user->user_id, 'member');
        Session::add('feedback_positive', htmlspecialchars($username) . ' wurde hinzugefügt.');
        Redirect::to('project/board/' . $projectId);
    }

    /**
     * Remove a member from a project. Owner only.
     *
     * @param int $projectId
     * @param int $memberId
     */
    public function removeMember($projectId, $memberId)
    {
        $userId = Session::get('user_id');

        if (!ProjectModel::isOwner($projectId, $userId)) {
            Session::add('feedback_negative', 'Nur der Owner kann Mitglieder entfernen.');
            Redirect::to('project/board/' . $projectId);
            return;
        }

        ProjectModel::removeMember($projectId, $memberId);
        Session::add('feedback_positive', 'Mitglied entfernt.');
        Redirect::to('project/board/' . $projectId);
    }
}
