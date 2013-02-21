<?php
ini_set('memory_limit', '4000M');
ini_set('max_execution_time', '100000');
ini_set('innodb_lock_wait_timeout', '100000');
try { 
require 'app/Mage.php';

$importFile = 'import.csv';
$resultFile = 'result.sql';
$delimeter = "\t"; // can be a ","

if (!Mage::app()->isInstalled()) {
    echo "Application is not installed yet, please complete install wizard first.";
    exit(1);
}


$connection = Mage::getModel('catalog/product')->getCollection()->getConnection();

$filename = Mage::getBaseDir() . '/' . $importFile;
$result = Mage::getBaseDir() . '/' . $resultFile;

$available_types = array('int', 'text', 'varchar', 'decimal', 'datetime'); // available attribute types

$attributes = Mage::getModel('eav/config')->getEntityAttributeCodes('catalog_product');

$sql = array();
$output = '';
$row = 1;
$handle = fopen($filename, "r");

while (($data = fgetcsv($handle, 1000, $delimeter)) !== FALSE) {
    if ($row == 1) { // attributes names should be in 1st row
        $obj = array();
        $import = array();
        $num = count($data);
        for ($c = 0; $c < $num; $c++) {
            $data[$c] = trim($data[$c]);
            if (in_array($data[$c], $attributes)) {
                $obj[$c] = array(
                    'type' => Mage::getModel('eav/config')->getAttribute('catalog_product', $data[$c])->getBackendType(),
                    'name' => $data[$c],
                    'id' => Mage::getModel('eav/config')->getAttribute('catalog_product', $data[$c])->getId(),
                    'source' => Mage::getModel('eav/config')->getAttribute('catalog_product', $data[$c])->getSource()
                ); // it's an attribute data
                $import[] = $c;
                $sql[$c] = "INSERT INTO catalog_product_entity_" . $obj[$c]['type'] . " VALUES ";
                $data[$c] = trim($data[$c]);
            }
        }
    } else { // and here we have data
        $num = count($data);
        for ($c = 0; $c < $num; $c++) {
            $data[$c] = trim($data[$c]) ; // if you trying to import prices with ",", use that -> str_replace(',','',trim($data[$c])); =)
            if (in_array($c, $import)) {
                if ($obj[$c]['name'] == 'sku') {
                    $sku = $data[$c];
                    $id = Mage::getModel('catalog/product')->getIdBySku($data[$c]);
                    if (!$id) {
                        echo "Can not found product ID by SKU";
                    }
                } else {
                    if (in_array($obj[$c]['type'], $available_types) && trim($data[$c]) != '') {
                        try {
                            $arr = $connection->fetchAll("SELECT count(*) as c FROM catalog_product_entity_" . $obj[$c]['type'] . " WHERE entity_id = " . $id . " AND attribute_id=" . $obj[$c]['id']);
                            if ($obj[$c]['type'] == 'int') {
                                $source = $obj[$c]['source'];
                                foreach ($source->getAllOptions(false) as $option) {
                                    if ($option['label'] == $data[$c]) {
                                        $data[$c] = trim($option['value']);
                                    }
                                }
                            }
                            if ($obj[$c]['type'] == 'int' && strtolower($data[$c]) == 'yes') {
                                $data[$c] = 1;
                            }
                            if ($obj[$c]['type'] == 'int' && strtolower($data[$c]) == 'no') {
                                $data[$c] = 0;
                            }

                            if ($arr[0]['c'] == 0) {
                                $sql[$c] .= " (value_id,4," . $obj[$c]['id'] . ",0," . $id . ",'" . addslashes($data[$c]) . "'),"; // here we have "4" hardcoded. it's a product entity type ID
                            } else {
                            $output .= "UPDATE catalog_product_entity_" . $obj[$c]['type'] . " SET value = '" . addslashes($data[$c]) . "' WHERE entity_id = " . $id . " AND attribute_id=" . $obj[$c]['id'] . "; ## {$obj[$c]['name']}\n"; // attribute code added at the end of each request - to be sure that we are not updating wrong attributes.
                            }
                        } catch (Exception $e) {
                            $str = '';
                            foreach ($data as $k => $d) {
                                $str .= $k . ' => ' . $d . ' ';
                            }
                            Mage::log($sku . ' ' . $id . ' ' . $str . ' ' . $obj[$c]['type'] . '  Exception ' . $e->getMessage());
                        }
                    }
                }
            }
        }
    }
    $row++;
}
fclose($handle);
foreach ($sql as $query) {
    $output .= substr($query, 0, -1) . ";\n";
}

echo $output;
file_put_contents($result, $output);
exit(1);
} catch (Exception $e) { 
echo $e->getMessage();
}

