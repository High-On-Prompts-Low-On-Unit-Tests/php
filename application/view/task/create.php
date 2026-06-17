<?php $this->renderFeedbackMessages(); ?>

<style>
    .form-card {
        max-width: 560px; margin: 30px auto;
        background: #fff; border: 1px solid #ddd;
        border-radius: 6px; padding: 28px 32px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.07);
    }
    .form-card h2 { margin: 0 0 4px; font-size: 1.3em; }
    .form-card .project-label { color: #888; font-size: 0.88em; margin-bottom: 20px; }
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 0.88em; font-weight: bold; margin-bottom: 5px; color: #444; }
    .form-group input[type=text],
    .form-group input[type=date],
    .form-group select,
    .form-group textarea {
        width: 100%; padding: 8px 10px; border: 1px solid #ccc;
        border-radius: 4px; font-size: 0.95em; box-sizing: border-box;
        background: #fff;
    }
    .form-group textarea { height: 90px; resize: vertical; }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
        outline: none; border-color: #0074d9;
    }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .form-actions { display: flex; gap: 10px; margin-top: 20px; }
    .btn-primary {
        padding: 8px 20px; background: #0074d9; color: #fff;
        border: none; border-radius: 4px; cursor: pointer; font-size: 0.95em;
    }
    .btn-primary:hover { background: #005fa3; }
    .btn-cancel {
        padding: 8px 16px; background: #f5f5f5; color: #333;
        border: 1px solid #ccc; border-radius: 4px; text-decoration: none;
        font-size: 0.95em;
    }
    .btn-cancel:hover { background: #e8e8e8; }
</style>

<div class="container">
    <div class="form-card">
        <h2>Neuer Task</h2>
        <div class="project-label">Projekt: <?= htmlspecialchars($this->project->name); ?></div>

        <form method="post" action="<?= Config::get('URL'); ?>task/create/<?= $this->project->id; ?>">
            <div class="form-group">
                <label for="title">Titel *</label>
                <input type="text" id="title" name="title" required
                       placeholder="Was soll erledigt werden?"
                       value="<?= htmlspecialchars($_POST['title'] ?? ''); ?>" />
            </div>
            <div class="form-group">
                <label for="description">Beschreibung</label>
                <textarea id="description" name="description"
                          placeholder="Details, Anforderungen, Links …"><?= htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <?php
                        $preStatus = $this->pre_status ?? 'todo';
                        $statuses  = array('todo' => 'To Do', 'inprogress' => 'In Progress', 'done' => 'Done');
                        foreach ($statuses as $val => $label):
                        ?>
                            <option value="<?= $val; ?>" <?= ($preStatus === $val ? 'selected' : ''); ?>>
                                <?= $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="deadline">Deadline</label>
                    <input type="date" id="deadline" name="deadline"
                           value="<?= htmlspecialchars($_POST['deadline'] ?? ''); ?>" />
                </div>
            </div>
            <div class="form-group">
                <label for="assigned_to">Zuweisen an</label>
                <select id="assigned_to" name="assigned_to">
                    <option value="">— niemanden —</option>
                    <?php foreach ($this->members as $m): ?>
                        <option value="<?= $m->user_id; ?>"
                            <?= (($_POST['assigned_to'] ?? '') == $m->user_id ? 'selected' : ''); ?>>
                            <?= htmlspecialchars($m->user_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" name="submit" value="1" class="btn-primary">Task erstellen</button>
                <a href="<?= Config::get('URL'); ?>project/board/<?= $this->project->id; ?>" class="btn-cancel">Abbrechen</a>
            </div>
        </form>
    </div>
</div>
