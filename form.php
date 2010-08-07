<?php
/*
 * vim:et sts=2 sw=2 ai nocindent:
 * by Shawn Allen, shawn at stamen dot com
 *
 * NOTE: This file is not intended to work standalone, but is included by 
 * index.php.
 */
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

      <?php if (isset($max_form_size)): ?>
      <input type="hidden" name="MAX_FILE_SIZE" value="<?= $max_form_size ?>"/>
      <?php endif; ?>

      <h2>CSV Input</h2>
      <ol>
        <li id="upload">
          <label>Upload a file (max size: <strong><?= $max_upload_size ?></strong>):
          <input type="file" name="upload" /></label>
          <em class="either-or">or</em>
        </li>
        <li id="post">
          <p><label for="csv">Paste your data here:</label></p>
          <textarea name="csv" cols="50" rows="5"><?= $input ?></textarea>
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
            <input id="delimiter" name="delimiter" type="text" size="1" value="<?= escape($delimiter) ?>"/></label></li>
        <li><label>Columns quoted with:
            <input name="quotechar" type="text" size="1" value="<?= escape($quotechar) ?>"/></label></li>
      </ul>
      <h3>Output Options</h3>
      <ul>
        <li><label>JSON-P callback function:
            <input name="callback" type="text" size="16" value="<?= escape($callback) ?>"/></label>
            <em class="either-or">or</em>
        </li>
        <li><label>Assign to JavaScript variable name:
            <input name="variable" type="text" size="16" value="<?= escape($variable) ?>"/></label></li>
        <li><label>Indent JSON by
            <input name="indent" type="text" size="1" value="<?= escape($indent) ?>"/> spaces</label></li>
        <li><label><input type="checkbox" name="download" value="true" <?php if ($download) print 'checked="checked"'; ?> />
            Download as file (rather than displaying in a text field).</label>
            <div>File name: <input name="download_name" type="text" size="16" value="<?= escape($output_filename) ?>" /></div></li>
      </ul>
      <p class="submit"><input type="submit" text="Submit"/> <input type="reset" value="Reset"/></p>
    </form>

    <?php if ($_SERVER['REQUEST_METHOD'] == 'POST'): ?>
    <form id="output">

      <h2>JSON Output</h2>

      <?php if (!empty($error)): ?>
      <p class="error"><strong><?= $error ?></strong></p>
      <?php endif; ?>

      <?php if (!empty($cmd)): ?>
      <pre id="cmd">$ <?= $cmd ?></pre>
      <?php endif; ?>

      <?php if (!empty($json)): ?>
      <p class="patience"><em>Be patient. Writing <?= pretty_size(mb_strlen($json)) ?> of JSON...</em></p>
      <textarea id="output" cols="50" rows="32"><?= escape(trim($json), false) ?></textarea>
      <?php endif; ?>

    </form>
    <?php endif; ?>

  </body>
</html>
