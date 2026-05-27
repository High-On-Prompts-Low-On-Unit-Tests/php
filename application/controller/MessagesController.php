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

        $this->View->users = MessageModel::getChatPartners($me);
        $this->View->unread = MessageModel::getUnreadCounts($me);

        $partner_id = Request::get('partner');

        if ($partner_id) {
            MessageModel::markAsRead($partner_id, $me);
            $this->View->active_partner_id = $partner_id;
            $this->View->messages = MessageModel::getConversation($me, $partner_id);
        } else {
            $this->View->active_partner_id = null;
            $this->View->messages = [];
        }
        $this->View->render('messages/index');
    }
    public function send()
    {
        $from = Session::get('user_id');
        $to = Request::get('to');
        $text = Request::get('text');

        if (!empty($to) && strlen(trim($text)) > 0) {
            MessageModel::sendMessage($from, $to, $text);
        }
        Redirect::to('messages/index?partner=' . $to);
    }
}