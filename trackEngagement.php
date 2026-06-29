<?php
// Engagement beacon — receives time-on-page, scroll depth, video plays, link clicks,
// section visibility, return visit flag from JS.

include __DIR__ . '/dbConnect/dbConnect.inc.php';

// Accept both POST (sendBeacon FormData) and GET (Image fallback)
$req = array_merge($_GET, $_POST);

$id              = intval($req['id']                 ?? 0);
$timeOnPage      = isset($req['time_on_page'])        ? intval($req['time_on_page'])        : -1;
$scrollDepth     = isset($req['scroll_depth'])        ? intval($req['scroll_depth'])        : -1;
$videoPlayed     = intval($req['video_played']        ?? 0);
$videoWatchSecs  = isset($req['video_watch_seconds'])  ? intval($req['video_watch_seconds'])  : -1;
$videosWatched   = substr(trim($req['videos_watched'] ?? ''), 0, 1000);
$linkClicked     = substr(trim($req['link_clicked']   ?? ''), 0, 300);
$sectionsSeen    = substr(trim($req['sections_seen']  ?? ''), 0, 200);
$sectionTimes    = substr(trim($req['section_times']  ?? ''), 0, 500);
$isReturnVisit   = intval($req['is_return_visit']     ?? 0);

if ($id <= 0) { http_response_code(400); exit; }

// Clamp numeric values
$timeOnPage     = max(-1, min($timeOnPage,     86400));
$scrollDepth    = max(-1, min($scrollDepth,    100));
$videoPlayed    = $videoPlayed    ? 1 : 0;
$isReturnVisit  = $isReturnVisit  ? 1 : 0;
$videoWatchSecs = max(-1, min($videoWatchSecs, 86400));

// Validate videos_watched is a JSON array
if ($videosWatched !== '') {
    $vwDecoded = @json_decode($videosWatched, true);
    if (!is_array($vwDecoded)) $videosWatched = '';
}
// Validate section_times is JSON
if ($sectionTimes  !== '' && @json_decode($sectionTimes)  === null) $sectionTimes  = '';
// Strip non-alpha/comma from sections_seen
$sectionsSeen = preg_replace('/[^a-zA-Z,& ]/', '', $sectionsSeen);

mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS TIME_ON_PAGE        SMALLINT       UNSIGNED NULL");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS SCROLL_DEPTH        TINYINT        UNSIGNED NULL");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS VIDEO_PLAYED        TINYINT        UNSIGNED NULL DEFAULT 0");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS VIDEO_WATCH_SECONDS SMALLINT       UNSIGNED NULL");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS VIDEOS_WATCHED      VARCHAR(1000)  NULL");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS LINKS_CLICKED       VARCHAR(2000)  NULL");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS SECTIONS_SEEN       VARCHAR(200)   NULL");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS SECTION_TIMES       VARCHAR(500)   NULL");
mysqli_query($cn, "ALTER TABLE PP_VIEW_LOG ADD COLUMN IF NOT EXISTS IS_RETURN_VISIT     TINYINT        UNSIGNED NULL DEFAULT 0");

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

// Build SET clause
$setParts = [];
if ($timeOnPage     >= 0)  $setParts[] = "TIME_ON_PAGE        = $timeOnPage";
if ($scrollDepth    >= 0)  $setParts[] = "SCROLL_DEPTH        = GREATEST(IFNULL(SCROLL_DEPTH,0), $scrollDepth)";
if ($videoPlayed)          $setParts[] = "VIDEO_PLAYED        = 1";
if ($videoWatchSecs >= 0)  $setParts[] = "VIDEO_WATCH_SECONDS = $videoWatchSecs";
if ($isReturnVisit)        $setParts[] = "IS_RETURN_VISIT     = 1";

if ($videosWatched !== '') {
    $vw_e = mysqli_real_escape_string($cn, $videosWatched);
    $setParts[] = "VIDEOS_WATCHED = '$vw_e'";
}
if ($sectionsSeen !== '') {
    $ss_e = mysqli_real_escape_string($cn, $sectionsSeen);
    $setParts[] = "SECTIONS_SEEN = '$ss_e'";
}
if ($sectionTimes !== '') {
    $st_e = mysqli_real_escape_string($cn, $sectionTimes);
    $setParts[] = "SECTION_TIMES = '$st_e'";
}

if (!empty($setParts)) {
    $set = implode(', ', $setParts);
    mysqli_query($cn, "UPDATE PP_VIEW_LOG SET $set WHERE ID = $id LIMIT 1");
}

http_response_code(204);
