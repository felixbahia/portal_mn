<?php
$file = "."."$caminho";

header('Content-type: application/pdf');
readfile($file);
exit(0);
?>

