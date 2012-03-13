ALTER TABLE `requisition_status` DROP FOREIGN KEY `req_status_fk`;
ALTER TABLE `requisition_status` DROP FOREIGN KEY `status_statusinfo_fk`;
ALTER TABLE `requisition_files` DROP FOREIGN KEY `file_requisition_fk`;
ALTER TABLE `requisition_components` DROP FOREIGN KEY `requisition_component_fk`;

RENAME TABLE `requisition_components` TO `[PREFIX]_components`;
RENAME TABLE `requisition_files` TO `[PREFIX]_files`;
RENAME TABLE `requisition_status` TO `[PREFIX]_status`;
RENAME TABLE `requisition_status_info` TO `[PREFIX]_status_info`;
RENAME TABLE `requisitions` TO `[PREFIX]`;

ALTER TABLE `[PREFIX]`
CHANGE COLUMN `requisition_id` `order_id`  int(11) NOT NULL COMMENT 'Identifying row' FIRST ,
CHANGE COLUMN `requisitioned_by` `ordered_by`  int(11) NOT NULL COMMENT 'The userid of the person that generated this requisition' AFTER `order_id`,
CHANGE COLUMN `project` `main_project`  int(11) NULL DEFAULT NULL COMMENT 'The id of the project this order belongs to' AFTER `company`,
ADD COLUMN `notes`  text NULL AFTER `main_project`;

ALTER TABLE `[PREFIX]_components`
MODIFY COLUMN `component_price`  float(11,2) NOT NULL AFTER `component_id`,
CHANGE COLUMN `requisition_id` `order_id`  int(11) NOT NULL AFTER `component_description`,
ADD COLUMN `project`  int NULL AFTER `component_description`;

ALTER TABLE `[PREFIX]_files`
CHANGE COLUMN `requisition_id` `order_id`  int(11) NOT NULL COMMENT 'Forign key used to reference the requisitions table to assosiate a file with a requisition.' AFTER `file_id`;

ALTER TABLE `[PREFIX]_status`
CHANGE COLUMN `requisition_status_id` `order_status_id`  int(11) NOT NULL FIRST ,
CHANGE COLUMN `requisition_id` `order_id`  int(11) NOT NULL AFTER `order_status_id`;

ALTER TABLE `[PREFIX]_status_info`
CHANGE COLUMN `requisition_status_info_id` `order_status_info_id`  int(11) NOT NULL FIRST ;

CREATE TABLE `[PREFIX]_default_components` (
`component_id` int(11) NOT NULL,
`catalog_number` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
`brand` varchar(30) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
`supplier` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
`discount` float NULL DEFAULT NULL,
`vendor_price` float NOT NULL,
`exchange_rate` float NOT NULL,
`local_price` float NOT NULL,
`quote_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
`description` mediumtext CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
`notes` mediumtext CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
PRIMARY KEY (`component_id`) 
);