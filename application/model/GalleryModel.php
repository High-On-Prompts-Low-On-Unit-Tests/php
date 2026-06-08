<?php

/**
 * GalleryModel
 *
 * Handles all DB queries for the image gallery.
 */
class GalleryModel
{
    /** @var string Base path for user image storage (outside public web root) */
    const UPLOAD_DIR = '/userpictures/';

    /** @var array Allowed MIME types for upload */
    const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif'];

    /** @var int Max file size in bytes (5 MB) */
    const MAX_SIZE = 5242880;

    /**
     * Returns the full upload directory path for a user.
     * Path: <project_root>/userpictures/<UserID>/
     *
     * @param int $userId
     * @return string
     */
    public static function getUserUploadDir($userId)
    {
        return realpath(dirname(__FILE__) . '/../..') . self::UPLOAD_DIR . (int)$userId . '/';
    }

    /**
     * Get all images owned by a user.
     *
     * @param int $userId
     * @return array
     */
    public static function getOwnImages($userId)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "SELECT * FROM gallery_images WHERE owner_id = :owner_id ORDER BY created_at DESC";
        $query = $database->prepare($sql);
        $query->execute(array(':owner_id' => $userId));
        return $query->fetchAll();
    }

    /**
     * Get all shared (public) images from other users.
     *
     * @param int $userId current user id (excluded from results)
     * @return array
     */
    public static function getSharedImages($userId)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "SELECT gi.*, u.user_name AS owner_name
                FROM gallery_images gi
                JOIN users u ON u.user_id = gi.owner_id
                WHERE gi.shared = 1 AND gi.owner_id != :owner_id
                ORDER BY gi.created_at DESC";
        $query = $database->prepare($sql);
        $query->execute(array(':owner_id' => $userId));
        return $query->fetchAll();
    }

    /**
     * Get a single image by ID.
     *
     * @param int $imageId
     * @return object|false
     */
    public static function getImageById($imageId)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "SELECT * FROM gallery_images WHERE id = :id LIMIT 1";
        $query = $database->prepare($sql);
        $query->execute(array(':id' => $imageId));
        return $query->fetch();
    }

    /**
     * Save image record to database.
     *
     * @param int    $ownerId
     * @param string $filename     hashed filename on disk
     * @param string $originalName original upload name for display
     * @param int    $size
     * @return bool
     */
    public static function saveImage($ownerId, $filename, $originalName, $size)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "INSERT INTO gallery_images (owner_id, filename, original_name, size) VALUES (:owner_id, :filename, :original_name, :size)";
        $query = $database->prepare($sql);
        return $query->execute(array(
            ':owner_id'      => $ownerId,
            ':filename'      => $filename,
            ':original_name' => $originalName,
            ':size'          => $size
        ));
    }

    /**
     * Toggle the shared status of an image.
     *
     * @param int $imageId
     * @param int $ownerId ensures only the owner can toggle
     * @return bool
     */
    public static function toggleShared($imageId, $ownerId)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "UPDATE gallery_images SET shared = NOT shared WHERE id = :id AND owner_id = :owner_id LIMIT 1";
        $query = $database->prepare($sql);
        return $query->execute(array(':id' => $imageId, ':owner_id' => $ownerId));
    }

    /**
     * Delete an image record from DB.
     *
     * @param int $imageId
     * @param int $ownerId ensures only the owner can delete
     * @return bool
     */
    public static function deleteImage($imageId, $ownerId)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "DELETE FROM gallery_images WHERE id = :id AND owner_id = :owner_id LIMIT 1";
        $query = $database->prepare($sql);
        return $query->execute(array(':id' => $imageId, ':owner_id' => $ownerId));
    }

    /**
     * Increment the download counter.
     *
     * @param int $imageId
     * @return bool
     */
    public static function incrementDownloads($imageId)
    {
        $database = DatabaseFactory::getFactory()->getConnection();
        $sql = "UPDATE gallery_images SET downloads = downloads + 1 WHERE id = :id LIMIT 1";
        $query = $database->prepare($sql);
        return $query->execute(array(':id' => $imageId));
    }
}
