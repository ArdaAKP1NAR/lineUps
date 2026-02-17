<?php
/**
 * Veritabanı bağlantısı - adminpanel/Db kullanır
 */
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'adminpanel' . DIRECTORY_SEPARATOR . 'Db.php';

$pdo = Db::get();
