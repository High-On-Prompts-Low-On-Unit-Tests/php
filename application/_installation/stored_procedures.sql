-- ============================================================
-- Stored Procedures für den Messenger-Dienst (Gruppenchat)
-- ============================================================

-- ----------------------------------------
-- 1. sp_get_user_groups
--    Alle Gruppen eines Users mit Anzeigenamen und letzter Nachricht
-- ----------------------------------------
DROP PROCEDURE IF EXISTS sp_get_user_groups;
DELIMITER //
CREATE PROCEDURE sp_get_user_groups(IN p_user_id INT)
BEGIN
    SELECT g.id, g.name, g.type, g.created_by,
           CASE
               WHEN g.type = 'direct' THEN (
                   SELECT u.user_name FROM group_members gm2
                   JOIN users u ON u.user_id = gm2.user_id
                   WHERE gm2.group_id = g.id AND gm2.user_id != p_user_id
                   LIMIT 1
               )
               ELSE g.name
           END AS display_name,
           (SELECT gm3.created_at FROM group_messages gm3
            WHERE gm3.group_id = g.id ORDER BY gm3.created_at DESC LIMIT 1
           ) AS last_message_at
    FROM chat_groups g
    JOIN group_members gm ON gm.group_id = g.id AND gm.user_id = p_user_id
    ORDER BY last_message_at DESC, g.created_at DESC;
END //
DELIMITER ;

-- ----------------------------------------
-- 2. sp_get_group_messages
--    Alle Nachrichten einer Gruppe mit Absendernamen
-- ----------------------------------------
DROP PROCEDURE IF EXISTS sp_get_group_messages;
DELIMITER //
CREATE PROCEDURE sp_get_group_messages(IN p_group_id INT)
BEGIN
    SELECT m.id, m.group_id, m.sender_id, m.message_text, m.created_at,
           u.user_name AS sender_name
    FROM group_messages m
    JOIN users u ON u.user_id = m.sender_id
    WHERE m.group_id = p_group_id
    ORDER BY m.created_at ASC;
END //
DELIMITER ;

-- ----------------------------------------
-- 3. sp_send_message
--    Nachricht in eine Gruppe senden
-- ----------------------------------------
DROP PROCEDURE IF EXISTS sp_send_message;
DELIMITER //
CREATE PROCEDURE sp_send_message(
    IN p_group_id INT,
    IN p_sender_id INT,
    IN p_message_text TEXT
)
BEGIN
    INSERT INTO group_messages (group_id, sender_id, message_text)
    VALUES (p_group_id, p_sender_id, p_message_text);
END //
DELIMITER ;

-- ----------------------------------------
-- 4. sp_get_group
--    Einzelne Gruppe anhand ID abrufen
-- ----------------------------------------
DROP PROCEDURE IF EXISTS sp_get_group;
DELIMITER //
CREATE PROCEDURE sp_get_group(IN p_group_id INT)
BEGIN
    SELECT * FROM chat_groups WHERE id = p_group_id;
END //
DELIMITER ;

-- ----------------------------------------
-- 5. sp_get_group_members
--    Alle Mitglieder einer Gruppe mit Rolle und Username
-- ----------------------------------------
DROP PROCEDURE IF EXISTS sp_get_group_members;
DELIMITER //
CREATE PROCEDURE sp_get_group_members(IN p_group_id INT)
BEGIN
    SELECT gm.user_id, gm.role, gm.joined_at, u.user_name
    FROM group_members gm
    JOIN users u ON u.user_id = gm.user_id
    WHERE gm.group_id = p_group_id
    ORDER BY gm.role ASC, u.user_name ASC;
END //
DELIMITER ;

-- ----------------------------------------
-- 6. sp_is_group_member
--    Prüft ob ein User Mitglied einer Gruppe ist (gibt 1 oder nichts zurück)
-- ----------------------------------------
DROP PROCEDURE IF EXISTS sp_is_group_member;
DELIMITER //
CREATE PROCEDURE sp_is_group_member(IN p_group_id INT, IN p_user_id INT)
BEGIN
    SELECT 1 AS is_member FROM group_members
    WHERE group_id = p_group_id AND user_id = p_user_id;
END //
DELIMITER ;

-- ----------------------------------------
-- 7. sp_is_group_admin
--    Prüft ob ein User Admin einer Gruppe ist
-- ----------------------------------------
DROP PROCEDURE IF EXISTS sp_is_group_admin;
DELIMITER //
CREATE PROCEDURE sp_is_group_admin(IN p_group_id INT, IN p_user_id INT)
BEGIN
    SELECT 1 AS is_admin FROM group_members
    WHERE group_id = p_group_id AND user_id = p_user_id AND role = 'admin';
END //
DELIMITER ;

-- ----------------------------------------
-- 8. sp_get_unread_counts
--    Ungelesene Nachrichten pro Gruppe für einen User
-- ----------------------------------------
DROP PROCEDURE IF EXISTS sp_get_unread_counts;
DELIMITER //
CREATE PROCEDURE sp_get_unread_counts(IN p_user_id INT)
BEGIN
    SELECT gm.group_id,
           COUNT(msg.id) AS cnt
    FROM group_members gm
    LEFT JOIN group_messages msg
        ON msg.group_id = gm.group_id
        AND msg.sender_id != p_user_id
        AND (gm.last_read_at IS NULL OR msg.created_at > gm.last_read_at)
    WHERE gm.user_id = p_user_id
    GROUP BY gm.group_id;
END //
DELIMITER ;

-- ----------------------------------------
-- 9. sp_mark_as_read
--    Setzt last_read_at auf NOW() für einen User in einer Gruppe
-- ----------------------------------------
DROP PROCEDURE IF EXISTS sp_mark_as_read;
DELIMITER //
CREATE PROCEDURE sp_mark_as_read(IN p_group_id INT, IN p_user_id INT)
BEGIN
    UPDATE group_members SET last_read_at = NOW()
    WHERE group_id = p_group_id AND user_id = p_user_id;
END //
DELIMITER ;

-- ----------------------------------------
-- 10. sp_get_all_users
--     Alle User außer dem angegebenen (für Chat-Partner-Auswahl)
-- ----------------------------------------
DROP PROCEDURE IF EXISTS sp_get_all_users;
DELIMITER //
CREATE PROCEDURE sp_get_all_users(IN p_exclude_user_id INT)
BEGIN
    SELECT user_id, user_name FROM users
    WHERE user_id != p_exclude_user_id
    ORDER BY user_name;
END //
DELIMITER ;

-- ----------------------------------------
-- 11. sp_insert_member
--     Mitglied zu einer Gruppe hinzufügen
-- ----------------------------------------
DROP PROCEDURE IF EXISTS sp_insert_member;
DELIMITER //
CREATE PROCEDURE sp_insert_member(
    IN p_group_id INT,
    IN p_user_id INT,
    IN p_role VARCHAR(10)
)
BEGIN
    INSERT INTO group_members (group_id, user_id, role)
    VALUES (p_group_id, p_user_id, p_role);
END //
DELIMITER ;

-- ----------------------------------------
-- 12. sp_remove_member
--     Mitglied aus einer Gruppe entfernen
-- ----------------------------------------
DROP PROCEDURE IF EXISTS sp_remove_member;
DELIMITER //
CREATE PROCEDURE sp_remove_member(IN p_group_id INT, IN p_user_id INT)
BEGIN
    DELETE FROM group_members
    WHERE group_id = p_group_id AND user_id = p_user_id;
END //
DELIMITER ;

-- ----------------------------------------
-- 13. sp_rename_group
--     Gruppenname ändern
-- ----------------------------------------
DROP PROCEDURE IF EXISTS sp_rename_group;
DELIMITER //
CREATE PROCEDURE sp_rename_group(IN p_group_id INT, IN p_name VARCHAR(100))
BEGIN
    UPDATE chat_groups SET name = p_name WHERE id = p_group_id;
END //
DELIMITER ;

-- ----------------------------------------
-- 14. sp_create_group
--     Neue Gruppe erstellen, gibt die neue ID zurück
-- ----------------------------------------
DROP PROCEDURE IF EXISTS sp_create_group;
DELIMITER //
CREATE PROCEDURE sp_create_group(
    IN p_name VARCHAR(100),
    IN p_type VARCHAR(10),
    IN p_created_by INT,
    OUT p_group_id INT
)
BEGIN
    INSERT INTO chat_groups (name, type, created_by)
    VALUES (p_name, p_type, p_created_by);
    SET p_group_id = LAST_INSERT_ID();
END //
DELIMITER ;

-- ----------------------------------------
-- 15. sp_find_direct_chat
--     Sucht einen bestehenden Direct-Chat zwischen zwei Usern
-- ----------------------------------------
DROP PROCEDURE IF EXISTS sp_find_direct_chat;
DELIMITER //
CREATE PROCEDURE sp_find_direct_chat(IN p_user_id INT, IN p_partner_id INT)
BEGIN
    SELECT g.id FROM chat_groups g
    JOIN group_members gm1 ON gm1.group_id = g.id AND gm1.user_id = p_user_id
    JOIN group_members gm2 ON gm2.group_id = g.id AND gm2.user_id = p_partner_id
    WHERE g.type = 'direct'
    LIMIT 1;
END //
DELIMITER ;
