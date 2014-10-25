<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2014 Eventum Team.                              |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+


/**
 * Class to handle the business logic related to the administration
 * of users and permissions in the system.
 */

class User
{

    // definition of roles
    private static $roles = array(
        1 => "Viewer",
        2 => "Reporter",
        3 => "Customer",
        4 => "Standard User",
        5 => "Developer",
        6 => "Manager",
        7 => "Administrator"
    );

    private static $localized_roles;
    private static function getLocalizedRoles()
    {
        if (self::$localized_roles === null) {
            foreach (self::$roles as $id => $role) {
                self::$localized_roles[$id] = ev_gettext($role);
            }
        }

        return self::$localized_roles;
    }

    /**
     * Method to reset localized roles, i.e after changing active language.
     */
    public static function resetLocalizedRoles()
    {
        self::$localized_roles = null;
    }

    /**
     * Method used to get the user ID associated with the given customer
     * contact ID.
     *
     * @param   integer $customer_contact_id The customer contact ID
     * @return  integer The user ID
     */
    public static function getUserIDByContactID($customer_contact_id)
    {
        $stmt = "SELECT
                    usr_id
                 FROM
                    {{%user}}
                 WHERE
                    usr_customer_contact_id=?";

        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($customer_contact_id));
        } catch (DbException $e) {
            return '';
        }
        return $res;
    }

    /**
     * Method used to get the account email address associated with the given
     * customer contact ID.
     *
     * @param   integer $customer_contact_id The customer contact ID
     * @return  string The user's email address
     */
    public static function getEmailByContactID($customer_contact_id)
    {
        $stmt = "SELECT
                    usr_email
                 FROM
                    {{%user}}
                 WHERE
                    usr_customer_contact_id=?";
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($customer_contact_id));
        } catch (DbException $e) {
            return '';
        }
        return $res;
    }

    /**
     * Method used to get the SMS email address associated with the given
     * user ID.
     *
     * @param   integer $usr_id The user ID
     * @return  string The user's SMS email address
     */
    public static function getSMS($usr_id)
    {
        $stmt = "SELECT
                    usr_sms_email
                 FROM
                    {{%user}}
                 WHERE
                    usr_id=?";
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($usr_id));
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to update the SMS email address associated with the given
     * user ID.
     *
     * @param   integer $usr_id The user ID
     * @param   string $sms_email The user's SMS email address
     * @return  boolean Whether the update was successfull or not
     */
    public static function updateSMS($usr_id, $sms_email)
    {
        $stmt = "UPDATE
                    {{%user}}
                 SET
                    usr_sms_email=?
                 WHERE
                    usr_id=?";
        try {
            DB_Helper::getInstance()->query($stmt, array($sms_email, $usr_id));
        } catch (DbException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to get the customer contact ID associated with
     * the given user ID.
     *
     * @param   integer $usr_id The user ID
     * @return  integer The customer contact ID
     */
    public static function getCustomerContactID($usr_id)
    {
        $stmt = "SELECT
                    usr_customer_contact_id
                 FROM
                    {{%user}}
                 WHERE
                    usr_id=?";
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($usr_id));
        } catch (DbException $e) {
            return -1;
        }

        return $res;
    }

    /**
     * Method used to get the customer ID associated with
     * the given user ID.
     *
     * @param   integer $usr_id The user ID
     * @return  integer The customer ID
     */
    public static function getCustomerID($usr_id)
    {
        static $returns;

        if (!empty($returns[$usr_id])) {
            return $returns[$usr_id];
        }

        $stmt = "SELECT
                    usr_customer_id
                 FROM
                    {{%user}}
                 WHERE
                    usr_id=?";
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($usr_id));
        } catch (DbException $e) {
            return -1;
        }

        $returns[$usr_id] = $res;

        return $res;
    }

    /**
     * Method used to update the user account and set the user as a confirmed one.
     *
     * @param   string $email The email address
     * @return  boolean
     */
    public static function confirmVisitorAccount($email)
    {
        $stmt = "UPDATE
                    {{%user}}
                 SET
                    usr_status='active'
                 WHERE
                    usr_email=?";
        try {
            DB_Helper::getInstance()->query($stmt, array($email));
        } catch (DbException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to check whether the hash passed in the confirmation URL is
     * a valid one when comparing against the provided email address.
     *
     * @param   string $email The email address associated with the user account
     * @param   string $hash The md5 hash string to be checked against
     * @return  integer -1 if there was an error in the query, -2 for users that don't exist,
     *                  -3 if it cannot be authenticated and 1 if it did work
     */
    public static function checkHash($email, $hash)
    {
        $stmt = "SELECT
                    usr_full_name
                 FROM
                    {{%user}}
                 WHERE
                    usr_email=?";
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($email));
        } catch (DbException $e) {
            return -1;
        }

        if ($res == null) {
            return -2;
        }
        $check_hash = md5($res . $email . Auth::privateKey());
        if ($hash != $check_hash) {
            return -3;
        }
        return 1;
    }

    /**
     * Method used to create a new user account with pending status and send a
     * confirmation email to the prospective user.
     *
     * @param   string $role The user role
     * @param   array $projects The list of projects that this user will be associated with
     * @return  integer 1 if the creation worked, -1 otherwise
     */
    public static function createVisitorAccount($role, $projects)
    {
        // check for double submits
        if (Auth::userExists($_POST["email"])) {
            return -2;
        }

        $stmt = "INSERT INTO
                    {{%user}}
                 (
                    usr_created_date,
                    usr_password,
                    usr_full_name,
                    usr_email,
                    usr_status
                 ) VALUES (?, ?, ?, ?, ?)";
        try {
            DB_Helper::getInstance()->query(
                $stmt, array(
                    Date_Helper::getCurrentDateGMT(),
                    Auth::hashPassword($_POST["passwd"]),
                    $_POST["full_name"],
                    $_POST["email"],
                    'pending',
                )
            );
        } catch (DbException $e) {
            return -1;
        }

        $new_usr_id = DB_Helper::get_last_insert_id();
        // add the project associations!
        for ($i = 0; $i < count($projects); $i++) {
            Project::associateUser($projects[$i], $new_usr_id, $role);
        }

        Prefs::set($new_usr_id, Prefs::getDefaults($projects));

        // send confirmation email to user
        $hash = md5($_POST["full_name"] . $_POST["email"] . Auth::privateKey());

        $tpl = new Template_Helper();
        $tpl->setTemplate('notifications/visitor_account.tpl.text');
        $tpl->bulkAssign(array(
            "app_title"   => Misc::getToolCaption(),
            "email"     =>  $_POST['email'],
            'hash'      =>  $hash
        ));
        $text_message = $tpl->getTemplateContents();

        $setup = Setup::load();
        $mail = new Mail_Helper();
        // need to make this message MIME based
        $mail->setTextBody($text_message);
        $mail->send($setup["smtp"]["from"], $_POST["email"], APP_SHORT_NAME . ": New Account - Confirmation Required");

        return 1;
    }

    /**
     * Method used to send a confirmation email to the user that is associated
     * to the email address.
     *
     * @param   string $usr_id The user ID
     * @return  void
     */
    public static function sendPasswordConfirmationEmail($usr_id)
    {
        $info = self::getDetails($usr_id);
        // send confirmation email to user
        $hash = md5($info["usr_full_name"] . $info["usr_email"] . Auth::privateKey());

        $tpl = new Template_Helper();
        $tpl->setTemplate('notifications/password_confirmation.tpl.text');
        $tpl->bulkAssign(array(
            "app_title" => Misc::getToolCaption(),
            "user"      =>  $info,
            'hash'      =>  $hash
        ));
        $text_message = $tpl->getTemplateContents();

        $setup = Setup::load();
        $mail = new Mail_Helper();
        // need to make this message MIME based
        $mail->setTextBody($text_message);
        $mail->send($setup["smtp"]["from"], $info["usr_email"], APP_SHORT_NAME . ": New Password - Confirmation Required");
    }

    /**
     * Method used to confirm the request of a new password and send an email
     * to the user with the new random password.
     *
     * @param   string $email The email address
     * @return  void
     */
    public static function confirmNewPassword($email)
    {
        $usr_id = self::getUserIDByEmail($email);
        // create the new password
        $password = substr(md5(microtime() . uniqid("")), 0, 12);
        Auth::updatePassword($usr_id, $password, $password, true);
    }

    public static function getUserIDByExternalID($external_id)
    {
        $sql = "SELECT
                    usr_id
                FROM
                    {{%user}}
                WHERE
                    usr_external_id=?";
        try {
            $res = DB_Helper::getInstance()->getOne($sql, array($external_id));
        } catch (DbException $e) {
            return null;
        }

        return $res;
    }

    /**
     * Method used to lookup the user ID of a given email address.
     *
     * @param   string $email The email address associated with the user account
     * @param   boolean $check_aliases If user aliases should be checked as well.
     * @return  integer The user ID
     */
    public static function getUserIDByEmail($email, $check_aliases = false)
    {
        static $returns;

        if (!is_string($email)) {
            if (PEAR::isError($email)) {
                Error_Handler::logError(array($email->getMessage(), $email->getDebugInfo()), __FILE__, __LINE__);

                return null;
            }

            Error_Handler::logError('$email parameter is not a string: '.gettype($email), __FILE__, __LINE__);

            return null;
        }

        if (!empty($returns[$email])) {
            return $returns[$email];
        }

        $stmt = "SELECT
                    usr_id
                 FROM
                    {{%user}}
                 WHERE
                    usr_email=?";
        $res = DB_Helper::getInstance()->getOne($stmt, array($email));

        if ((empty($res)) && $check_aliases) {
            $returns[$email] = self::getUserIDByAlias($email);
        } else {
            $returns[$email] = $res;
        }

        return $returns[$email];
    }

    /**
     * Method used to check whether an user is set to status active
     * or not.
     *
     * @param   string $status The status of the user
     * @return  boolean
     */
    public static function isActiveStatus($status)
    {
        if ($status == 'active') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Method used to check whether an user is set to status pending
     * or not.
     *
     * @param   string $status The status of the user
     * @return  boolean
     */
    public static function isPendingStatus($status)
    {
        if ($status == 'pending') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Method used to get the list of all active users available in the system
     * as an associative array of user IDs => user full names.
     *
     * @param   integer $prj_id The id of the project to show users from
     * @param   integer $role The role ID of the user
     * @param   boolean $exclude_grouped If users with a group should be excluded
     * @Param   integer $grp_id The ID of the group.
     * @return  array The associative array of users
     */
    public static function getActiveAssocList($prj_id = false, $role = null, $exclude_grouped = false, $grp_id = false)
    {
        $grp_id = Misc::escapeInteger($grp_id);
        $stmt = "SELECT
                    usr_id,
                    usr_full_name
                 FROM
                    {{%user}}";
        if ($prj_id != false) {
            $stmt .= ",
                    {{%project_user}}";
        }
        $stmt .= "
                 WHERE
                    usr_status='active' AND
                    usr_id != " . APP_SYSTEM_USER_ID;
        if ($prj_id != false) {
            $stmt .= " AND pru_prj_id = " . Misc::escapeInteger($prj_id) . " AND
                       usr_id = pru_usr_id";
            if ($role != NULL) {
                $stmt .= " AND pru_role > $role ";
            }
        }
        if ($grp_id != false) {
            if ($exclude_grouped == false) {
                $stmt .= " AND (usr_grp_id IS NULL OR usr_grp_id = $grp_id)";
            } else {
                $stmt .= " AND usr_grp_id = $grp_id";
            }
        } elseif ($exclude_grouped == true) {
            $stmt .= " AND (usr_grp_id IS NULL or usr_grp_id = 0)";
        }
        $stmt .= "
                 ORDER BY
                    usr_full_name ASC";
        try {
            $res = DB_Helper::getInstance()->getAssoc($stmt);
        } catch (DbException $e) {
            return "";
        }

        return $res;
    }

    /**
     * Method used to get an associative array of the available roles.
     *
     * @return  array The list of roles
     */
    public static function getAssocRoleIDs()
    {
        $assoc_roles = array();
        foreach (self::$roles as $key => $value) {
            $value = str_replace(" ", "_", strtolower($value));
            $assoc_roles[$value] = (integer) $key;
        }

        return $assoc_roles;
    }

    /**
     * Method used to get the full list of roles available in the
     * system.
     *
     * @param   array $exclude_role The list of roles to ignore
     * @return  array The list of roles
     */
    public static function getRoles($exclude_role = null)
    {
        if (empty($exclude_role)) {
            return self::getLocalizedRoles();
        }

        $roles = self::getLocalizedRoles();
        if (!is_array($exclude_role)) {
            $exclude_role = array($exclude_role);
        }

        foreach ($exclude_role as $role_title) {
            unset($roles[self::getRoleID($role_title)]);
        }

        return $roles;
    }

    /**
     * Method used to get the role title for a specific role ID.
     *
     * @param   integer $role_id The role ID
     * @return  string The role title
     */
    public static function getRole($role_id)
    {
        $roles = self::getLocalizedRoles();
        // XXX manage/custom_fields.php uses role_id = 9 as "Never Display", which is hack
        return isset($roles[$role_id]) ? $roles[$role_id] : null;
    }

    /**
     * Method used to get the role ID for a specific role title.
     *
     * @param   string $role_title The role title
     * @return  integer The role ID
     */
    public static function getRoleID($role_title)
    {
        foreach (self::$roles as $role_id => $role) {
            if (strtolower($role) == strtolower($role_title)) {
                return $role_id;
            }
        }
        return null;
    }

    /**
     * Method used to get the role for a specific user and project.
     *
     * @param   integer $usr_id The user ID
     * @param   integer $prj_id The project ID
     * @return  integer The role ID
     */
    public static function getRoleByUser($usr_id, $prj_id)
    {
        static $returns;

        if ($usr_id == APP_SYSTEM_USER_ID) {
            return self::getRoleID("Administrator");
        }

        if (!empty($returns[$usr_id][$prj_id])) {
            return $returns[$usr_id][$prj_id];
        }

        $stmt = "SELECT
                    pru_role
                 FROM
                    {{%project_user}}
                 WHERE
                    pru_usr_id=? AND
                    pru_prj_id=?";
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($usr_id, $prj_id));
        } catch (DbException $e) {
            return "";
        }

        $returns[$usr_id][$prj_id] = $res;
        return $res;
    }

    /**
     * Method used to get the account details of a specific user.
     *
     * @param   integer $usr_id The user ID
     * @return  array The account details
     */
    public static function getDetails($usr_id)
    {
        $res = self::getDetailsAssoc(array($usr_id));

        return reset($res);
    }

    /**
     * Method used to get the account details of a list users.
     *
     * @param   array $usr_ids The user ID
     * @return  array List of accounts with account details
     */
    public static function getDetailsAssoc($usr_ids)
    {
        static $returns;
        $key = md5(serialize($usr_ids));

        if (empty($returns[$key])) {
            $stmt = "SELECT
                        *
                     FROM
                        {{%user}}
                     WHERE
                        usr_id IN (" . implode(', ', Misc::escapeInteger($usr_ids)) . ")";
            try {
                $res = DB_Helper::getInstance()->getAll($stmt);
            } catch (DbException $e) {
                return null;
            }

            foreach ($res as &$row) {
                // FIXME: maybe PEAR has some "fill NULL" mode?
                if (!isset($row['usr_grp_id'])) {
                    $row['usr_grp_id'] = null;
                }

                $row['group'] = Group::getName($row['usr_grp_id']);
                $roles = Project::getAssocList($row['usr_id'], false, true);
                $row['projects'] = array_keys($roles);
                $row['roles'] = $roles;
            }
            $returns[$key] = $res;
        }

        return $returns[$key];
    }

    /**
     * Method used to get the full name of the specified user.
     *
     * @param   integer $usr_id The user ID
     * @return  string The user' full name
     */
    public static function getFullName($usr_id)
    {
        static $returns;

        if (!is_array($usr_id)) {
            $items = array($usr_id);
        } else {
            $items = $usr_id;
        }

        $key = md5(serialize($usr_id));
        if (!empty($returns[$key])) {
            return $returns[$key];
        }

        if (count($items) < 1) {
            if (!is_array($usr_id)) {
                return '';
            } else {
                return array();
            }
        }

        $stmt = "SELECT
                    usr_full_name
                 FROM
                    {{%user}}
                 WHERE
                    usr_id IN (" . implode(', ', Misc::escapeInteger($items)) . ")";
        try {
            if (!is_array($usr_id)) {
                $res = DB_Helper::getInstance()->getOne($stmt);
            } else {
                $res = DB_Helper::getInstance()->getColumn($stmt);
            }
        } catch (DbException $e) {
            return "";
        }

        $returns[$key] = $res;

        return $res;
    }

    /**
     * Method used to get the email address of the specified user.
     *
     * @param   integer $usr_id The user ID or user ids
     * @return  string The user' full name
     */
    public static function getEmail($usr_id)
    {
        static $returns;

        if (!is_array($usr_id)) {
            $items = array($usr_id);
        } else {
            $items = $usr_id;
        }

        $key = md5(serialize($usr_id));
        if (!empty($returns[$key])) {
            return $returns[$key];
        }

        if (count($items) < 1) {
            if (!is_array($usr_id)) {
                return '';
            } else {
                return array();
            }
        }

        $stmt = "SELECT
                    usr_email
                 FROM
                    {{%user}}
                 WHERE
                    usr_id IN (" . implode(', ', Misc::escapeInteger($items)) . ")";
        try {
            if (!is_array($usr_id)) {
                $res = DB_Helper::getInstance()->getOne($stmt);
            } else {
                $res = DB_Helper::getInstance()->getColumn($stmt);
            }
        } catch (DbException $e) {
            if (!is_array($usr_id)) {
                return '';
            } else {
                return array();
            }
        }

        $returns[$key] = $res;

        return $res;
    }

    /**
     * Method used to get the group id of the specified user.
     *
     * @param   integer $usr_id The user ID
     * @return  string The user' full name
     */
    public static function getGroupID($usr_id)
    {
        static $returns;

        if (!is_array($usr_id)) {
            $items = array($usr_id);
        } else {
            $items = $usr_id;
        }

        $key = md5(serialize($usr_id));
        if (!empty($returns[$key])) {
            return $returns[$key];
        }

        $stmt = "SELECT
                    usr_grp_id
                 FROM
                    {{%user}}
                 WHERE
                    usr_id IN (" . implode(', ', Misc::escapeInteger($items)) . ")";
        try {
            if (!is_array($usr_id)) {
                $res = DB_Helper::getInstance()->getOne($stmt);
            } else {
                $res = DB_Helper::getInstance()->getColumn($stmt);
            }
        } catch (DbException $e) {
            return "";
        }

        $returns[$key] = $res;

        return $res;
    }

    /**
     * Returns the status of the user associated with the given email address.
     *
     * @param   string $email The email address
     * @return  string The user status
     */
    public static function getStatusByEmail($email)
    {
        static $returns;

        if (isset($returns[$email])) {
            return $returns[$email];
        }

        $email = User::getEmail(User::getUserIDByEmail($email, true));

        $stmt = "SELECT
                    usr_status
                 FROM
                    {{%user}}
                 WHERE
                    usr_email=?";
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($email));
        } catch (DbException $e) {
            return '';
        }

        $returns[$email] = $res;

        return $res;
    }

    /**
     * Method used to change the status of users, making them inactive
     * or active.
     *
     * @return  boolean
     */
    public static function changeStatus()
    {
        // check if the user being inactivated is the last one
        $stmt = "SELECT
                    COUNT(*)
                 FROM
                    {{%user}}
                 WHERE
                    usr_status='active'";

        $total_active = DB_Helper::getInstance()->getOne($stmt);
        if (($total_active < 2) && ($_POST["status"] == "inactive")) {
            return false;
        }

        $items = @implode(", ", Misc::escapeInteger($_POST["items"]));
        $stmt = "UPDATE
                    {{%user}}
                 SET
                    usr_status=?
                 WHERE
                    usr_id IN ($items)";
        try {
            DB_Helper::getInstance()->query($stmt, array($_POST["status"]));
        } catch (DbException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to update the account password for a specific user.
     *
     * @param   integer $usr_id The user ID
     * @param   boolean $send_notification Whether to send the notification email or not
     * @return  integer 1 if the update worked, -1 otherwise
     */
    public static function updatePassword($usr_id, $send_notification = false)
    {
        if ($_POST['new_password'] != $_POST['confirm_password']) {
            return -2;
        }
        $stmt = "UPDATE
                    {{%user}}
                 SET
                    usr_password=?
                 WHERE
                    usr_id=?";
        try {
            DB_Helper::getInstance()->query(
                $stmt, array(Auth::hashPassword($_POST["new_password"]), $usr_id)
            );
        } catch (DbException $e) {
            return -1;
        }

        if ($send_notification) {
            Notification::notifyUserPassword($usr_id, $_POST["new_password"]);
        }

        return 1;
    }

    /**
     * Method used to update the account full name for a specific user.
     *
     * @param   integer $usr_id The user ID
     * @return  integer 1 if the update worked, -1 otherwise
     */
    public static function updateFullName($usr_id)
    {
        $full_name = trim(strip_tags($_POST["full_name"]));
        $stmt = "UPDATE
                    {{%user}}
                 SET
                    usr_full_name=?
                 WHERE
                    usr_id=?";
        try {
            DB_Helper::getInstance()->query($stmt, array($full_name, $usr_id));
        } catch (DbException $e) {
            return -1;
        }

        Notification::notifyUserAccount($usr_id);

        return 1;
    }

    /**
     * Method used to update the account email for a specific user.
     *
     * @param   integer $usr_id The user ID
     * @return  integer 1 if the update worked, -1 otherwise
     */
    public static function updateEmail($usr_id)
    {
        $stmt = "UPDATE
                    {{%user}}
                 SET
                    usr_email=?
                 WHERE
                    usr_id=?";
        try {
            DB_Helper::getInstance()->query($stmt, array($_POST["email"], $usr_id));
        } catch (DbException $e) {
            return -1;
        }

        Notification::notifyUserAccount($usr_id);

        return 1;
    }

    public static function updateFromPost()
    {
        $usr_id = $_POST['id'];
        $data = array(
            'grp_id'    =>  $_POST['grp_id'],
            'full_name' =>  $_POST['full_name'],
            'email'     =>  $_POST['email'],
            'password'  =>  $_POST['password'],
            'role'      =>  $_POST['role'],
        );

        if (isset($_POST['par_code'])) {
            $data['par_code'] = $_POST['par_code'];
        }

        return self::update($usr_id, $data);
    }

    /**
     * Method used to update the account details for a specific user.
     *
     * @param $usr_id
     * @param $data
     * @param bool $notify
     * @return  integer 1 if the update worked, -1 otherwise
     */
    public static function update($usr_id, $data, $notify = true)
    {
        // system account should not be updateable
        if ($usr_id == APP_SYSTEM_USER_ID) {
            return 1;
        }

        $stmt = "UPDATE
                    {{%user}}
                 SET
                    usr_full_name='" . Misc::escapeString($data["full_name"]) . "',
                    usr_email='"     . Misc::escapeString($data["email"])     . "'";
        if (isset($data["grp_id"])) {
            $group_id = !empty($data["grp_id"]) ? Misc::escapeInteger($data["grp_id"]) : 'NULL';
            $stmt .= ",
                    usr_grp_id='" . $group_id . "'";
        }
        if (!empty($data["password"])) {
            $stmt .= ",
                    usr_password='" . Auth::hashPassword($data["password"]) . "'";
        }
        if (isset($data["external_id"])) {
            $stmt .= ",
                    usr_external_id='" . Misc::escapeString($data["external_id"]) . "'";
        }
        if (isset($data["par_code"])) {
            $stmt .= ",
                    usr_par_code='" . Misc::escapeString($data["par_code"]) . "'";
        }
        $stmt .= "
                 WHERE
                    usr_id=" . Misc::escapeInteger($usr_id);
        try {
            DB_Helper::getInstance()->query($stmt);
        } catch (DbException $e) {
            return -1;
        }

        if (isset($data['role'])) {
            // update the project associations now
            $stmt = "DELETE FROM
                        {{%project_user}
                     WHERE
                        pru_usr_id=?";
            try {
                DB_Helper::getInstance()->query($stmt, array($usr_id));
            } catch (DbException $e) {
                return -1;
            }

            foreach ($data["role"] as $prj_id => $role) {
                if ($role < 1) {
                    continue;
                }
                $stmt = "INSERT INTO
                            {{%project_user}}
                         (
                            pru_prj_id,
                            pru_usr_id,
                            pru_role
                         ) VALUES (
                            ?, ?, ?
                         )";
                try {
                    DB_Helper::getInstance()->query(
                        $stmt, array(
                            $prj_id, $usr_id, $role
                        )
                    );
                } catch (DbException $e) {
                    return -1;
                }
            }
        }

        if ($notify == true) {
            if (!empty($data["password"])) {
                Notification::notifyUserPassword($usr_id, $data["password"]);
            } else {
                Notification::notifyUserAccount($usr_id);
            }
        }

        return 1;
    }

    public static function insertFromPost()
    {
        $user = array(
            'password'  =>  $_POST['password'],
            'full_name' =>  $_POST['full_name'],
            'email'     =>  $_POST['email'],
            'grp_id'    =>  $_POST['grp_id'],
            'role'      =>  $_POST['role'],
            'external_id'   =>  '',
        );

        if (isset($_POST['par_code'])) {
            $user['par_code'] = $_POST['par_code'];
        }

        $insert = self::insert($user);
        if ($insert != -1) {
            return 1;
        } else {
            return -1;
        }
    }

    /**
     * Method used to add a new user to the system.
     *
     * @param   array $user The array of user information
     * @return  integer 1 if the update worked, -1 otherwise
     */
    public static function insert($user)
    {
        $projects = array();
        foreach ($user["role"] as $prj_id => $role) {
            if ($role < 1) {
                continue;
            }
            $projects[] = $prj_id;
        }

        $params = array(
            isset($user['customer_id']) ? $user['customer_id'] : null,
            isset($user['contact_id']) ? $user['contact_id'] : null,
            Date_Helper::getCurrentDateGMT(),
            Auth::hashPassword($user['password']),
            $user['full_name'],
            $user['email'],
            $group_id, // FIXME $group_id is not defined
            $user['external_id'],
            isset($user['par_code']) ? $user['par_code'] : null,
        );
        $stmt = "INSERT INTO
                    {{%user}}
                 (
                    usr_customer_id,
                    usr_customer_contact_id,
                    usr_created_date,
                    usr_password,
                    usr_full_name,
                    usr_email,
                    usr_grp_id,
                    usr_external_id,
                    usr_par_code
                 ) VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                 )";
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DbException $e) {
            return -1;
        }

        $new_usr_id = DB_Helper::get_last_insert_id();
        // add the project associations!
        $projects = array();
        foreach ($user["role"] as $prj_id => $role) {
            if ($role < 1) {
                continue;
            }
            Project::associateUser($prj_id, $new_usr_id, $role);
            $projects[] = $prj_id;
        }

        Prefs::set($new_usr_id, Prefs::getDefaults($projects));

        // send email to user
        Notification::notifyNewUser($new_usr_id, $user["password"]);

        return $new_usr_id;
    }

    /**
     * Method used to get the list of users available in the system.
     *
     * @param   boolean $show_customers Whether to return customers or not
     * @return  array The list of users
     */
    public static function getList($show_customers)
    {
        $stmt = "SELECT
                    *
                 FROM
                    {{%user}}
                 WHERE
                    usr_id != " . APP_SYSTEM_USER_ID . "
                 ORDER BY
                    usr_status ASC,
                    usr_full_name ASC";
        try {
            $res = DB_Helper::getInstance()->getAll($stmt);
        } catch (DbException $e) {
            return "";
        }

        $data = array();
        $count = count($res);
        for ($i = 0; $i < $count; $i++) {
            $roles = Project::getAssocList($res[$i]['usr_id'], false, true);
            $role = current($roles);
            $role = $role['pru_role'];
            if (($show_customers == false) && (
                ((@$roles[Auth::getCurrentProject()]['pru_role']) == self::getRoleID("Customer")) ||
                ((count($roles) == 1) && ($role == self::getRoleID("Customer"))))) {
                continue;
            }
            $row = $res[$i];
            $row["roles"] = $roles;
            if (!empty($res[$i]["usr_grp_id"])) {
                $row["group_name"] = Group::getName($res[$i]["usr_grp_id"]);
            }
            if (!empty($res[$i]["usr_par_code"])) {
                $row["partner_name"] = Partner::getName($res[$i]["usr_par_code"]);
            }
            $data[] = $row;
        }

        return $data;
    }

    /**
     * Method used to get an associative array of the user's email address and
     * user ID.
     *
     * @return  array The list of users
     */
    public static function getAssocEmailList()
    {
        static $emails;

        if (!empty($emails)) {
            return $emails;
        }

        $stmt = "SELECT
                    LOWER(usr_email),
                    usr_id
                 FROM
                    {{%user}}";
        try {
            $res = DB_Helper::getInstance()->getAssoc($stmt);
        } catch (DbException $e) {
            return "";
        }

        $emails = $res;

        return $res;
    }

    /**
     * Method used to get an associative array of the user ID and
     * full name of the users available in the system.
     *
     * @return  array The list of users
     */
    public static function getAssocList()
    {
        $stmt = "SELECT
                    usr_id,
                    usr_full_name
                 FROM
                    {{%user}}
                 ORDER BY
                    usr_full_name ASC";
        try {
            $res = DB_Helper::getInstance()->getAssoc($stmt);
        } catch (DbException $e) {
            return "";
        }
        return $res;
    }

    /**
     * Method used to get the full name and email for the specified
     * user.
     *
     * @param   integer $usr_id The user ID
     * @return  array The email and full name
     */
    public static function getNameEmail($usr_id)
    {
        static $returns;

        if (!empty($returns[$usr_id])) {
            return $returns[$usr_id];
        }

        $stmt = "SELECT
                    usr_full_name,
                    usr_email
                 FROM
                    {{%user}}
                 WHERE
                    usr_id=?";
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, array($usr_id));
        } catch (DbException $e) {
            return "";
        }

        $returns[$usr_id] = $res;

        return $res;
    }

    /**
     * Method used to get the appropriate 'From' header for a
     * specified user.
     *
     * @param   integer $usr_id The user ID
     * @return  string The formatted 'From' header
     */
    public static function getFromHeader($usr_id)
    {
        $info = self::getNameEmail($usr_id);

        return $info["usr_full_name"] . " <" . $info["usr_email"] . ">";
    }

    /**
     * Returns the list of all users who are currently marked as
     * clocked-in.
     *
     * @return  array The list of clocked-in users
     */
    public static function getClockedInList()
    {
        $stmt = "SELECT
                    usr_full_name,
                    usr_email
                 FROM
                    {{%user}}
                 WHERE
                    usr_clocked_in=1";
        try {
            $res = DB_Helper::getInstance()->getAssoc($stmt);
        } catch (DbException $e) {
            return array();
        }

        return $res;
    }

    /**
     * Marks a user as clocked in.
     *
     * @param   int $usr_id The id of the user to clock out.
     * @return int
     */
    public static function clockIn($usr_id)
    {
        $stmt = "UPDATE
                    {{%user}}
                 SET
                    usr_clocked_in = 1
                 WHERE
                    usr_id = ?";
        try {
            DB_Helper::getInstance()->query($stmt, array($usr_id));
        } catch (DbException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Marks a user as clocked out.
     *
     * @param   integer $usr_id The id of the user to clock out.
     * @return int
     */
    public static function clockOut($usr_id)
    {
        $stmt = "UPDATE
                    {{%user}}
                 SET
                    usr_clocked_in = 0
                 WHERE
                    usr_id = ?";
        try {
            DB_Helper::getInstance()->query($stmt, array($usr_id));
        } catch (DbException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Returns true if a user is clocked in.
     *
     * @param   integer $usr_id The id of the user to clock out.
     * @return  boolean True if the user is logged in, false otherwise
     */
    public static function isClockedIn($usr_id)
    {
        $setup = Setup::load();
        // If clock in handling is disabled, say that we are always clocked in
        if ($setup['handle_clock_in'] == 'disabled') {
            return true;
        }
        $stmt = "SELECT
                    usr_clocked_in
                 FROM
                    {{%user}}
                 WHERE
                    usr_id = ?";
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($usr_id));
        } catch (DbException $e) {
            return -1;
        }

        if ($res == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Sets the group ID
     *
     * @param   integer $usr_id The id of the user.
     * @param   integer $grp_id The id of the group.
     * @return int
     */
    public static function setGroupID($usr_id, $grp_id)
    {
        if ($grp_id == false) {
            $grp_id = 'null';
        } else {
            $grp_id = Misc::escapeInteger($grp_id);
        }

        $stmt = "UPDATE
                    {{%user}}
                 SET
                    usr_grp_id = ?
                 WHERE
                    usr_id = ?";
        try {
            DB_Helper::getInstance()->query($stmt, array($grp_id, $usr_id));
        } catch (DbException $e) {
            return -1;
        }

        return 1;
    }

    public static function getLang($usr_id, $force_refresh = false)
    {
        static $returns;

        $usr_id = Misc::escapeInteger($usr_id);
        if (empty($returns[$usr_id]) || $force_refresh == true) {
            $sql = "SELECT
                        usr_lang
                    FROM
                        {{%user}}
                    WHERE
                        usr_id = ?";
            try {
                $res = DB_Helper::getInstance()->getOne($sql, array($usr_id));
            } catch (DbException $e) {
                return APP_DEFAULT_LOCALE;
            }

            if (empty($res)) {
                $res = APP_DEFAULT_LOCALE;
            }
            $returns[$usr_id] = $res;
        }

        return $returns[$usr_id];
    }

    public static function setLang($usr_id, $language)
    {
        $sql = "UPDATE
                    {{%user}}
                SET
                    usr_lang = ?
                WHERE
                    usr_id = ?";
        try {
            DB_Helper::getInstance()->query($sql, array($language, $usr_id));
        } catch (DbException $e) {
            return false;
        }

        return true;
    }

    public static function getAliases($usr_id)
    {
        $sql = "SELECT
                    ual_email
                FROM
                    {{%user_alias}}
                WHERE
                    ual_usr_id = ?";
        try {
            $res = DB_Helper::getInstance()->getColumn($sql, array($usr_id));
        } catch (DbException $e) {
            return array();
        }

        return $res;
    }

    public static function addAlias($usr_id, $email)
    {
        // see if alias belongs to a user right now
        $email_usr_id = self::getUserIDByEmail($email);
        if (!empty($email_usr_id)) {
            return false;
        }

        $existing_alias_usr_id = self::getUserIDByAlias($email);
        if (!empty($existing_alias_usr_id)) {
            return false;
        }

        $sql = "INSERT INTO
                    {{%user_alias}}
                SET
                    ual_usr_id = ?,
                    ual_email = ?";

        try {
            $res = DB_Helper::getInstance()->query($sql, array($usr_id, $email));
        } catch (DbException $e) {
            return false;
        }

        return true;
    }

    public static function removeAlias($usr_id, $email)
    {
        $sql = "DELETE FROM
                    {{%user_alias}}
                WHERE
                    ual_usr_id = ? AND
                    ual_email = ?";
        try {
            DB_Helper::getInstance()->query($sql, array($usr_id, $email));
        } catch (DbException $e) {
            return false;
        }

        return true;
    }

    public static function getUserIDByAlias($email)
    {
        $sql = "SELECT
                    ual_usr_id
                FROM
                    {{%user_alias}}
                WHERE
                    ual_email = ?";
        try {
            $res = DB_Helper::getInstance()->getOne($sql, array($email));
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }

    public static function isPartner($usr_id)
    {
        $sql = "SELECT
                    usr_par_code
                FROM
                    {{%user}}
                WHERE
                    usr_id = ?";
        try {
            $res = DB_Helper::getInstance()->getOne($sql, array($usr_id));
        } catch (DbException $e) {
            return false;
        }

        return !empty($res);
    }

    public static function getPartnerID($usr_id)
    {
        $sql = "SELECT
                    usr_par_code
                FROM
                    {{%user}}
                WHERE
                    usr_id = ?";
        try {
            $res = DB_Helper::getInstance()->getOne($sql, array($usr_id));
        } catch (DbException $e) {
            return false;
        }

        return $res;
    }

    public static function getExternalID($usr_id)
    {
        $details = User::getDetails($usr_id);

        return $details['usr_external_id'];
    }

    public static function unlock($usr_id)
    {
        $stmt = "UPDATE
                    {{%user}}
                 SET
                    usr_failed_logins = 0
                 WHERE
                    usr_id=?";
        try {
            DB_Helper::getInstance()->query($stmt, array($usr_id));
        } catch (DbException $e) {
            return false;
        }

        return true;
    }
}
