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

// if using an uploaded file, grab its temp file name
if (isset($_FILES['upload']) && !empty($_FILES['upload']['name'])) {

  $filename = $_FILES['upload']['tmp_name'];

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
