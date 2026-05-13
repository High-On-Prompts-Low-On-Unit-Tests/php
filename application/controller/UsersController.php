<?php

/**
 * Shows a public read-only list of all users and their groups
 */
class UsersController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Renders the public user list with group names
     */
    public function index()
    {
        $this->View->render('users/index', array(
            'users' => UserModel::getPublicProfilesOfAllUsers()
        ));
    }
}
