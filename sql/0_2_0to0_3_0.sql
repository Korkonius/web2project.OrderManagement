ALTER TABLE `[PREFIX]_default_components` MODIFY COLUMN `component_id`  int(11) NOT NULL AUTO_INCREMENT FIRST ;
ALTER TABLE `[PREFIX]_default_components` MODIFY COLUMN `notes` mediumtext CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL;
ALTER TABLE `[PREFIX]_default_components` ADD COLUMN `wet_material`  tinytext NULL AFTER `catalog_number`;
ALTER TABLE `[PREFIX]_default_components` ADD COLUMN `vendor_currency`  tinytext NULL AFTER `vendor_price`;

CREATE TABLE `[PREFIX]_deliveries` (
  `delivery_id` int(11) NOT NULL COMMENT 'The unique identificator for order deliveries',
  `order_id` int(11) NOT NULL COMMENT 'Reference to the order id',
  `start_date` datetime NOT NULL COMMENT 'When to start expecting a delivery',
  `end_date` datetime NOT NULL COMMENT 'The delivery should arrive before this date',
  `company` int(11) NOT NULL COMMENT 'Company responsible for the delivery',
  `arrived` datetime DEFAULT NULL COMMENT '1 if the delivery has arrived 0 otherwise',
  PRIMARY KEY (`delivery_id`)
) ENGINE=InnoDB;
