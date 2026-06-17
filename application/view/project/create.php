<?php $this->renderFeedbackMessages(); ?>

<style>
    .form-card {
        max-width: 540px; margin: 30px auto;
        background: #fff; border: 1px solid #ddd;
        border-radius: 6px; padding: 28px 32px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.07);
    }
    .form-card h2 { margin: 0 0 20px; font-size: 1.3em; }
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 0.88em; font-weight: bold; margin-bottom: 5px; color: #444; }
    .form-group input[type=text],
    .form-group textarea {
        width: 100%; padding: 8px 10px; border: 1px solid #ccc;
        border-radius: 4px; font-size: 0.95em; box-sizing: border-box;
    }
    .form-group textarea { height: 90px; resize: vertical; }
    .form-group input[type=text]:focus,
    .form-group textarea:focus { outline: none; border-color: #0074d9; }
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
        <h2>Neues Projekt erstellen</h2>
        <form method="post" action="<?= Config::get('URL'); ?>project/create">
            <div class="form-group">
                <label for="name">Projektname *</label>
                <input type="text" id="name" name="name" required
                       placeholder="z.B. Website Redesign"
                       value="<?= htmlspecialchars($_POST['name'] ?? ''); ?>" />
            </div>
            <div class="form-group">
                <label for="description">Beschreibung</label>
                <textarea id="description" name="description"
                          placeholder="Kurze Beschreibung des Projekts …"><?= htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" name="submit" value="1" class="btn-primary">Erstellen</button>
                <a href="<?= Config::get('URL'); ?>project" class="btn-cancel">Abbrechen</a>
            </div>
        </form>
    </div>
</div>
