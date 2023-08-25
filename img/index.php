<?php 
session_start();
?>
<!DOCTYPE html>
<head>
	<title>Loot Hunt</title>
	<style type="text/css"></style>
	<!--<meta name=viewport content='width=500'>-->
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php 
	echo file_get_contents("../templates/tracking.html");
	?>
	<style>
		html {
  font-family: sans-serif;
}

form {
  background: #ccc;
  margin: 0 auto;
  padding: 20px;
  border: 1px solid black;
}

form ol {
  padding-left: 0;
}

form li, div > p {
  background: #eee;
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
  list-style-type: none;
  border: 1px solid black;
}

form img {
  height: 64px;
  order: 1;
}

form p {
  line-height: 32px;
  padding-left: 10px;
}

form label, form button {
  background-color: #33c3f0;
  padding: 5px 10px;
  border-radius: 5px;
  border: 1px ridge black;
  font-size: 0.8rem;
  height: auto;
}

form label:hover, form button:hover {
  background-color: #f06033;
  color: white;
}

form label:active, form button:active {
  background-color: #0D3F8F;
  color: white;
}
	</style>
</head>
<html>
<body>

<form action="upload.php" method="post" enctype="multipart/form-data">
  <div>
    <label for="fileToUpload">Choose images to upload (PNG, JPG, GIF)</label>
    <input type="file" name="fileToUpload" id="fileToUpload"  accept=".jpg, .jpeg, .png, .gif">
  </div>
  <div class="preview">
    <p>No files currently selected for upload</p>
  </div>
  <div>
    <button>Submit</button>
  </div>
</form>


<script type="text/javascript">
var input = document.querySelector('input');
var preview = document.querySelector('.preview');

input.style.opacity = 0;input.addEventListener('change', updateImageDisplay);function updateImageDisplay() {
  while(preview.firstChild) {
    preview.removeChild(preview.firstChild);
  }

  var curFiles = input.files;
  if(curFiles.length === 0) {
    var para = document.createElement('p');
    para.textContent = 'No files currently selected for upload';
    preview.appendChild(para);
  } else {
    var list = document.createElement('ol');
    preview.appendChild(list);
    for(var i = 0; i < curFiles.length; i++) {
      var listItem = document.createElement('li');
      var para = document.createElement('p');
      if(validFileType(curFiles[i])) {
        para.textContent = 'File name ' + curFiles[i].name + ', file size ' + returnFileSize(curFiles[i].size) + '.';
        var image = document.createElement('img');
        image.src = window.URL.createObjectURL(curFiles[i]);

        listItem.appendChild(image);
        listItem.appendChild(para);

      } else {
        para.textContent = 'File name ' + curFiles[i].name + ': Not a valid file type. Update your selection.';
        listItem.appendChild(para);
      }

      list.appendChild(listItem);
    }
  }
}var fileTypes = [
  'image/jpeg',
  'image/pjpeg',
  'image/png',
  'image/gif'
]

function validFileType(file) {
  for(var i = 0; i < fileTypes.length; i++) {
    if(file.type === fileTypes[i]) {
      return true;
    }
  }

  return false;
}function returnFileSize(number) {
  if(number < 1024) {
    return number + 'bytes';
  } else if(number > 1024 && number < 1048576) {
    return (number/1024).toFixed(1) + 'KB';
  } else if(number > 1048576 && number < 5242880) {
    return (number/1048576).toFixed(1) + 'MB';
  } else if(number > 5242880) {
    return (number/1048576).toFixed(1) + 'MB - Files must be under 5MB';
  }
}
</script>
</body>
</html>