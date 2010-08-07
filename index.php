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
  $units = array('bytes', 'Kb', 'Mb', 'Gb', 'Tb'); 

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

/*
 * whether to place uploaded file contents in the raw input field
 * (useful for re-submitting content without having to re-select the file from 
 * an upload dialog).
 */
$post_file_input = false;
// if $post_file_input is true, the max number of displayed input lines from 
// uploaded files
$show_max_lines = 10000;

$max_upload_size = ini_get('upload_max_filesize');
if (isset($max_form_size)) $max_upload_size = min($max_form_size, $max_upload_size);
$max_upload_size = pretty_size($max_upload_size);

// if using an uploaded file, grab its temp file name
if (isset($_FILES['upload']) &&
    $_FILES['upload']['error'] != UPLOAD_ERR_NO_FILE) {

  $error_code = $_FILES['upload']['error'];
  if ($error_code == UPLOAD_ERR_OK) {

    $filename = $_FILES['upload']['tmp_name'];

    if ($post_file_input) {
      $fp = fopen($filename, 'r');
      $input = array();
      while (!feof($fp) && count($input) < $show_max_lines) {
        $input[] = fgets($fp, 1024);
      }
      $input = trim(implode("\n", $input));
    }

  } else {

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

  $input = trim($_POST['csv']);

  $filename = tempnam('/tmp', 'upload-csv');
  $fp = fopen($filename, 'wb');
  fwrite($fp, $input);
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
  $output = array();
  $status = 0;
  exec($cmd, $output, $status);
  if ($status == 0) {
    $json = join("\n", $output);
  } else {
    $error = join("\n", $output);
  }

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
