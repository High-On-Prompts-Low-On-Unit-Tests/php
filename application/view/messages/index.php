<div class="container messages-page">
    <h1>Messages</h1>
    <div class="box">
        <?php $this->renderFeedbackMessages(); ?>

        <div class="messages-layout">
            <!-- Left sidebar: chats list -->
            <div class="messages-list">
                <h3>Chats</h3>

                <!-- New group button -->
                <button class="btn-new-group" onclick="document.getElementById('new-group-modal').style.display='flex'">+ New Group</button>

                <!-- Start direct chat -->
                <details class="dm-dropdown">
                    <summary>+ Direct Message</summary>
                    <ul>
                        <?php foreach ($this->all_users as $u) { ?>
                            <li>
                                <a href="<?= Config::get('URL') . 'messages/startDirect/' . $u->user_id; ?>">
                                    <?= htmlentities($u->user_name); ?>
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                </details>

                <?php if (!empty($this->groups)) { ?>
                    <ul>
                        <?php foreach ($this->groups as $g) { ?>
                            <?php $n = isset($this->unread[$g->id]) ? $this->unread[$g->id] : 0; ?>
                            <li class="<?= $n ? 'has-unread' : ''; ?> <?= $this->active_group_id == $g->id ? 'active-partner' : ''; ?>">
                                <a href="<?= Config::get('URL') . 'messages/index?group=' . $g->id; ?>">
                                    <?php if ($g->type === 'group') { ?>
                                        <span class="group-icon">&#128101;</span>
                                    <?php } ?>
                                    <?= htmlentities($g->display_name ?: 'Unnamed Group'); ?>
                                    <?php if ($n) echo '<span class="badge">' . $n . '</span>'; ?>
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } else { ?>
                    <div class="empty-hint">No chats yet. Start a conversation!</div>
                <?php } ?>
            </div>

            <!-- Right side: active chat -->
            <div style="flex:1;">
                <?php if ($this->active_group) { ?>
                    <?php
                        $group = $this->active_group;
                        $is_group = $group->type === 'group';
                        $me = Session::get('user_id');
                    ?>

                    <!-- Chat header -->
                    <div class="chat-header">
                        <h3>
                            <?php if ($is_group) { ?>
                                <span class="group-icon">&#128101;</span>
                            <?php } ?>
                            <?= htmlentities($group->name ?: $this->groups[array_search($this->active_group_id, array_column($this->groups, 'id'))]->display_name ?? 'Chat'); ?>
                        </h3>

                        <?php if ($is_group) { ?>
                            <div class="chat-actions">
                                <details class="action-menu">
                                    <summary>&#9881; Manage</summary>
                                    <div class="action-dropdown">
                                        <!-- Members list -->
                                        <div class="members-section">
                                            <strong>Members (<?= count($this->members); ?>)</strong>
                                            <ul>
                                                <?php foreach ($this->members as $m) { ?>
                                                    <li>
                                                        <?= htmlentities($m->user_name); ?>
                                                        <?php if ($m->role === 'admin') echo '<span class="role-tag">Admin</span>'; ?>
                                                        <?php if ($this->is_admin && $m->user_id != $me) { ?>
                                                            <form method="post" action="<?= Config::get('URL'); ?>messages/removeMember" style="display:inline;">
                                                                <input type="hidden" name="group_id" value="<?= $group->id; ?>" />
                                                                <input type="hidden" name="user_id" value="<?= $m->user_id; ?>" />
                                                                <button type="submit" class="btn-remove" onclick="return confirm('Remove <?= htmlentities($m->user_name); ?>?')">&#10005;</button>
                                                            </form>
                                                        <?php } ?>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </div>

                                        <!-- Add member (admin only) -->
                                        <?php if ($this->is_admin) { ?>
                                            <form method="post" action="<?= Config::get('URL'); ?>messages/addMember" class="add-member-form">
                                                <input type="hidden" name="group_id" value="<?= $group->id; ?>" />
                                                <select name="user_id">
                                                    <option value="">Add member...</option>
                                                    <?php
                                                        $member_ids = array_column($this->members, 'user_id');
                                                        foreach ($this->all_users as $u) {
                                                            if (!in_array($u->user_id, $member_ids)) {
                                                                echo '<option value="' . $u->user_id . '">' . htmlentities($u->user_name) . '</option>';
                                                            }
                                                        }
                                                    ?>
                                                </select>
                                                <button type="submit">Add</button>
                                            </form>

                                            <!-- Rename -->
                                            <form method="post" action="<?= Config::get('URL'); ?>messages/renameGroup" class="rename-form">
                                                <input type="hidden" name="group_id" value="<?= $group->id; ?>" />
                                                <input type="text" name="group_name" placeholder="New name" value="<?= htmlentities($group->name); ?>" />
                                                <button type="submit">Rename</button>
                                            </form>
                                        <?php } ?>

                                        <!-- Leave group -->
                                        <form method="post" action="<?= Config::get('URL'); ?>messages/leaveGroup">
                                            <input type="hidden" name="group_id" value="<?= $group->id; ?>" />
                                            <button type="submit" class="btn-leave" onclick="return confirm('Leave this group?')">Leave Group</button>
                                        </form>
                                    </div>
                                </details>
                            </div>
                        <?php } ?>
                    </div>

                    <!-- Messages -->
                    <?php if (!empty($this->messages)) { ?>
                        <?php $msgs = $this->messages; $count = count($msgs); ?>
                        <section class="discussion">
                            <?php for ($i = 0; $i < $count; $i++) { ?>
                                <?php
                                    $m = $msgs[$i];
                                    $prev = $i > 0 ? $msgs[$i - 1] : null;
                                    $next = $i < $count - 1 ? $msgs[$i + 1] : null;
                                    $is_me = $m->sender_id == $me;
                                    $side = $is_me ? 'sender' : 'recipient';
                                    $prev_same = $prev && $prev->sender_id == $m->sender_id;
                                    $next_same = $next && $next->sender_id == $m->sender_id;
                                    if (!$prev_same && !$next_same) {
                                        $pos = 'first last';
                                    } elseif ($prev_same && $next_same) {
                                        $pos = 'middle';
                                    } elseif ($prev_same) {
                                        $pos = 'last';
                                    } else {
                                        $pos = 'first';
                                    }
                                ?>
                                <?php if (!$is_me && !$prev_same && $is_group) { ?>
                                    <div class="sender-label"><?= htmlentities($m->sender_name); ?></div>
                                <?php } ?>
                                <div class="bubble <?= $side; ?> <?= $pos; ?>">
                                    <?= nl2br(htmlentities($m->message_text)); ?>
                                </div>
                            <?php } ?>
                        </section>
                    <?php } else { ?>
                        <div class="empty-hint">No messages yet. Say something!</div>
                    <?php } ?>

                    <!-- Send form -->
                    <form method="post" action="<?= Config::get('URL'); ?>messages/send" class="send-form">
                        <input type="hidden" name="group_id" value="<?= $this->active_group_id; ?>" />
                        <input type="text" name="text" placeholder="Type a message..." autocomplete="off" />
                        <button type="submit">Send</button>
                    </form>

                <?php } else { ?>
                    <div class="empty-hint">Select a chat or start a new conversation.</div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<!-- New Group Modal -->
<div id="new-group-modal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <h3>Create Group</h3>
        <form method="post" action="<?= Config::get('URL'); ?>messages/createGroup">
            <label>Group Name</label>
            <input type="text" name="group_name" required placeholder="Enter group name" />

            <label>Select Members</label>
            <div class="member-checkboxes">
                <?php foreach ($this->all_users as $u) { ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="members[]" value="<?= $u->user_id; ?>" />
                        <?= htmlentities($u->user_name); ?>
                    </label>
                <?php } ?>
            </div>

            <div class="modal-actions">
                <button type="submit">Create</button>
                <button type="button" onclick="document.getElementById('new-group-modal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>
