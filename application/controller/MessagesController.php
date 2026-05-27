<?php
class MessagesController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        Auth::checkAuthentication();
    }

    public function index()
    {
        $me = Session::get('user_id');

        $this->View->groups = MessageModel::getUserGroups($me);
        $this->View->unread = MessageModel::getUnreadCounts($me);
        $this->View->all_users = MessageModel::getAllUsers($me);

        $group_id = Request::get('group');

        if ($group_id && MessageModel::isGroupMember($group_id, $me)) {
            MessageModel::markAsRead($group_id, $me);
            $this->View->active_group_id = (int) $group_id;
            $this->View->active_group = MessageModel::getGroup($group_id);
            $this->View->messages = MessageModel::getGroupMessages($group_id);
            $this->View->members = MessageModel::getGroupMembers($group_id);
            $this->View->is_admin = MessageModel::isGroupAdmin($group_id, $me);
        } else {
            $this->View->active_group_id = null;
            $this->View->active_group = null;
            $this->View->messages = [];
            $this->View->members = [];
            $this->View->is_admin = false;
        }

        $this->View->render('messages/index');
    }

    public function send()
    {
        $from = Session::get('user_id');
        $group_id = Request::post('group_id');
        $text = Request::post('text');

        if (!empty($group_id) && strlen(trim($text)) > 0) {
            MessageModel::sendMessage($group_id, $from, $text);
        }

        Redirect::to('messages/index?group=' . $group_id);
    }

    public function startDirect($partner_id = null)
    {
        if (!$partner_id) {
            Redirect::to('messages/index');
            return;
        }

        $me = Session::get('user_id');
        $group_id = MessageModel::getOrCreateDirectChat($me, (int) $partner_id);

        if ($group_id) {
            Redirect::to('messages/index?group=' . $group_id);
        } else {
            Redirect::to('messages/index');
        }
    }

    public function createGroup()
    {
        $me = Session::get('user_id');
        $name = Request::post('group_name');
        $member_ids = isset($_POST['members']) ? $_POST['members'] : [];

        if (empty(trim($name)) || empty($member_ids)) {
            Redirect::to('messages/index');
            return;
        }

        $member_ids = array_map('intval', $member_ids);
        $group_id = MessageModel::createGroup($name, $me, $member_ids);

        Redirect::to('messages/index?group=' . $group_id);
    }

    public function addMember()
    {
        $me = Session::get('user_id');
        $group_id = Request::post('group_id');
        $user_id = Request::post('user_id');

        if ($group_id && $user_id && MessageModel::isGroupAdmin($group_id, $me)) {
            MessageModel::addMember($group_id, (int) $user_id);
        }

        Redirect::to('messages/index?group=' . $group_id);
    }

    public function removeMember()
    {
        $me = Session::get('user_id');
        $group_id = Request::post('group_id');
        $user_id = Request::post('user_id');

        if ($group_id && $user_id) {
            MessageModel::removeMember($group_id, (int) $user_id, $me);
        }

        Redirect::to('messages/index?group=' . $group_id);
    }

    public function leaveGroup()
    {
        $me = Session::get('user_id');
        $group_id = Request::post('group_id');

        if ($group_id) {
            MessageModel::leaveGroup($group_id, $me);
        }

        Redirect::to('messages/index');
    }

    public function renameGroup()
    {
        $me = Session::get('user_id');
        $group_id = Request::post('group_id');
        $name = Request::post('group_name');

        if ($group_id && !empty(trim($name))) {
            MessageModel::renameGroup($group_id, $name, $me);
        }

        Redirect::to('messages/index?group=' . $group_id);
    }
}
