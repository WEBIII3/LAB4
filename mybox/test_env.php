<?php
echo "<h3>🔍 Verificación de variables de entorno desde Apache</h3>";
echo "<pre>";
echo "HOME_PATH = " . getenv("HOME_PATH") . "\n";
echo "DB_USER   = " . getenv("DB_USER") . "\n";
echo "DB_PASS   = " . getenv("DB_PASS") . "\n";
echo "</pre>";
?>
