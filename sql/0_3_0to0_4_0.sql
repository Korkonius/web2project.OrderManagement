CREATE TABLE `ordermgmt_modules` (
`module_id` int NULL COMMENT 'Unique identifier for the module',
`module_name` tinytext NULL COMMENT 'Short name identifying the module',
`module_description` mediumtext NULL COMMENT 'A short description of the module',
`module_buildtime` double NULL COMMENT 'Estimated time spent to build one of these modules',
`module_delivered` int NULL COMMENT 'The amount of modules of this type that is delivered',
PRIMARY KEY (`module_id`));

CREATE TABLE `ordermgmt_module_components` (
`stored_component_id` int(11) NOT NULL,
`module_id` int(11) NOT NULL,
`amount` varchar(255) NOT NULL COMMENT 'How many parts is part of this module',
PRIMARY KEY (`stored_component_id`, `module_id`)
) ENGINE=InnoDB
DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `ordermgmt_module_files` (
`file_id` int NULL COMMENT 'Referencing a file id present in the system',
`module_id` int NULL,
PRIMARY KEY (`file_id`, `module_id`));

CREATE TABLE `ordermgmt_module_rel` (
`parent_id` int NULL,
`child_id` int NULL,
PRIMARY KEY (`parent_id`, `child_id`));

ALTER TABLE `ordermgmt_module_components` ADD CONSTRAINT `component_module_fk` FOREIGN KEY (`module_id`) REFERENCES `ordermgmt_modules` (`module_id`);
ALTER TABLE `ordermgmt_module_files` ADD CONSTRAINT `module_file_fk` FOREIGN KEY (`module_id`) REFERENCES `ordermgmt_modules` (`module_id`);
ALTER TABLE `ordermgmt_module_rel` ADD CONSTRAINT `parent_module_fk` FOREIGN KEY (`parent_id`) REFERENCES `ordermgmt_modules` (`module_id`);
ALTER TABLE `ordermgmt_module_rel` ADD CONSTRAINT `child_module_fk` FOREIGN KEY (`child_id`) REFERENCES `ordermgmt_modules` (`module_id`);