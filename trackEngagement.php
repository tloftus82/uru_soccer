<?php
// Engagement beacon — receives time-on-page, scroll depth, video plays, link clicks from JS
// Called via navigator.sendBeacon() on page unload and immediately on video play / link click.

include __DIR__ . '/dbConnect/dbConnect.inc.php';

// Accept both POST (sendBeacon FormData) and GET (Image fallback)
$req = array_merge($_GET, $_POST);

$id           = intval($req['id']           ?? 0);
$timeOnPage   = isset($req['time_on_page'])  ? intval($req['time_on_page'])  : -1;
$scrollDepth  = isset($req['scroll_depth'])  ? intval($req['scroll_depth'])  : -1;
$videoPlayed  = intval($req['video_played']  ?? 0);
$linkClicked  = substr(trim($req['link_clicked'] ?? ''), 0, 300);

if ($id <= 0) { http_response_code(400); exit; }

// Clamp values to sane ranges
$timeOnPage  = max(-1, min($timeOnPage,  86400));
$scrollDepth = max(-1, min($scrollDepth, 100));
$videoPlayed = $videoPlayed ? 1 : 0;

mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS TIME_ON_PAGE   SMALLINT     UNSIGNED NULL");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS SCROLL_DEPTH   TINYINT      UNSIGNED NULL");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS VIDEO_PLAYED   TINYINT      UNSIGNED NULL DEFAULT 0");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS LINKS_CLICKED  VARCHAR(2000) NULL");

// Handle link click — append to comma-separated list, avoid duplicates
if ($linkClicked !== '') {
    $lc_e = mysqli_real_escape_string($cn, $linkClicked);
    mysqli_query($cn, "UPDATE PP_VIEW_LOG
        SET LINKS_CLICKED = CASE
            WHEN LINKS_CLICKED IS NULL OR LINKS_CLICKED = '' THEN '$lc_e'
            WHEN FIND_IN_SET('$lc_e', LINKS_CLICKED) THEN LINKS_CLICKED
            ELSE CONCAT(LINKS_CLICKED, ',', '$lc_e')
        END
        WHERE ID = $id LIMIT 1");
}

// Build SET clause for numeric fields
$setParts = [];
if ($timeOnPage  >= 0) $setParts[] = "TIME_ON_PAGE = $timeOnPage";
if ($scrollDepth >= 0) $setParts[] = "SCROLL_DEPTH = GREATEST(IFNULL(SCROLL_DEPTH,0), $scrollDepth)";
if ($videoPlayed)      $setParts[] = "VIDEO_PLAYED = 1";

if (!empty($setParts)) {
    $set = implode(', ', $setParts);
    mysqli_query($cn, "UPDATE PP_VIEW_LOG SET $set WHERE ID = $id LIMIT 1");
}

http_response_code(204);
