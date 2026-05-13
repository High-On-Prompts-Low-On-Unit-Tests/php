<div class="container">
    <h1>Admin - User Management</h1>

    <div class="box">

        <?php $this->renderFeedbackMessages(); ?>

        <div>
            <table id="admin-user-table" class="overview-table">
                <thead>
                <tr>
                    <th>Id</th>
                    <th>Avatar</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Group</th>
                    <th>Active</th>
                    <th>Suspended</th>
                    <th>Profile</th>
                    <th>Suspension (days)</th>
                    <th>Soft delete</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($this->users as $user) { ?>
                    <tr class="<?= ($user->user_active == 0 ? 'inactive' : 'active'); ?>">
                        <td><?= $user->user_id; ?></td>
                        <td class="avatar">
                            <?php if (isset($user->user_avatar_link)) { ?>
                                <img src="<?= $user->user_avatar_link; ?>"/>
                            <?php } ?>
                        </td>
                        <td><?= $user->user_name; ?></td>
                        <td><?= $user->user_email; ?></td>
                        <td>
                            <form action="<?= Config::get("URL"); ?>admin/actionAccountSettings" method="post">
                                <select name="user_account_type">
                                    <?php foreach ($this->groups as $group) { ?>
                                        <option value="<?= $group->id; ?>"
                                            <?= ($user->user_account_type == $group->id ? 'selected' : ''); ?>>
                                            <?= $group->group_name; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                        </td>
                        <td><?= ($user->user_active == 0 ? 'No' : 'Yes'); ?></td>
                        <td>
                            <?php if ($user->user_suspension_timestamp != null && $user->user_suspension_timestamp > time()) {
                                $days_left = ceil(($user->user_suspension_timestamp - time()) / 86400);
                                echo $days_left . ' day(s) left';
                            } else {
                                echo 'No';
                            } ?>
                        </td>
                        <td>
                            <a href="<?= Config::get('URL') . 'profile/showProfile/' . $user->user_id; ?>">Profile</a>
                        </td>
                            <td><input type="number" name="suspension" min="0" /></td>
                            <td><input type="checkbox" name="softDelete" <?php if ($user->user_deleted) { ?> checked <?php } ?> /></td>
                            <td>
                                <input type="hidden" name="user_id" value="<?= $user->user_id; ?>" />
                                <input type="submit" value="Save" />
                            </td>
                            </form>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
