<?php $this->renderFeedbackMessages(); ?>

<style>
    .board-header {
        display: flex; align-items: center; gap: 15px;
        margin-bottom: 20px; flex-wrap: wrap;
    }
    .board-header h2 { margin: 0; }
    .board-header .actions a {
        font-size: 0.85em; padding: 5px 12px; margin-left: 6px;
        border: 1px solid #ccc; border-radius: 3px;
        text-decoration: none; color: #333; background: #f5f5f5;
    }
    .board-header .actions a:hover { background: #e8e8e8; }
    .board-header .actions a.primary { background: #0074d9; color: #fff; border-color: #0074d9; }
    .board-header .actions a.primary:hover { background: #005fa3; }
    .board-header .actions a.danger { color: #c00; border-color: #c00; }

    /* Kanban columns */
    .kanban-board {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        align-items: start;
    }
    .kanban-col {
        background: #f4f5f7;
        border-radius: 6px;
        padding: 12px;
        min-height: 300px;
    }
    .kanban-col-header {
        font-weight: bold; font-size: 0.95em;
        padding: 6px 4px 10px;
        display: flex; justify-content: space-between; align-items: center;
        border-bottom: 2px solid #ddd; margin-bottom: 10px;
    }
    .col-todo       .kanban-col-header { border-color: #aaa; }
    .col-inprogress .kanban-col-header { border-color: #0074d9; }
    .col-done       .kanban-col-header { border-color: #2ecc40; }

    .task-count {
        background: #ddd; color: #555;
        font-size: 0.78em; padding: 2px 7px; border-radius: 10px;
    }
    .col-inprogress .task-count { background: #d6eaff; color: #0074d9; }
    .col-done       .task-count { background: #d4f5d4; color: #27ae60; }

    /* Task cards */
    .task-card {
        background: #fff;
        border: 1px solid #ddd;
        border-left: 3px solid #ddd;
        border-radius: 4px;
        padding: 10px 12px;
        margin-bottom: 8px;
        cursor: grab;
        box-shadow: 0 1px 2px rgba(0,0,0,0.06);
        transition: box-shadow 0.15s;
    }
    .task-card:hover { box-shadow: 0 3px 8px rgba(0,0,0,0.12); }
    .task-card.priority-low    { border-left-color: #2ecc40; }
    .task-card.priority-medium { border-left-color: #f39c12; }
    .task-card.priority-high   { border-left-color: #e74c3c; }
    .task-card.overdue         { border-left-color: #e74c3c !important; }

    .task-card h4 { margin: 0 0 5px; font-size: 0.92em; }
    .task-card h4 a { text-decoration: none; color: #333; }
    .task-card h4 a:hover { color: #0074d9; }
    .task-card .task-meta {
        font-size: 0.78em; color: #999;
        display: flex; align-items: center; gap: 5px; flex-wrap: wrap;
    }
    .task-card .task-meta .assigned       { color: #0074d9; }
    .task-card .task-meta .deadline-ok    { color: #27ae60; }
    .task-card .task-meta .deadline-over  { color: #e74c3c; font-weight: bold; }

    .priority-badge {
        font-size: 0.72em; padding: 1px 6px; border-radius: 8px;
        font-weight: bold; text-transform: uppercase; letter-spacing: 0.03em;
    }
    .priority-badge.low    { background: #d4f5d4; color: #27ae60; }
    .priority-badge.medium { background: #fef3cd; color: #d68910; }
    .priority-badge.high   { background: #fde8e8; color: #c0392b; }

    .add-task-link {
        display: block; text-align: center; margin-top: 8px;
        padding: 6px; border: 1px dashed #bbb; border-radius: 4px;
        color: #888; text-decoration: none; font-size: 0.85em;
    }
    .add-task-link:hover { background: #eee; color: #333; }

    /* Members panel */
    .members-panel {
        margin-top: 30px; padding: 15px;
        border: 1px solid #ddd; border-radius: 6px; background: #fafafa;
        overflow: visible;
    }
    .members-panel h3 { margin: 0 0 12px; font-size: 1em; }
    .member-list { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
    .member-chip {
        display: inline-flex; align-items: center; gap: 5px;
        background: #e8f4fd; border-radius: 20px;
        padding: 4px 10px; font-size: 0.85em;
    }
    .member-chip .role { color: #888; font-size: 0.8em; }
    .member-chip a { color: #c00; text-decoration: none; font-size: 0.8em; }

    .invite-form { display: flex; gap: 8px; align-items: center; }
    .invite-form button {
        padding: 6px 16px; background: #0074d9; color: #fff;
        border: none; border-radius: 3px; cursor: pointer; white-space: nowrap;
    }
    .invite-form button:hover { background: #005fa3; }

    /* Autocomplete */
    .ac-wrap { position: relative; flex: 1; min-width: 220px; }
    .ac-wrap input[type=text] {
        width: 100%; padding: 6px 10px; border: 1px solid #ccc;
        border-radius: 3px; font-size: 0.9em; box-sizing: border-box;
    }
    .ac-wrap input[type=text]:focus { outline: none; border-color: #0074d9; }
    .ac-dropdown {
        display: none; position: fixed;
        background: #fff; border: 1px solid #ccc;
        border-radius: 3px; z-index: 99999;
        max-height: 200px; overflow-y: auto;
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        min-width: 220px;
    }
    .ac-item {
        padding: 7px 10px; cursor: pointer; font-size: 0.9em;
    }
    .ac-item:hover { background: #e8f4fd; color: #0074d9; }

    /* Drag & Drop */
    .sortable-ghost { opacity: 0.4; }
    .sortable-drag  { box-shadow: 0 4px 14px rgba(0,0,0,0.2); }
</style>

<div class="container">

    <!-- Board header -->
    <div class="board-header">
        <div>
            <a href="<?= Config::get('URL'); ?>project" style="font-size:0.85em; color:#888; text-decoration:none;">
                ← Projekte
            </a>
            <h2 style="margin-top:4px;"><?= htmlspecialchars($this->project->name); ?></h2>
            <?php if ($this->project->description): ?>
                <p style="margin:2px 0 0; color:#666; font-size:0.9em;">
                    <?= htmlspecialchars($this->project->description); ?>
                </p>
            <?php endif; ?>
        </div>
        <div class="actions" style="margin-left:auto;">
            <a href="<?= Config::get('URL'); ?>task/create/<?= $this->project->id; ?>" class="primary">+ Task</a>
            <?php if ($this->is_owner): ?>
                <a href="<?= Config::get('URL'); ?>project/edit/<?= $this->project->id; ?>">Bearbeiten</a>
                <a href="<?= Config::get('URL'); ?>project/delete/<?= $this->project->id; ?>"
                   class="danger"
                   onclick="return confirm('Projekt wirklich löschen?');">Löschen</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Kanban Board -->
    <div class="kanban-board">
        <?php
        $columns = array(
            'todo'       => 'To Do',
            'inprogress' => 'In Progress',
            'done'       => 'Done'
        );
        $today = date('Y-m-d');

        foreach ($columns as $status => $label):
            $colTasks = $this->tasks[$status] ?? array();
        ?>
        <div class="kanban-col col-<?= $status; ?>" id="col-<?= $status; ?>" data-status="<?= $status; ?>">
            <div class="kanban-col-header">
                <span><?= $label; ?></span>
                <span class="task-count"><?= count($colTasks); ?></span>
            </div>

            <div class="task-list" id="list-<?= $status; ?>">
                <?php foreach ($colTasks as $task):
                    $overdue  = ($task->deadline && $task->deadline < $today && $status !== 'done');
                    $priority = $task->priority ?? 'medium';
                    $cls      = 'task-card priority-' . $priority . ($overdue ? ' overdue' : '');
                ?>
                <div class="<?= $cls; ?>" data-task-id="<?= $task->id; ?>">
                    <h4>
                        <a href="<?= Config::get('URL'); ?>task/edit/<?= $task->id; ?>">
                            <?= htmlspecialchars($task->title); ?>
                        </a>
                    </h4>
                    <div class="task-meta">
                        <span class="priority-badge <?= $priority; ?>">
                            <?= $priority === 'low' ? 'Low' : ($priority === 'high' ? 'High' : 'Med'); ?>
                        </span>
                        <?php if ($task->assigned_name): ?>
                            <span class="assigned">@<?= htmlspecialchars($task->assigned_name); ?></span>
                        <?php endif; ?>
                        <?php if ($task->deadline): ?>
                            &middot;
                            <span class="<?= $overdue ? 'deadline-over' : 'deadline-ok'; ?>">
                                <?= date('d.m.Y', strtotime($task->deadline)); ?>
                                <?= $overdue ? ' ⚠' : ''; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <a href="<?= Config::get('URL'); ?>task/create/<?= $this->project->id; ?>?status=<?= $status; ?>"
               class="add-task-link">+ Task hinzufügen</a>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Members panel -->
    <div class="members-panel">
        <h3>Mitglieder</h3>
        <div class="member-list">
            <?php foreach ($this->members as $m): ?>
                <div class="member-chip">
                    <span><?= htmlspecialchars($m->user_name); ?></span>
                    <span class="role">(<?= $m->role; ?>)</span>
                    <?php if ($this->is_owner && $m->role !== 'owner'): ?>
                        <a href="<?= Config::get('URL'); ?>project/removeMember/<?= $this->project->id; ?>/<?= $m->user_id; ?>"
                           onclick="return confirm('Mitglied entfernen?');">✕</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if ($this->is_owner): ?>
            <form class="invite-form"
                  action="<?= Config::get('URL'); ?>project/addMember/<?= $this->project->id; ?>"
                  method="post"
                  onsubmit="return validateInvite();">
                <input type="hidden" name="username" id="invite-username" value="" />
                <div class="ac-wrap">
                    <input type="text" id="invite-input" autocomplete="off"
                           placeholder="Username suchen …" />
                    <div id="invite-dropdown" class="ac-dropdown"></div>
                </div>
                <button type="submit">Einladen</button>
            </form>
        <?php endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(function () {
    var baseUrl = '<?= Config::get('URL'); ?>';

    /* ── Drag & Drop ── */
    document.querySelectorAll('.task-list').forEach(function (list) {
        Sortable.create(list, {
            group: 'tasks',
            animation: 150,
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            onEnd: function (evt) {
                var taskId    = evt.item.dataset.taskId;
                var newStatus = evt.to.closest('.kanban-col').dataset.status;
                document.querySelectorAll('.kanban-col').forEach(function (col) {
                    col.querySelector('.task-count').textContent =
                        col.querySelector('.task-list').children.length;
                });
                var fd = new FormData();
                fd.append('status', newStatus);
                fetch(baseUrl + 'task/changeStatus/' + taskId, { method: 'POST', body: fd });
            }
        });
    });

    /* ── Invite autocomplete (vanilla JS + fetch) ── */
    <?php if ($this->is_owner): ?>
    var projectId  = <?= (int)$this->project->id; ?>;
    var invInput   = document.getElementById('invite-input');
    var invHidden  = document.getElementById('invite-username');
    var acTimer;

    /* Move dropdown to body so no parent can clip it */
    var invDrop = document.getElementById('invite-dropdown');
    document.body.appendChild(invDrop);

    function positionDrop() {
        var rect = invInput.getBoundingClientRect();
        invDrop.style.top   = rect.bottom + 'px';
        invDrop.style.left  = rect.left   + 'px';
        invDrop.style.width = rect.width  + 'px';
    }

    invInput.addEventListener('input', function () {
        clearTimeout(acTimer);
        invHidden.value = '';
        var q = invInput.value.trim();
        if (q.length < 1) { closeDrop(); return; }
        acTimer = setTimeout(function () {
            fetch(baseUrl + 'project/searchUsers/' + projectId + '?q=' + encodeURIComponent(q))
                .then(function (r) { return r.json(); })
                .then(function (results) {
                    invDrop.innerHTML = '';
                    if (!results.length) { closeDrop(); return; }
                    results.forEach(function (u) {
                        var item = document.createElement('div');
                        item.className = 'ac-item';
                        item.textContent = u.text;
                        item.addEventListener('mousedown', function (e) {
                            e.preventDefault();
                            invInput.value  = u.text;
                            invHidden.value = u.text;
                            closeDrop();
                        });
                        invDrop.appendChild(item);
                    });
                    positionDrop();
                    invDrop.style.display = 'block';
                })
                .catch(function () { closeDrop(); });
        }, 250);
    });

    invInput.addEventListener('blur', function () { setTimeout(closeDrop, 150); });

    function closeDrop() { invDrop.style.display = 'none'; }

    function validateInvite() {
        if (!invHidden.value) {
            alert('Bitte einen User aus der Liste auswählen.');
            return false;
        }
        return true;
    }
    window.validateInvite = validateInvite;
    <?php endif; ?>
}());
</script>
