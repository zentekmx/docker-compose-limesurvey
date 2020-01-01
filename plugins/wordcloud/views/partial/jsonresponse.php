<?php
/**
 * Subview: JSON renderer
 * 
 * @package WordCloud
 * @author Markus Flür <markus.fluer@limesurvey.org>
 * @license GPL3.0
 */
?>
<?php
$JSON = json_encode($data, JSON_UNESCAPED_UNICODE);
if($JSON) {
    header("Content-Type: application/json");
    echo $JSON;
} else {
    header("JSON could not be created", true, 500);
    echo json_last_error_msg();
}
?>