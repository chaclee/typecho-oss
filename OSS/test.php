<?php
require_once('sdk.class.php');

$obj = new ALIOSS('FkCOMLBf3x56cTXV','OQ1pODKZLGWTxhVnWZNbZ7ALNg8ITE','oss-cn-beijing.aliyuncs.com');

$response = $obj->list_bucket();
print_r($response);