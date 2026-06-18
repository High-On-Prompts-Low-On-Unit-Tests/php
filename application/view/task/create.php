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
        border-radius: 4px; font-size: 0.95em; box-sizing: border-box; background: #fff;
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
        border: 1px solid #ccc; border-radius: 4px; text-decoration: none; font-size: 0.95em;
    }
    .btn-cancel:hover { background: #e8e8e8; }

    /* Combobox */
    .cb-wrap { position: relative; }
    .cb-wrap input[type=text] { cursor: pointer; }
    .cb-dropdown {
        display: none; position: fixed;
        background: #fff; border: 1px solid #ccc; border-radius: 4px;
        z-index: 99999; max-height: 200px; overflow-y: auto;
        box-shadow: 0 4px 10px rgba(0,0,0,0.12);
    }
    .cb-item {
        padding: 8px 10px; cursor: pointer; font-size: 0.92em;
    }
    .cb-item:hover, .cb-item.active { background: #e8f4fd; color: #0074d9; }
    .cb-item.none-option { color: #999; font-style: italic; }
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
                        foreach ($statuses as $val => $lbl):
                        ?>
                            <option value="<?= $val; ?>" <?= ($preStatus === $val ? 'selected' : ''); ?>>
                                <?= $lbl; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="priority">Priorität</label>
                    <select id="priority" name="priority">
                        <?php
                        $curPrio = $_POST['priority'] ?? 'medium';
                        $prios   = array('low' => '🟢 Low', 'medium' => '🟡 Medium', 'high' => '🔴 High');
                        foreach ($prios as $val => $lbl):
                        ?>
                            <option value="<?= $val; ?>" <?= ($curPrio === $val ? 'selected' : ''); ?>>
                                <?= $lbl; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Zuweisen an</label>
                    <input type="hidden" id="assigned_to" name="assigned_to"
                           value="<?= htmlspecialchars($_POST['assigned_to'] ?? ''); ?>" />
                    <div class="cb-wrap">
                        <input type="text" id="assigned_display" autocomplete="off"
                               placeholder="— niemanden —" readonly
                               value="<?php
                                   $preAssigned = $_POST['assigned_to'] ?? '';
                                   if ($preAssigned) {
                                       foreach ($this->members as $m) {
                                           if ($m->user_id == $preAssigned) {
                                               echo htmlspecialchars($m->user_name); break;
                                           }
                                       }
                                   }
                               ?>" />
                        <div id="assigned_dropdown" class="cb-dropdown"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="deadline">Deadline</label>
                    <input type="date" id="deadline" name="deadline"
                           value="<?= htmlspecialchars($_POST['deadline'] ?? ''); ?>" />
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" name="submit" value="1" class="btn-primary">Task erstellen</button>
                <a href="<?= Config::get('URL'); ?>project/board/<?= $this->project->id; ?>" class="btn-cancel">Abbrechen</a>
            </div>
        </form>
    </div>
</div>

<?php
// Pass members as JSON for the combobox
$membersJson = json_encode(array_map(function($m) {
    return array('id' => $m->user_id, 'name' => $m->user_name);
}, $this->members));
?>
<script>
(function () {
    var members  = <?= $membersJson; ?>;
    var display  = document.getElementById('assigned_display');
    var hidden   = document.getElementById('assigned_to');
    var drop     = document.getElementById('assigned_dropdown');
    document.body.appendChild(drop);

    function buildItems(filter) {
        drop.innerHTML = '';
        var all = [{id: '', name: '— niemanden —'}].concat(members);
        var q   = (filter || '').toLowerCase();
        all.forEach(function (m) {
            if (q && m.id && m.name.toLowerCase().indexOf(q) === -1) return;
            var item = document.createElement('div');
            item.className = 'cb-item' + (m.id === '' ? ' none-option' : '');
            item.textContent = m.name;
            item.addEventListener('mousedown', function (e) {
                e.preventDefault();
                hidden.value  = m.id;
                display.value = m.id ? m.name : '';
                display.placeholder = '— niemanden —';
                closeDrop();
            });
            drop.appendChild(item);
        });
    }

    function openDrop() {
        var rect = display.getBoundingClientRect();
        drop.style.top   = rect.bottom + 'px';
        drop.style.left  = rect.left   + 'px';
        drop.style.width = rect.width  + 'px';
        buildItems(display.value);
        drop.style.display = 'block';
        display.readOnly = false;
        display.select();
    }

    function closeDrop() {
        drop.style.display = 'none';
        display.readOnly = true;
        /* restore display to selected name if user typed but didn't pick */
        var found = members.find(function (m) { return m.id == hidden.value; });
        display.value = found ? found.name : '';
    }

    display.addEventListener('click', openDrop);
    display.addEventListener('input', function () {
        buildItems(display.value);
        drop.style.display = 'block';
    });
    display.addEventListener('blur', function () {
        setTimeout(closeDrop, 150);
    });
}());
</script>
