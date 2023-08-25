<?php

if(isset($_POST['hash'])){
  $hash = str_replace('#','',$_POST['hash']);
  header('Location: '.$home.'trade/'.$hash);
  die();
}