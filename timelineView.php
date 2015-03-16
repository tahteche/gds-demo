<!DOCTYPE html>
<html>
  <head>
    <title>Timelinator</title>
    <link rel="stylesheet" type="text/css" href="static/style.css" />
  </head>

  <body>
    <p><a href="logout">Log Out</a></p>
    <h2> <?php echo 'Hello, ' . htmlspecialchars($user->getNickname()) . '.'; ?> 
      Welcome to Timelinator - The killer timeline app :-)</h2>
    <form action="timeline" method="post">
      <p><label for="status"> What's up? </label> <input type="text" id="status" name="status" /></p>
      <!-- <p><label for="person"> Who are you? </label> <input type="text" id="person" name="person" /></p> -->
      <input value="Submit" type="submit"/>
    </form>
  </body>

</html> 