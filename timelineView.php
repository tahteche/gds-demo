<!DOCTYPE html>
<html>
  <head>
    <title>Timelinator</title>
    <link rel="stylesheet" href="static/style.css" />
    <script href="bower_components/webcomponentsjs/webcomponents.min.js"></script>
    <link rel="import" href="bower_components/polymer/polymer.html" />
    <link href="bower_components/core-icons/core-icons.html" rel="import" />
    <link href="bower_components/paper-fab/paper-fab.html" rel="import" />
    <link rel="import" href="bower_components/core-icons/communication-icons.html">
    <script>
    window.onload = function() {
      document.getElementById('post-form').style.display = "none";
    }
    function toggleDisplay() {
      var displayStatus = document.getElementById('post-form').style.display;
      if(displayStatus === "") {
        document.getElementById('post-form').style.display = "none";
      }
      else{
        document.getElementById('post-form').style.display = "";
      }
    }
      </script>
  </head>
  <body>

    <p><a href="logout">Log Out</a></p>
    <h2> <?php echo 'Hello, ' . htmlspecialchars($user->getNickname()) . '.'; ?> 
      Welcome to Timelinator - The timeline app :-)
    </h2>

    <paper-fab id="post-button" icon="communication:comment" onclick="toggleDisplay()" ></paper-fab>


    <form id="post-form" action="<?php echo $upload_url ?>" enctype="multipart/form-data" method="post">
      <p><input placeholder="What's Up?" type="text" id="status" name="status" /></p>
      <p><input placeholder="Upload a Picture" type="file" name="picture" /></p>
      <input id="submit-button" value="Submit" type="submit"/>
    </form>
    <div id="all-post">