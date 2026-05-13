<div class="container">
    <h1>Users</h1>

    <div class="box">
        <div>
            <table id="users-table" class="overview-table">
                <thead>
                <tr>
                    <th>Id</th>
                    <th>Avatar</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Group</th>
                    <th>Active</th>
                    <th>Profile</th>
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
                        <td><?= $user->group_name; ?></td>
                        <td><?= ($user->user_active == 0 ? 'No' : 'Yes'); ?></td>
                        <td>
                            <a href="<?= Config::get('URL') . 'profile/showProfile/' . $user->user_id; ?>">Profile</a>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
