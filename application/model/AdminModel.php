<?php

/**
 * Handles all data manipulation of the admin part
 */
class AdminModel
{
    /**
     * Sets the account type, deletion and suspension values for a user
     *
     * @param int $suspensionInDays Number of days to suspend the user
     * @param string $softDelete Checkbox value ("on" or null)
     * @param int $userId The user's id
     * @param int $accountType The new account type / group id
     */
    public static function setAccountSuspensionAndDeletionStatus($suspensionInDays, $softDelete, $userId, $accountType = null)
    {

        if ($userId == Session::get('user_id')) {
            Session::add('feedback_negative', Text::get('FEEDBACK_ACCOUNT_CANT_DELETE_SUSPEND_OWN'));
            return false;
        }

        if ($accountType !== null) {
            self::updateUserGroupInDatabase($userId, $accountType);
        }

        if ($suspensionInDays > 0) {
            $suspensionTime = time() + ($suspensionInDays * 60 * 60 * 24);
        } else {
            $suspensionTime = null;
        }

        if ($softDelete == "on") {
            $delete = 1;
        } else {
            $delete = 0;
        }

        self::writeDeleteAndSuspensionInfoToDatabase($userId, $suspensionTime, $delete);

        if ($suspensionTime != null OR $delete == 1) {
            self::resetUserSession($userId);
        }
    }

    /**
     * Updates the user's account type / group in the database
     *
     * @param int $userId The user's id
     * @param int $accountType The new account type / group id
     * @return bool
     */
    private static function updateUserGroupInDatabase($userId, $accountType)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $query = $database->prepare("UPDATE users SET user_account_type = :account_type WHERE user_id = :user_id LIMIT 1");
        $query->execute(array(
            ':account_type' => $accountType,
            ':user_id' => $userId
        ));

        if ($query->rowCount() == 1) {
            Session::add('feedback_positive', Text::get('FEEDBACK_ACCOUNT_TYPE_CHANGE_SUCCESSFUL'));
            return true;
        }
        return false;
    }

    /**
     * Writes the deletion and suspension info for the user into the database
     *
     * @param int $userId The user's id
     * @param int|null $suspensionTime Unix timestamp when suspension expires
     * @param int $delete 1 for deleted, 0 for active
     * @return bool
     */
    private static function writeDeleteAndSuspensionInfoToDatabase($userId, $suspensionTime, $delete)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $query = $database->prepare("UPDATE users SET user_suspension_timestamp = :user_suspension_timestamp, user_deleted = :user_deleted  WHERE user_id = :user_id LIMIT 1");
        $query->execute(array(
                ':user_suspension_timestamp' => $suspensionTime,
                ':user_deleted' => $delete,
                ':user_id' => $userId
        ));

        if ($query->rowCount() == 1) {
            Session::add('feedback_positive', Text::get('FEEDBACK_ACCOUNT_SUSPENSION_DELETION_STATUS'));
            return true;
        }
    }

    /**
     * Kicks the selected user out of the system instantly by resetting the user's session.
     * This means, the user will be "logged out".
     *
     * @param $userId
     * @return bool
     */
    private static function resetUserSession($userId)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $query = $database->prepare("UPDATE users SET session_id = :session_id  WHERE user_id = :user_id LIMIT 1");
        $query->execute(array(
                ':session_id' => null,
                ':user_id' => $userId
        ));

        if ($query->rowCount() == 1) {
            Session::add('feedback_positive', Text::get('FEEDBACK_ACCOUNT_USER_SUCCESSFULLY_KICKED'));
            return true;
        }
    }
}
