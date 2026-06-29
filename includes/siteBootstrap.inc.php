<?php
// Central bootstrap: DB connection + URU_VARIABLES load
// Include this instead of manually calling dbConnect + fetching URU_VARIABLES on each page.
if (!isset($cn)) {
    include(__DIR__ . '/../dbConnect/dbConnect.inc.php');
}
if (!isset($_uruVars)) {
    $_uruVars = [];
    $__vr = mysqli_query($cn, "SELECT VAR_KEY, VAR_VALUE FROM URU_VARIABLES");
    if ($__vr) while ($__vrow = mysqli_fetch_assoc($__vr)) $_uruVars[$__vrow['VAR_KEY']] = $__vrow['VAR_VALUE'];
}
