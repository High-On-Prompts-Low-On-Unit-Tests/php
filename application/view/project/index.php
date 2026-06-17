<?php $this->renderFeedbackMessages(); ?>

<style>
    .project-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
        margin: 20px 0;
    }
    .project-card {
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 18px;
        background: #fff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        transition: box-shadow 0.2s;
    }
    .project-card:hover { box-shadow: 0 3px 8px rgba(0,0,0,0.15); }
    .project-card h3 { margin: 0 0 8px; font-size: 1.1em; }
    .project-card h3 a { text-decoration: none; color: #333; }
    .project-card h3 a:hover { color: #0074d9; }
    .project-card .meta { font-size: 0.82em; color: #888; margin-bottom: 10px; }
    .project-card .desc { font-size: 0.9em; color: #555; margin-bottom: 12px; min-height: 36px; }
    .project-card .actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .project-card .actions a {
        font-size: 0.82em; padding: 4px 10px;
        border: 1px solid #ccc; border-radius: 3px;
        text-decoration: none; color: #333; background: #f5f5f5;
    }
    .project-card .actions a:hover { background: #e8e8e8; }
    .project-card .actions a.danger { border-color: #c00; color: #c00; }
    .project-card .actions a.danger:hover { background: #fee; }
    .role-badge {
        display: inline-block; font-size: 0.75em; padding: 2px 7px;
        border-radius: 10px; margin-left: 6px; vertical-align: middle;
        background: #e8f4fd; color: #0074d9;
    }
    .role-badge.owner { background: #fdf3e8; color: #e67e22; }
    .new-project-btn {
        display: inline-block; margin-bottom: 10px; padding: 8px 18px;
        background: #0074d9; color: #fff; text-decoration: none;
        border-radius: 4px; font-size: 0.95em;
    }
    .new-project-btn:hover { background: #005fa3; }
</style>

<div class="container">
    <h2>Meine Projekte</h2>

    <a href="<?= Config::get('URL'); ?>project/create" class="new-project-btn">+ Neues Projekt</a>

    <?php if (empty($this->projects)): ?>
        <p style="color:#888; margin-top:20px;">Noch keine Projekte. Erstelle dein erstes Projekt!</p>
    <?php else: ?>
        <div class="project-grid">
            <?php foreach ($this->projects as $p): ?>
                <div class="project-card">
                    <h3>
                        <a href="<?= Config::get('URL'); ?>project/board/<?= $p->id; ?>">
                            <?= htmlspecialchars($p->name); ?>
                        </a>
                        <span class="role-badge <?= $p->user_role; ?>">
                            <?= $p->user_role === 'owner' ? 'Owner' : 'Mitglied'; ?>
                        </span>
                    </h3>
                    <div class="meta">
                        <?= $p->task_count; ?> Task<?= $p->task_count != 1 ? 's' : ''; ?> &middot;
                        von <?= htmlspecialchars($p->owner_name); ?> &middot;
                        <?= date('d.m.Y', strtotime($p->created_at)); ?>
                    </div>
                    <div class="desc">
                        <?= htmlspecialchars(mb_substr($p->description ?? '', 0, 100)); ?>
                        <?= mb_strlen($p->description ?? '') > 100 ? '…' : ''; ?>
                    </div>
                    <div class="actions">
                        <a href="<?= Config::get('URL'); ?>project/board/<?= $p->id; ?>">Board öffnen</a>
                        <?php if ($p->user_role === 'owner'): ?>
                            <a href="<?= Config::get('URL'); ?>project/edit/<?= $p->id; ?>">Bearbeiten</a>
                            <a href="<?= Config::get('URL'); ?>project/delete/<?= $p->id; ?>"
                               class="danger"
                               onclick="return confirm('Projekt wirklich löschen? Alle Tasks werden gelöscht.');">
                                Löschen
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
