<?php
$nomail = true;
require_once '../vendor/autoload.php';
require_once '../app/autoload.php';
$auth = checkLogin();
$transport = 'https://';
if (!isset($_SERVER['HTTPS'])) $transport = 'http://';
$conn = new mysqli($servername, $username, $password, $database);
$public = makeToken(25);
$secret = makeToken(25);
$resize = true; // SHOULD WE RESIZE
$target_dir = "uploads/"; // DIRECTORY OF UPLOADS
$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
$uploadOk = 1;
$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
if ($imageFileType == "gif") $resize = false;
$saveFile = $target_dir . $secret.".".$imageFileType;
// Check if image file is a actual image or fake image
if(isset($_POST["submit"])) { 
	$check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
	if($check !== false) {
		echo "File is an image - " . $check["mime"] . ". ";
		$uploadOk = 1;
	} else {
		echo "File is not an image. ";
		$uploadOk = 0;
	}
}
// Check if file already exists
if (file_exists($target_file)) {
	echo "Sorry, file already exists. ";
	$uploadOk = 0;
}
// Check file size
if ($_FILES["fileToUpload"]["size"] > 10500000) {
	echo "Sorry, your file is too large. ";
	$uploadOk = 0;
}
// Allow certain file formats
if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
	&& $imageFileType != "gif" ) {
	echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed. ";
$uploadOk = 0;
}
// Check if $uploadOk is set to 0 by an error
if ($uploadOk == 0) {
	echo "Sorry, your file was not uploaded.";
// if everything is ok, try to upload file
} else {
	if (intval($_SESSION["update"]) == 0){
		if ($auth){
			$detail = (array) jwtDecode($_SESSION['user']);
			$name = $detail['user'];
		}
		else die("Not logged in");
		$update = "UPDATE `players` SET `img` = '$public' WHERE `display` = '".$name."'";
	}
	if (intval($_SESSION["update"]) > 0){
		$sql = "SELECT * FROM `players` WHERE `token` = '".$_COOKIE["user"]."'";
		if ($auth){
			$detail = (array) jwtDecode($_SESSION['user']);
			$name = $detail['user'];
		}
		else die("Not logged in");
		$update = "UPDATE `packages` SET `img` = '$public' WHERE `mayor` = '".$name."' AND `id` = '".$_SESSION['update']."'";
	}

	if ($resize == true){
		if ($resized = smart_resize_image($_FILES["fileToUpload"]["tmp_name"], null, 300, 600, true, $saveFile, false, false, 100 )) {
			$info  = getimagesize($saveFile);
			$width = $info[0];
			$height = $info[1];
			$tags = strtok($_FILES["fileToUpload"]["name"], '.');
			
			$insert = "INSERT INTO `images` (`public`, `private`, `tags`, `height`, `width`, `ext`, `hits`)
			VALUES ('$public', '$secret', '$tags', '$height', '$width', '$imageFileType', '0')";
			mysqli_query($conn, $insert);
			echo $conn->error;
			mysqli_query($conn, $update);
			echo $conn->error;
			mysqli_close($conn);

			echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded. <br><a href='javascript:parent.location.href=parent.location.href'>Change image</a><br>";
			echo file_get_contents($transport.$_SERVER['HTTP_HOST'].$GLOBALS['home']."store/!".$public);
		} else {
			echo "Sorry, there was an error uploading your file. ";
		}
	}
	else{
		if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $saveFile)) {
			$info  = getimagesize($saveFile);
			$width = $info[0];
			$height = $info[1];
			$tags = strtok($_FILES["fileToUpload"]["name"], '.');
			
			$insert = "INSERT INTO `images` (`public`, `private`, `tags`, `height`, `width`, `ext`, `hits`)
			VALUES ('$public', '$secret', '$tags', '$height', '$width', '$imageFileType', '0')";
			mysqli_query($conn, $insert);
			echo $conn->error;
			mysqli_query($conn, $update);
			echo $conn->error;
			mysqli_close($conn);
			
			echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded. <br><a href='javascript:parent.location.href=parent.location.href'>Change image</a><br>";
			echo file_get_contents($transport.$_SERVER['HTTP_HOST'].$GLOBALS['home']."store/!".$public);
		} else {
			echo "Sorry, there was an error uploading your file. ";
		}
	}
}


function smart_resize_image($file,
	$string             = null,
	$width              = 0, 
	$height             = 0, 
	$proportional       = false, 
	$output             = 'file', 
	$delete_original    = true, 
	$use_linux_commands = false,
	$quality            = 100
) {
	
	if ( $height <= 0 && $width <= 0 ) return false;
	if ( $file === null && $string === null ) return false;
	
		# Setting defaults and meta
	$info                         = $file !== null ? getimagesize($file) : getimagesizefromstring($string);
	$image                        = '';
	$final_width                  = 0;
	$final_height                 = 0;
	list($width_old, $height_old) = $info;
	$cropHeight = $cropWidth      = 0;

	if($info[2] == IMAGETYPE_JPEG){
		$exif = exif_read_data($file);
	}
	
		# Calculating proportionality
	if ($proportional) {
		if      ($width  == 0)  $factor = $height/$height_old;
		elseif  ($height == 0)  $factor = $width/$width_old;
		else                    $factor = min( $width / $width_old, $height / $height_old );
		
		$final_width  = round( $width_old * $factor );
		$final_height = round( $height_old * $factor );
	}
	else {
		$final_width = ( $width <= 0 ) ? $width_old : $width;
		$final_height = ( $height <= 0 ) ? $height_old : $height;
		$widthX = $width_old / $width;
		$heightX = $height_old / $height;
		
		$x = min($widthX, $heightX);
		$cropWidth = ($width_old - $width * $x) / 2;
		$cropHeight = ($height_old - $height * $x) / 2;
	}
	
		# Loading image to memory according to type
	switch ( $info[2] ) {
		case IMAGETYPE_JPEG:  $file !== null ? $image = imagecreatefromjpeg($file) : $image = imagecreatefromstring($string);  break;
		case IMAGETYPE_GIF:   $file !== null ? $image = imagecreatefromgif($file)  : $image = imagecreatefromstring($string);  break;
		case IMAGETYPE_PNG:   $file !== null ? $image = imagecreatefrompng($file)  : $image = imagecreatefromstring($string);  break;
		default: return false;
	}
	
	
		# This is the resizing/resampling/transparency-preserving magic
	$image_resized = imagecreatetruecolor( $final_width, $final_height );
	if ( ($info[2] == IMAGETYPE_GIF) || ($info[2] == IMAGETYPE_PNG) ) {
		$transparency = imagecolortransparent($image);
		$palletsize = imagecolorstotal($image);
		
		if ($transparency >= 0 && $transparency < $palletsize) {
			$transparent_color  = imagecolorsforindex($image, $transparency);
			$transparency       = imagecolorallocate($image_resized, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
			imagefill($image_resized, 0, 0, $transparency);
			imagecolortransparent($image_resized, $transparency);
		}
		elseif ($info[2] == IMAGETYPE_PNG) {
			imagealphablending($image_resized, false);
			$color = imagecolorallocatealpha($image_resized, 0, 0, 0, 127);
			imagefill($image_resized, 0, 0, $color);
			imagesavealpha($image_resized, true);
		}
	}
	imagecopyresampled($image_resized, $image, 0, 0, $cropWidth, $cropHeight, $final_width, $final_height, $width_old - 2 * $cropWidth, $height_old - 2 * $cropHeight);
	
	
		# Taking care of original, if needed
	if ( $delete_original ) {
		if ( $use_linux_commands ) exec('rm '.$file);
		else @unlink($file);
	}
	
		# Preparing a method of providing result
	switch ( strtolower($output) ) {
		case 'browser':
		$mime = image_type_to_mime_type($info[2]);
		header("Content-type: $mime");
		$output = NULL;
		break;
		case 'file':
		$output = $file;
		break;
		case 'return':
		return $image_resized;
		break;
		default:
		break;
	}
	
	$orientation = false;
	if (isset($exif['Orientation'])) $orientation = $exif['Orientation'];

	switch ($orientation) {
        case 3:
        $image_resized = imagerotate($image_resized, 180, 0);
        break;
        case 6:
        $image_resized = imagerotate($image_resized, -90, 0);
        break;
        case 8:
        $image_resized = imagerotate($image_resized, 90, 0);
        break;
        default:
        $image_resized = $image_resized;
    } 

		# Writing image according to type to the output destination and image quality
	switch ( $info[2] ) {
		case IMAGETYPE_GIF:   imagegif($image_resized, $output);    break;
		case IMAGETYPE_JPEG:  imagejpeg($image_resized, $output, $quality);   break;
		case IMAGETYPE_PNG:
		$quality = 9 - (int)((0.9*$quality)/10.0);
		imagepng($image_resized, $output, $quality);
		break;
		default: return false;
	}
	
	return true;
}

?>