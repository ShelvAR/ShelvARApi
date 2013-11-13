<?php
	include_once $_SERVER['DOCUMENT_ROOT'] . "/database.php";
	include_once $_SERVER['DOCUMENT_ROOT'] . "/header_include.php";
	
	$err = array();	

	if (isset($_POST['submitted'])) // Handle the form.
	{
		if (empty($_POST['user_id'])) // Validate the email address.
		{	
			$uid = FALSE;
			echo ‘<p><font color=”red” size=”+1″>You forgot to enter your user name!</font></p>’;
		} 
		else 
		{
			// Check for the existence of that email address.
			$query = “SELECT user_id FROM users WHERE user_id=’”.  escape_data($_POST['user_id']) . “‘”;
			$result = mysql_query ($query) or trigger_error(“Query: $query\n<br />MySQL Error: ” . mysql_error());
			
			if (mysql_num_rows($result) == 1)
			{
				// Retrieve the user ID.
				list($uid) = mysql_fetch_array ($result, MYSQL_NUM);
			} 
			else 
			{
				echo ‘<p><font color=”red” size=”+1″>The submitted user name does not match those on file!</font></p>’;
				$uid = FALSE;
			}
		}
		if ($uid) // If everything is OK.
		{ 
			// Create a new, random password.
			$p = substr ( md5(uniqid(rand(),1)), 3, 10);
			
			// Make the query.
			$query = “UPDATE users SET pass=SHA(‘$p’) WHERE user_id=$uid”;
			$result = mysql_query ($query) or trigger_error(“Query: $query\n<br />MySQL Error: ” . mysql_error());
			
			if (mysql_affected_rows() == 1)  // If it ran ok
			{
				//Send an email
				$body = “Your password to log into ShelvAR has been temporarily changed to ‘$p’. Please log in using this password and your username. At that time you may change your password to something more familiar.”;
				mail ($_POST['email'], ‘Your temporary password.’, $body, ‘From: admin@shelvar.com’);
				echo ‘<h3>Your password has been changed. You will receive the new, temporary password at the email address with which you registered. Once you have logged in with this password, you may change it by clicking on the “Change Password” link.</h3>’;
				
				mysql_close(); // Close the database connection.
				exit();
			}
			else  //Failed the Validation test
			{
				echo ‘<p><font color=”red” size=”+1″>Please try again.</font></p>’;
			}
		}
	}

echo(
	'<!DOCTYPE html>
	<html lang="en">
	  <head>
		<meta charset="utf-8">
		<title>ShelvAR Forgot Password</title>
		<link href="bootstrap.css" rel="stylesheet">
		<style type="text/css">
			html, body {
				background-color: #C60C30;
			}
			body {
				padding-top: 40px; 
			}
			
			.container {
				width: 600px;
			}

			.container > .content {
				background-color: #fff;
				padding: 20px;
				margin: 0 -20px; 
				-webkit-border-radius: 10px 10px 10px 10px;
				   -moz-border-radius: 10px 10px 10px 10px;
						border-radius: 10px 10px 10px 10px;
				-webkit-box-shadow: 0 1px 2px rgba(0,0,0,.15);
				   -moz-box-shadow: 0 1px 2px rgba(0,0,0,.15);
						box-shadow: 0 1px 2px rgba(0,0,0,.15);
			}
			
			img {
				max-width: 100%;
				width: auto	9;
				height: auto;
				border: 0;
				-ms-interpolation-mode: bicubic;
				margin-left: -3%;
			}

			.login-form {
				margin-left: 25px;
			}

			legend {
				margin-right: -50px;
				font-weight: bold;
				color: #404040;
			}
		</style>
	</head>
	<body>
	  <div class="container">
		<img src="../ShelvARLogo_Big.png" width="200"/>
		<br/>
		<br/>
		<div class="content">
		  <div class="row">
			<div class="login-form">
			  <h3>Please enter user name and select forgot password</h3>
			    <form method="POST" action="?oauth_token='.$_GET["oauth_token"].'">
				<fieldset>
				  <div class="control-group">
					<input type="text" class="input-xlarge" name="user_id" placeholder="Username">
				  </div>
				  <input type="hidden" id="login" name="login" value="login" />
				  <button class="btn btn-primary" type="submit">Forgot Password</button>
				</fieldset>
			  </form>
			</div> <!-- form -->
		  </div> <!-- row -->');

if(count($err)){
  echo('<div class="row"><div class="login-form">');
  echo('<h3>Errors</h3>');
  //print_r($err);
  foreach($err as $key => $value){
    echo("<p>" . $value . "</p>");
  }
  echo('</div></div>');
 }

echo ('
		</div> <!-- content -->
	  </div> <!-- container -->
	</body>
	</html>'
); 	
?>	