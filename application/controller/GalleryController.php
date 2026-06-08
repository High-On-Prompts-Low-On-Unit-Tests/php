<?php

/**
 * GalleryController
 *
 * Private image gallery with upload, download, share and delete.
 * Images are stored outside public web root for security.
 */
class GalleryController extends Controller
{
    /**
     * Auth check in constructor — all actions require login.
     */
    public function __construct()
    {
        parent::__construct();
        Auth::checkAuthentication();
    }

    /**
     * Shows own images and shared images from other users.
     */
    public function index()
    {
        $userId = Session::get('user_id');

        $this->View->render('gallery/index', array(
            'own_images'    => GalleryModel::getOwnImages($userId),
            'shared_images' => GalleryModel::getSharedImages($userId)
        ));
    }

    /**
     * Handles image upload via POST.
     * Validates MIME type, file size and moves file to private directory.
     */
    public function upload()
    {
        $userId = Session::get('user_id');

        // check if file was sent
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            Session::add('feedback_negative', 'Upload fehlgeschlagen.');
            Redirect::to('gallery');
            return;
        }

        $file = $_FILES['image'];

        // check file size (max 5 MB)
        if ($file['size'] > GalleryModel::MAX_SIZE) {
            Session::add('feedback_negative', 'Datei zu gross (max. 5 MB).');
            Redirect::to('gallery');
            return;
        }

        // check MIME type from file content (not from browser header)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, GalleryModel::ALLOWED_TYPES)) {
            Session::add('feedback_negative', 'Dateityp nicht erlaubt (nur JPG, PNG, GIF).');
            Redirect::to('gallery');
            return;
        }

        // create user directory if it doesn't exist
        $uploadDir = GalleryModel::getUserUploadDir($userId);
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // generate hashed filename (prevents guessing), keep original name for display
        $originalName = basename($file['name']);
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $filename = md5(uniqid(mt_rand(), true)) . '.' . strtolower($ext);

        // move file to private directory
        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            GalleryModel::saveImage($userId, $filename, $originalName, $file['size']);
            Session::add('feedback_positive', 'Bild hochgeladen.');
        } else {
            Session::add('feedback_negative', 'Fehler beim Speichern.');
        }

        Redirect::to('gallery');
    }

    /**
     * Serves an image file via PHP (no direct URL access).
     * Only the owner or users with access to shared images can view.
     *
     * @param int $imageId
     */
    public function view($imageId)
    {
        $userId = Session::get('user_id');
        $image = GalleryModel::getImageById($imageId);

        if (!$image) {
            Redirect::to('gallery');
            return;
        }

        // access check: owner or shared
        if ($image->owner_id != $userId && $image->shared != 1) {
            Session::add('feedback_negative', 'Kein Zugriff.');
            Redirect::to('gallery');
            return;
        }

        $path = GalleryModel::getUserUploadDir($image->owner_id) . $image->filename;

        if (!file_exists($path)) {
            Session::add('feedback_negative', 'Datei nicht gefunden.');
            Redirect::to('gallery');
            return;
        }

        // serve image inline (display in browser)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($path);
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . basename($image->filename) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    /**
     * Downloads an image file as attachment.
     *
     * @param int $imageId
     */
    public function download($imageId)
    {
        $userId = Session::get('user_id');
        $image = GalleryModel::getImageById($imageId);

        if (!$image) {
            Redirect::to('gallery');
            return;
        }

        // access check: owner or shared
        if ($image->owner_id != $userId && $image->shared != 1) {
            Session::add('feedback_negative', 'Kein Zugriff.');
            Redirect::to('gallery');
            return;
        }

        $path = GalleryModel::getUserUploadDir($image->owner_id) . $image->filename;

        if (!file_exists($path)) {
            Session::add('feedback_negative', 'Datei nicht gefunden.');
            Redirect::to('gallery');
            return;
        }

        // increment download counter
        GalleryModel::incrementDownloads($imageId);

        // serve as download with original filename
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($path);
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . basename($image->original_name) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    /**
     * Toggles shared/private status of an image (owner only).
     *
     * @param int $imageId
     */
    public function toggleShare($imageId)
    {
        $userId = Session::get('user_id');
        GalleryModel::toggleShared($imageId, $userId);
        Redirect::to('gallery');
    }

    /**
     * Deletes an image (owner only). Removes file from disk and DB record.
     *
     * @param int $imageId
     */
    public function delete($imageId)
    {
        $userId = Session::get('user_id');
        $image = GalleryModel::getImageById($imageId);

        if ($image && $image->owner_id == $userId) {
            // delete file from disk
            $path = GalleryModel::getUserUploadDir($userId) . $image->filename;
            if (file_exists($path)) {
                unlink($path);
            }
            // delete DB record
            GalleryModel::deleteImage($imageId, $userId);
            Session::add('feedback_positive', 'Bild gelöscht.');
        }

        Redirect::to('gallery');
    }
}
