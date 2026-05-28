<?php

/**
 * NoteModel
 * This is basically a simple CRUD (Create/Read/Update/Delete) demonstration.
 */
class NoteModel
{
    /**
     * Get all notes (notes are just example data that the user has created)
     * @return array an array with several objects (the results)
     */
    public static function getAllNotes()
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "SELECT user_id, note_id, note_text FROM notes WHERE user_id = :user_id";
        $query = $database->prepare($sql);
        $query->execute(array(':user_id' => Session::get('user_id')));

        // fetchAll() is the PDO method that gets all result rows
        return $query->fetchAll();
    }

    /**
     * Get a single note (PDO version — used by the application)
     * @param int $note_id id of the specific note
     * @return object a single object (the result)
     */
    public static function getNote($note_id)
    {
        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "SELECT user_id, note_id, note_text FROM notes WHERE user_id = :user_id AND note_id = :note_id LIMIT 1";
        $query = $database->prepare($sql);
        $query->execute(array(':user_id' => Session::get('user_id'), ':note_id' => $note_id));

        // fetch() is the PDO method that gets a single result
        return $query->fetch();
    }

    /**
     * Get a single note (MySQLi version — same logic, different DB extension)
     * @param int $note_id id of the specific note
     * @return object|null a single object (the result) or null
     */
    public static function getNoteMySQLi($note_id)
    {
        // 1. Verbindung: MySQLi direkt instanziieren (kein Singleton wie bei PDO)
        $mysqli = new mysqli(
            Config::get('DB_HOST'),
            Config::get('DB_USER'),
            Config::get('DB_PASS'),
            Config::get('DB_NAME'),
            (int) Config::get('DB_PORT')
        );

        if ($mysqli->connect_error) {
            echo 'Database connection failed: ' . $mysqli->connect_errno;
            exit;
        }

        $mysqli->set_charset(Config::get('DB_CHARSET'));

        // 2. SQL: Fragezeichen-Platzhalter statt benannter Platzhalter
        $sql = "SELECT user_id, note_id, note_text FROM notes WHERE user_id = ? AND note_id = ? LIMIT 1";

        // 3. Prepare: Statement vorbereiten
        $stmt = $mysqli->prepare($sql);

        // 4. Bind: Parameter separat binden ("ii" = zwei Integer)
        $user_id = Session::get('user_id');
        $stmt->bind_param("ii", $user_id, $note_id);

        // 5. Execute: Ausführen
        $stmt->execute();

        // 6. Fetch: Result-Set holen, dann Objekt abrufen
        $result = $stmt->get_result();
        $note = $result->fetch_object();

        // 7. Close: Statement und Verbindung schließen
        $stmt->close();
        $mysqli->close();

        return $note;
    }

    /**
     * Set a note (create a new one)
     * @param string $note_text note text that will be created
     * @return bool feedback (was the note created properly ?)
     */
    public static function createNote($note_text)
    {
        if (!$note_text || strlen($note_text) == 0) {
            Session::add('feedback_negative', Text::get('FEEDBACK_NOTE_CREATION_FAILED'));
            return false;
        }

        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "INSERT INTO notes (note_text, user_id) VALUES (:note_text, :user_id)";
        $query = $database->prepare($sql);
        $query->execute(array(':note_text' => $note_text, ':user_id' => Session::get('user_id')));

        if ($query->rowCount() == 1) {
            return true;
        }

        // default return
        Session::add('feedback_negative', Text::get('FEEDBACK_NOTE_CREATION_FAILED'));
        return false;
    }

    /**
     * Update an existing note
     * @param int $note_id id of the specific note
     * @param string $note_text new text of the specific note
     * @return bool feedback (was the update successful ?)
     */
    public static function updateNote($note_id, $note_text)
    {
        if (!$note_id || !$note_text) {
            return false;
        }

        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "UPDATE notes SET note_text = :note_text WHERE note_id = :note_id AND user_id = :user_id LIMIT 1";
        $query = $database->prepare($sql);
        $query->execute(array(':note_id' => $note_id, ':note_text' => $note_text, ':user_id' => Session::get('user_id')));

        if ($query->rowCount() == 1) {
            return true;
        }

        Session::add('feedback_negative', Text::get('FEEDBACK_NOTE_EDITING_FAILED'));
        return false;
    }

    /**
     * Delete a specific note
     * @param int $note_id id of the note
     * @return bool feedback (was the note deleted properly ?)
     */
    public static function deleteNote($note_id)
    {
        if (!$note_id) {
            return false;
        }

        $database = DatabaseFactory::getFactory()->getConnection();

        $sql = "DELETE FROM notes WHERE note_id = :note_id AND user_id = :user_id LIMIT 1";
        $query = $database->prepare($sql);
        $query->execute(array(':note_id' => $note_id, ':user_id' => Session::get('user_id')));

        if ($query->rowCount() == 1) {
            return true;
        }

        // default return
        Session::add('feedback_negative', Text::get('FEEDBACK_NOTE_DELETION_FAILED'));
        return false;
    }
}
