<?php
exec('/home/u929623538/domains/cibilrepair.in/public_html/auto-deploy.sh 2>&1', $output);
echo implode("\n", $output);
?>
