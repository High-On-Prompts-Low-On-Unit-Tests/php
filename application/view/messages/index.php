<div class="container messages-page">
    <h1>Messages</h1>
    <div class="box">
        <?php $this->renderFeedbackMessages(); ?>

        <div class="messages-layout">
            <div class="messages-list">
                <h3>People</h3>
                <?php if (!empty($this->users)) { ?>
                    <ul>
                        <?php foreach ($this->users as $u) { ?>
                            <?php $n = isset($this->unread[$u->user_id]) ? $this->unread[$u->user_id] : 0; ?>
                            <li class="<?= $n ? 'has-unread' : ''; ?> <?= $this->active_partner_id == $u->user_id ? 'active-partner' : ''; ?>">
                                <a href="<?= Config::get('URL') . 'messages/index?partner=' . $u->user_id; ?>">
                                    <?= htmlentities($u->user_name); ?>
                                    <?php if ($n) echo '<span class="badge">' . $n . '</span>'; ?>
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                <?php } else { ?>
                    <div>No users yet.</div>
                <?php } ?>
            </div>

            <div style="flex:1;">
                <?php if ($this->active_partner_id) { ?>
                    <h3>Chat</h3>
                    <?php if (!empty($this->messages)) { ?>
                        <?php $msgs = $this->messages; $count = count($msgs); ?>
                        <section class="discussion">
                            <?php for ($i = 0; $i < $count; $i++) { ?>
                                <?php
                                    $m = $msgs[$i];
                                    $prev = $i > 0 ? $msgs[$i - 1] : null;
                                    $next = $i < $count - 1 ? $msgs[$i + 1] : null;
                                    $is_me = $m->sender_id == Session::get('user_id');
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
                                <div class="bubble <?= $side; ?> <?= $pos; ?>">
                                    <?= nl2br(htmlentities($m->message_text)); ?>
                                </div>
                            <?php } ?>
                        </section>
                    <?php } else { ?>
                        <div>No messages yet.</div>
                    <?php } ?>

                    <form method="get" action="<?php echo Config::get('URL');?>messages/send" style="margin-top:10px;">
                        <input type="hidden" name="to" value="<?php echo $this->active_partner_id; ?>" />
                        <input type="text" name="text" placeholder="Say something" style="width:70%;" />
                        <input type="submit" value="Send" />
                    </form>
                <?php } else { ?>
                    <div>Pick a person to start chatting.</div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
