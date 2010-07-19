<?php
/*
 * vim:et sts=2 sw=2 ai nocindent:
 * by Shawn Allen, shawn at stamen dot com
 */

function option($name, $default) {
  return isset($_POST[$name]) ? $_POST[$name] : $default;
}

// adapted from: http://php.net/filesize
function pretty_size($bytes, $precision=1) { 
  $units = array('b', 'KB', 'MB', 'GB', 'TB'); 

  $bytes = max($bytes, 0);
  $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
  $pow = min($pow, count($units) - 1);

  $bytes /= pow(1024, $pow);

  return sprintf('%s %s', round($bytes, $precision), $units[$pow]);
}

function escape($text, $quotes=true) {
  return htmlspecialchars($text, $quotes ? ENT_QUOTES : ENT_NOQUOTES, 'UTF-8');
}

/*
 * Comment out this line or change the value to specify a local upload file size 
 * limit.
 */
$max_form_size = 100 * 1024; // 100K

$max_upload_size = ini_get('upload_max_filesize');
if (isset($max_form_size)) $max_upload_size = min($max_form_size, $max_upload_size);
$max_upload_size = pretty_size($max_upload_size);

// if using an uploaded file, grab its temp file name
if (isset($_FILES['upload'])) {

  $error_code = $_FILES['upload']['error'];
  if ($error_code == UPLOAD_ERR_OK && !empty($_FILES['upload']['name'])) {

    $filename = $_FILES['upload']['tmp_name'];

  } else if ($error_code != UPLOAD_ERR_OK) {

    switch ($error) {
      case UPLOAD_ERR_INI_SIZE:
        $error = sprintf('Exceeded max upload file size: %s', $max_upload_size);
        break;
      case UPLOAD_ERR_FORM_SIZE:
        $error = sprintf('Exceeded max upload size: %s', $_POST['MAX_FILE_SIZE']);
        break;
      case UPLOAD_ERR_PARTIAL:
        $error = 'File upload incomplete.';
        break;
      case UPLOAD_ERR_NO_TMP_DIR:
        $error = 'Missing temp directory.';
        break;
      case UPLOAD_ERR_CANT_WRITE:
        $error = 'Unable to write to temp directory.';
        break;
      case UPLOAD_ERR_EXTENSION:
        $error = 'A PHP extension stopped the file upload.';
        break;
    }

  }

// if using pasted text, write it to a temp file
} else if ($_POST['csv']) {

  $filename = tempnam('/tmp', 'upload-csv');
  $fp = fopen($filename, 'wb');
  fwrite($fp, trim($_POST['csv']));
  fclose($fp);

}

// our conversion options
$delimiter = option('delimiter', ',');
$quotechar = option('quotechar', '"');
$indent = option('indent', 0);
$variable = option('variable', null);
$download = option('download', false) == 'true';
$callback = option('callback', null);
$output_filename = option('download_name', 'data.json');

if ($filename) {

  $opts = array('F' => $delimiter,
                'q' => $quotechar,
                'i' => $indent,
                'v' => $variable,
                'p' => $callback);
  $options = '';
  foreach ($opts as $o => $v) {
    // the quotechar argument can be empty
    if (!empty($v) || $o == 'q')
      $options .= sprintf(' -%s %s', $o, escapeshellarg($v));
  }
  // shell out the conversion to csv2json.py, capturing stdout
  $cmd = "python csv2json.py $options $filename";
  $json = `$cmd`;

  // if we're downloading, send the appropriate headers
  if (!empty($json) && $download) {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename=' . $output_filename);
  }
}

// buffer output so that we can send an accurate Content-Length header
ob_start();

if ($download == true) {

  print $json;

} else {

  header('Content-type: text/html; charset=UTF-8');
  include('form.php');

}

// grab the buffer, send the Content-Length header, and print
$output = ob_get_contents();
ob_end_clean();
// XXX: not sure if we want mb_strlen() here or just strlen()...
header(sprintf('Content-Length: %d', mb_strlen($output)));
print $output;

?>
