CREATE TABLE `[PREFIX]` (
`order_id` int(11) NOT NULL COMMENT 'Identifying row',
`ordered_by` int(11) NOT NULL COMMENT 'The userid of the person that generated this requisition',
`company` int(11) NOT NULL COMMENT 'The id of the company related to the order',
`main_project` int(11) NULL DEFAULT NULL COMMENT 'The id of the project this order belongs to',
`notes` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
`date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`order_id`) 
);

CREATE TABLE `[PREFIX]_components` (
`component_id` int(11) NOT NULL,
`component_price` float(11,2) NOT NULL,
`component_amount` int(11) NOT NULL,
`component_description` tinytext CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
`project` int(11) NULL DEFAULT NULL,
`order_id` int(11) NOT NULL,
PRIMARY KEY (`component_id`) ,
INDEX `requisition_component_fk` (`order_id`)
);

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

CREATE TABLE `[PREFIX]_files` (
`file_id` int(11) NOT NULL COMMENT 'Forign key to existing file storage structure, used to identify file.',
`order_id` int(11) NOT NULL COMMENT 'Forign key used to reference the requisitions table to assosiate a file with a requisition.',
PRIMARY KEY (`file_id`, `order_id`) ,
INDEX `file_requisition_fk` (`order_id`)
);

CREATE TABLE `[PREFIX]_status` (
`order_status_id` int(11) NOT NULL,
`order_id` int(11) NOT NULL,
`user_id` int(11) NOT NULL,
`status_id` int(11) NOT NULL,
`date_changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
`comments` mediumtext CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
PRIMARY KEY (`order_status_id`) ,
INDEX `req_status_fk` (`order_id`),
INDEX `status_statusinfo_fk` (`status_id`)
);

CREATE TABLE `[PREFIX]_status_info` (
`order_status_info_id` int(11) NOT NULL,
`status_title` varchar(45) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
`status_information` tinytext CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL,
`preferred_color` varchar(6) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '000000',
`icon_path` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
PRIMARY KEY (`order_status_info_id`) 
);

CREATE TABLE `[PREFIX]_deliveries` (
  `delivery_id` int(11) NOT NULL COMMENT 'The unique identificator for order deliveries',
  `order_id` int(11) NOT NULL COMMENT 'Reference to the order id',
  `start_date` datetime NOT NULL COMMENT 'When to start expecting a delivery',
  `end_date` datetime NOT NULL COMMENT 'The delivery should arrive before this date',
  `company` int(11) NOT NULL COMMENT 'Company responsible for the delivery',
  `arrived` datetime DEFAULT NULL COMMENT '1 if the delivery has arrived 0 otherwise',
  PRIMARY KEY (`delivery_id`)
);

INSERT INTO `[PREFIX]_status_info` VALUES(1,'New','New Order','000000', 'new.png');
INSERT INTO `[PREFIX]_status_info` VALUES(2,'Approved','Order request approved','000000', 'thumb_up.png');
INSERT INTO `[PREFIX]_status_info` VALUES(3,'Denied','Order request denied','000000', 'exclamation.png');
INSERT INTO `[PREFIX]_status_info` VALUES(4,'Pending','Order sent to third party','000000', 'time.png');
INSERT INTO `[PREFIX]_status_info` VALUES(5,'Recieved','Order components have reached their destination','000000','package_go.png');
INSERT INTO `[PREFIX]_status_info` VALUES(6,'Missing','Parts of the original order is missing or damaged','000000', 'error.png');
INSERT INTO `[PREFIX]_status_info` VALUES(7,'Completed','Order is completed','000000', 'accept.png');
INSERT INTO `[PREFIX]_status_info` VALUES(8,'Changed','Order components has changed','000000', 'information.png');