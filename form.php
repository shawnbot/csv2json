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
