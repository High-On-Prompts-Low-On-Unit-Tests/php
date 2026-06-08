<div class="container">

<?php $this->renderFeedbackMessages(); ?>

<style>
    /* CSS grid gallery based on selfhtml Bilderzoom-grid */
    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
        margin: 20px 0;
    }
    .gallery-item {
        position: relative;
        overflow: hidden;
        border: 1px solid #ddd;
        border-radius: 4px;
        background: #f9f9f9;
    }
    .gallery-item img {
        width: 100%;
        height: 180px;
        object-fit: cover;
        display: block;
        cursor: pointer;
        transition: transform 0.3s;
    }
    .gallery-item img:hover {
        transform: scale(1.05);
    }
    .gallery-item .info {
        padding: 8px;
        font-size: 0.85em;
    }
    .gallery-item .actions {
        padding: 5px 8px 8px;
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }
    .gallery-item .actions a,
    .gallery-item .actions button {
        font-size: 0.8em;
        padding: 3px 8px;
        cursor: pointer;
        text-decoration: none;
        border: 1px solid #999;
        border-radius: 3px;
        background: #eee;
        color: #333;
    }
    .gallery-item .actions a:hover,
    .gallery-item .actions button:hover {
        background: #ddd;
    }
    .badge-shared {
        position: absolute;
        top: 5px;
        right: 5px;
        background: #28a745;
        color: #fff;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 0.75em;
    }
    .badge-private {
        position: absolute;
        top: 5px;
        right: 5px;
        background: #dc3545;
        color: #fff;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 0.75em;
    }
    .upload-form {
        margin: 20px 0;
        padding: 15px;
        border: 2px dashed #ccc;
        border-radius: 6px;
        background: #fafafa;
    }
</style>

    <h2>Meine Galerie</h2>

    <!-- Upload form -->
    <div class="upload-form">
        <form action="<?= Config::get('URL'); ?>gallery/upload" method="post" enctype="multipart/form-data">
            <input type="file" name="image" accept=".jpg,.jpeg,.png,.gif" required />
            <button type="submit">Hochladen</button>
            <small>(max. 5 MB, JPG/PNG/GIF)</small>
        </form>
    </div>

    <!-- Own images -->
    <h3>Meine Bilder</h3>
    <?php if (empty($this->own_images)): ?>
        <p>Noch keine Bilder hochgeladen.</p>
    <?php else: ?>
        <div class="gallery-grid">
            <?php foreach ($this->own_images as $img): ?>
                <div class="gallery-item">
                    <span class="<?= $img->shared ? 'badge-shared' : 'badge-private'; ?>">
                        <?= $img->shared ? 'Öffentlich' : 'Privat'; ?>
                    </span>
                    <a href="<?= Config::get('URL'); ?>gallery/view/<?= $img->id; ?>" target="_blank">
                        <img src="<?= Config::get('URL'); ?>gallery/view/<?= $img->id; ?>" alt="<?= htmlspecialchars($img->original_name ?? $img->filename); ?>" />
                    </a>
                    <div class="info">
                        <?= htmlspecialchars($img->original_name ?? $img->filename); ?><br />
                        <?= round($img->size / 1024); ?> KB | <?= $img->downloads; ?> Downloads
                    </div>
                    <div class="actions">
                        <a href="<?= Config::get('URL'); ?>gallery/download/<?= $img->id; ?>">Download</a>
                        <a href="<?= Config::get('URL'); ?>gallery/toggleShare/<?= $img->id; ?>">
                            <?= $img->shared ? 'Privat machen' : 'Freigeben'; ?>
                        </a>
                        <a href="<?= Config::get('URL'); ?>gallery/delete/<?= $img->id; ?>"
                           onclick="return confirm('Wirklich löschen?');">Löschen</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Shared images from other users -->
    <h3>Freigegebene Bilder</h3>
    <?php if (empty($this->shared_images)): ?>
        <p>Keine freigegebenen Bilder vorhanden.</p>
    <?php else: ?>
        <div class="gallery-grid">
            <?php foreach ($this->shared_images as $img): ?>
                <div class="gallery-item">
                    <a href="<?= Config::get('URL'); ?>gallery/view/<?= $img->id; ?>" target="_blank">
                        <img src="<?= Config::get('URL'); ?>gallery/view/<?= $img->id; ?>" alt="<?= htmlspecialchars($img->original_name ?? $img->filename); ?>" />
                    </a>
                    <div class="info">
                        von <?= htmlspecialchars($img->owner_name); ?><br />
                        <?= round($img->size / 1024); ?> KB
                    </div>
                    <div class="actions">
                        <a href="<?= Config::get('URL'); ?>gallery/download/<?= $img->id; ?>">Download</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>
