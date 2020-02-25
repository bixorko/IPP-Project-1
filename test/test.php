<?php
$options = getopt("", ["help", "directory:", "recursive", "parse-script:", "int-script:", "parse-only", "int-only"]);
print_r($options);