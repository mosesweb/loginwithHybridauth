<?php
	/**
	* IMPORTANT:
	*            Modified version of:
  * http://hybridauth.sourceforge.net/userguide/Integrating_HybridAuth_Social_Login.html
	****************************************************************************/

	// start a new session (required for Hybridauth)
	session_start();


  include("connect.php");
  $link = db_connect();

  //echo dirname(__FILE__);
	/*
	* We need this function cause I'm lazy
	**/
	function mysqli_query_excute( $sql )
	{
		global $link;

		$result = mysqli_query( $link, $sql );

		if(  ! $result )
		{
			die( printf( "Error: %s\n", mysqli_error( $link ) ) );
		}

		return $result->fetch_object();
	}

	/*
	* get the user data from database by email and password
	**/
	function get_user_by_email_and_password( $email, $password )
	{
		return mysqli_query_excute( "SELECT * FROM users WHERE email = '$email' AND password = '$password'" );
	}

	/*
	* get the user data from database by provider name and provider user id
	**/
	function get_user_by_provider_and_id( $provider_name, $provider_user_id )
	{
		return mysqli_query_excute( "SELECT * FROM users WHERE hybridauth_provider_name = '$provider_name' AND hybridauth_provider_uid = '$provider_user_id'" );
	}

	/*
	* get the user data from database by provider name and provider user id
	**/
	function create_new_hybridauth_user( $email, $first_name, $last_name, $provider_name, $provider_user_id )
	{
		// let generate a random password for the user
		$password = md5( str_shuffle( "0123456789abcdefghijklmnoABCDEFGHIJ" ) );

		mysqli_query_excute(
			"INSERT INTO users
			(
				email,
				password,
				first_name,
				last_name,
				hybridauth_provider_name,
				hybridauth_provider_uid,
				created_at
			)
			VALUES
			(
				'$email',
				'$password',
				'$first_name',
				'$last_name',
				'$provider_name',
				$provider_user_id,
				NOW()
			)"
		);
	}

	// if page requested by submitting login form
	if( isset( $_REQUEST["email"] ) && isset( $_REQUEST["password"] ) )
	{
		$user_exist = get_user_by_email_and_password( $_REQUEST["email"], $_REQUEST["password"] );

		// user exist?
		if( $user_exist )
		{
			// set the user as connected and redirect him to a home page or something
			$_SESSION["user_connected"] = true;

			header("Location: http://www.localhost/g/");
		}

		// wrong email or password?
		else
		{
			// redirect him to an error page
			header("Location: http://www.localhost/g/err");
		}
	}

	// else, if login page request by clicking a provider button
	elseif( isset( $_REQUEST["provider"] ) )
	{
		// the selected provider
		$provider_name = $_REQUEST["provider"];

		try
		{
			// change the following paths if necessary
      $config   = dirname(__FILE__) . '/social/hybridauth/config.php'; //dirname(__FILE__) . '/library/config.php';
      require_once( dirname(__DIR__) ."/g/social/hybridauth/Hybrid/Auth.php" ); //dirname(__DIR__) . "

			// initialize Hybrid_Auth with a given file
			$hybridauth = new Hybrid_Auth( $config );

			// try to authenticate with the selected provider
			$adapter = $hybridauth->authenticate( $provider_name );

			// then grab the user profile
			$user_profile = $adapter->getUserProfile();
		}

		// something went wrong?
		catch( Exception $e )
		{
			header("Location: http://www.example.com/login-error.php");
		}

		// check if the current user already have authenticated using this provider before
		$user_exist = get_user_by_provider_and_id( $provider_name, $user_profile->identifier );

		// if the used didn't authenticate using the selected provider before
		// we create a new entry on database.users for him
		if( ! $user_exist )
		{
			create_new_hybridauth_user(
				$user_profile->email,
				$user_profile->firstName,
				$user_profile->lastName,
				$provider_name,
				$user_profile->identifier
			);
		}

		// set the user as connected and redirect him
		$_SESSION["user_connected"] = true;

		header("Location: http://www.localhost/g/");
	}
?>
<html>
	<head>
		<title>Simple Social Login Integration - HybridAuth</title>
	</head>
	<body>
		<form method="post" action="login.php">
			<fieldset>
				<fieldset>
					<legend>Sign-in form</legend>
					Email   : <input type="text" name="email" /><br />
					Password: <input type="password" name="password" /><br />

					<input type="submit" value="Sign-in" />
				</fieldset>

				<fieldset>
					<legend>Or use another service</legend>

					<a href="login.php?provider=facebook">Signin with Facebook</a> -
					<a href="login.php?provider=twitter" >Signin with Twitter</a> -
					<a href="login.php?provider=linkedin">Signin with Linkedin</a>
				</fieldset>
			</fieldset>
		</form>
	</body>
</html>
