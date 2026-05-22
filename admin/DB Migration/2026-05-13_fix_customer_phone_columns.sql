-- Fix customer phone fields so leading zeros are preserved
ALTER TABLE `customer`
  MODIFY `customer_tell` VARCHAR(50) DEFAULT NULL,
  MODIFY `customer_mobile` VARCHAR(50) DEFAULT NULL;
