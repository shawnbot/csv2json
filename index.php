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
$output_filename = option('download_name', 'data.json');

if ($filename) {

  $opts = array('F' => $delimiter,
                'q' => $quotechar,
                'i' => $indent,
                'v' => $variable);
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

if ($download == true):

  print $json;

else:

?>
<html>
  <head>
    <title>CSV &rarr; JSON</title>
    <style type="text/css">@import url(style.css);</style>
  </head>
  <body>
    <h1>CSV &rarr; JSON</h1>
    <form id="input" action="<?= $_SERVER['REQUEST_URI'] ?>"
      method="POST" enctype="multipart/form-data">
      <h2>CSV Input</h2>
      <ol>
        <li id="upload">
          <label>Upload a file (max size: <strong><?= pretty_size(ini_get('upload_max_filesize')) ?></strong>): <input type="file" name="upload"/></label>
          <em class="either-or">or</em>
        </li>
        <li id="post">
          <p><label for="csv">Paste your data here:</label></p>
          <textarea name="csv" cols="50" rows="5"><?= $_POST['csv'] ?></textarea>
        </li>
      </ol>

      <h3>Input Options</h3>
      <ul>
        <li><label>Columns separated by:
            <select name="delimiter_select" onchange="
            if (this.selectedIndex < this.options.length - 1) {
              document.getElementById('delimiter').value = this.options[this.selectedIndex].value;
            }
            ">
              <option value=",">,</option>
              <option value=";">;</option>
              <option value="tab">tab</option>
              <option value="|">|</option>
              <option value="" selected="selected">other:</option>
            </select>
            <input id="delimiter" name="delimiter" type="text" size="1" value="<?= htmlspecialchars($delimiter) ?>"/></label></li>
        <li><label>Columns quoted with:
            <input name="quotechar" type="text" size="1" value="<?= htmlspecialchars($quotechar) ?>"/></label></li>
      </ul>
      <h3>Output Options</h3>
      <ul>
        <li><label>Indent JSON by
            <input name="indent" type="text" size="1" value="<?= htmlspecialchars($indent) ?>"/> spaces</label></li>
        <li><label>Assign to JavaScript variable name:
            <input name="variable" type="text" size="1" value="<?= htmlspecialchars($variable) ?>"/></label></li>
        <li><label><input type="checkbox" name="download" value="true" <?php if ($download) print 'checked="checked"'; ?> />
            Download as file (rather than displaying in a text field).</label>
            <div>File name: <input name="download_name" type="text" value="<?= htmlspecialchars($output_filename) ?>" /></div></li>
      </ul>
      <p class="submit"><input type="submit" text="Submit"/> <input type="reset" value="Reset"/></p>
    </form>

    <?php if ($_SERVER['REQUEST_METHOD'] == 'POST'): ?>
    <form id="output">
      <h2>JSON Output</h2>
      <pre id="cmd">$ <?= $cmd ?></pre>
      <?php if (isset($json) && !empty($json)): ?>
      <p id="patience"><em>Be patient. Writing <?= pretty_size(mb_strlen($json)) ?> of JSON...</em></p>
      <textarea id="output" cols="50" rows="32"><?= trim($json) ?></textarea>
      <?php else: ?>
      <?php endif; ?>
    </form>
    <?php endif; ?>

  </body>
</html>
<?php

endif;

// grab the buffer, send the Content-Length header, and print
$output = ob_get_contents();
ob_end_clean();
// XXX: not sure if we want mb_strlen() here or just strlen()...
header(sprintf('Content-Length: %d', mb_strlen($output)));
print $output;

?>
