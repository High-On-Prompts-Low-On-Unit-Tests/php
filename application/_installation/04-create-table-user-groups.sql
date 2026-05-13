CREATE TABLE IF NOT EXISTS `user_groups` (
    `id` INT(11) NOT NULL,
    `group_name` VARCHAR(50) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `user_groups` (`id`, `group_name`) VALUES
(1, 'Guest'),
(2, 'Normal'),
(3, 'Reserved 3'),
(4, 'Reserved 4'),
(5, 'Reserved 5'),
(6, 'Reserved 6'),
(7, 'Admin');
