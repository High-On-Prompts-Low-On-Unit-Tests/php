<?php $this->renderFeedbackMessages(); ?>

<style>
    .task-edit-layout {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 24px;
        align-items: start;
        margin: 24px 0;
    }
    @media (max-width: 800px) {
        .task-edit-layout { grid-template-columns: 1fr; }
    }
    .form-card {
        background: #fff; border: 1px solid #ddd;
        border-radius: 6px; padding: 24px 28px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.07);
    }
    .form-card h2 { margin: 0 0 4px; font-size: 1.2em; }
    .form-card .project-label { color: #888; font-size: 0.85em; margin-bottom: 18px; }
    .form-group { margin-bottom: 14px; }
    .form-group label { display: block; font-size: 0.87em; font-weight: bold; margin-bottom: 4px; color: #444; }
    .form-group input[type=text],
    .form-group input[type=date],
    .form-group select,
    .form-group textarea {
        width: 100%; padding: 7px 9px; border: 1px solid #ccc;
        border-radius: 4px; font-size: 0.93em; box-sizing: border-box;
        background: #fff;
    }
    .form-group textarea { height: 85px; resize: vertical; }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
        outline: none; border-color: #0074d9;
    }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .form-actions { display: flex; gap: 10px; align-items: center; margin-top: 18px; flex-wrap: wrap; }
    .btn-primary {
        padding: 7px 18px; background: #0074d9; color: #fff;
        border: none; border-radius: 4px; cursor: pointer; font-size: 0.92em;
    }
    .btn-primary:hover { background: #005fa3; }
    .btn-cancel {
        padding: 7px 14px; background: #f5f5f5; color: #333;
        border: 1px solid #ccc; border-radius: 4px; text-decoration: none; font-size: 0.92em;
    }
    .btn-cancel:hover { background: #e8e8e8; }
    .btn-delete {
        padding: 7px 14px; background: #fff; color: #c00;
        border: 1px solid #c00; border-radius: 4px; text-decoration: none;
        font-size: 0.88em; margin-left: auto;
    }
    .btn-delete:hover { background: #fee; }

    /* Comments panel */
    .comments-panel h3 { margin: 0 0 14px; font-size: 1em; }
    .comment-list { list-style: none; margin: 0 0 16px; padding: 0; }
    .comment-list li {
        padding: 10px 12px; background: #f9f9f9;
        border: 1px solid #eee; border-radius: 4px; margin-bottom: 8px;
        font-size: 0.88em;
    }
    .comment-list li .comment-author {
        font-weight: bold; color: #0074d9; margin-bottom: 3px;
    }
    .comment-list li .comment-date { color: #bbb; font-size: 0.85em; margin-left: 6px; }
    .comment-list li .comment-text { color: #333; white-space: pre-wrap; }
    .comment-form textarea {
        width: 100%; padding: 8px 10px; border: 1px solid #ccc;
        border-radius: 4px; font-size: 0.9em; box-sizing: border-box;
        height: 70px; resize: vertical; margin-bottom: 8px;
    }
    .comment-form textarea:focus { outline: none; border-color: #0074d9; }
    .comment-form button {
        padding: 6px 16px; background: #0074d9; color: #fff;
        border: none; border-radius: 4px; cursor: pointer; font-size: 0.88em;
    }
    .comment-form button:hover { background: #005fa3; }
    .no-comments { color: #aaa; font-size: 0.88em; font-style: italic; margin-bottom: 12px; }
</style>

<div class="container">
    <p style="margin-bottom:6px;">
        <a href="<?= Config::get('URL'); ?>project/board/<?= $this->project->id; ?>"
           style="font-size:0.85em; color:#888; text-decoration:none;">
            ← <?= htmlspecialchars($this->project->name); ?>
        </a>
    </p>

    <div class="task-edit-layout">

        <!-- Left: Edit form -->
        <div class="form-card">
            <h2>Task bearbeiten</h2>
            <div class="project-label">Projekt: <?= htmlspecialchars($this->project->name); ?></div>

            <form method="post" action="<?= Config::get('URL'); ?>task/edit/<?= $this->task->id; ?>">
                <div class="form-group">
                    <label for="title">Titel *</label>
                    <input type="text" id="title" name="title" required
                           value="<?= htmlspecialchars($_POST['title'] ?? $this->task->title); ?>" />
                </div>
                <div class="form-group">
                    <label for="description">Beschreibung</label>
                    <textarea id="description" name="description"><?= htmlspecialchars($_POST['description'] ?? $this->task->description ?? ''); ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <?php
                            $statuses = array('todo' => 'To Do', 'inprogress' => 'In Progress', 'done' => 'Done');
                            $curStatus = $_POST['status'] ?? $this->task->status;
                            foreach ($statuses as $val => $label):
                            ?>
                                <option value="<?= $val; ?>" <?= ($curStatus === $val ? 'selected' : ''); ?>>
                                    <?= $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="deadline">Deadline</label>
                        <input type="date" id="deadline" name="deadline"
                               value="<?= htmlspecialchars($_POST['deadline'] ?? $this->task->deadline ?? ''); ?>" />
                    </div>
                </div>
                <div class="form-group">
                    <label for="assigned_to">Zuweisen an</label>
                    <select id="assigned_to" name="assigned_to">
                        <option value="">— niemanden —</option>
                        <?php
                        $curAssigned = $_POST['assigned_to'] ?? $this->task->assigned_to;
                        foreach ($this->members as $m):
                        ?>
                            <option value="<?= $m->user_id; ?>"
                                <?= ($curAssigned == $m->user_id ? 'selected' : ''); ?>>
                                <?= htmlspecialchars($m->user_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" name="submit" value="1" class="btn-primary">Speichern</button>
                    <a href="<?= Config::get('URL'); ?>project/board/<?= $this->project->id; ?>" class="btn-cancel">Abbrechen</a>
                    <a href="<?= Config::get('URL'); ?>task/delete/<?= $this->task->id; ?>"
                       class="btn-delete"
                       onclick="return confirm('Task wirklich löschen?');">Löschen</a>
                </div>
            </form>
        </div>

        <!-- Right: Comments -->
        <div class="form-card comments-panel">
            <h3>Kommentare (<?= count($this->comments); ?>)</h3>

            <?php if (empty($this->comments)): ?>
                <p class="no-comments">Noch keine Kommentare.</p>
            <?php else: ?>
                <ul class="comment-list">
                    <?php foreach ($this->comments as $c): ?>
                        <li>
                            <div class="comment-author">
                                <?= htmlspecialchars($c->user_name); ?>
                                <span class="comment-date">
                                    <?= date('d.m.Y H:i', strtotime($c->created_at)); ?>
                                </span>
                            </div>
                            <div class="comment-text"><?= htmlspecialchars($c->comment_text); ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <form class="comment-form"
                  method="post"
                  action="<?= Config::get('URL'); ?>task/addComment/<?= $this->task->id; ?>">
                <textarea name="comment_text" placeholder="Kommentar schreiben …" required></textarea>
                <button type="submit">Kommentieren</button>
            </form>
        </div>

    </div>
</div>
