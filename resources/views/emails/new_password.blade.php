
<html>
	<head>
		<title>Pterodactyl - Admin Reset Password</title>
	</head>
	<body>
		<center><h1>Pterodactyl - Admin Reset Password</h1></center>
		<p>Hello there! You are receiving this email because an admin has reset the password on your Pterodactyl account.</p>
		<p><strong>Login:</strong> <a href="{{ config('app.url') }}/auth/login">{{ config('app.url') }}/auth/login</a><br>
			<strong>Email:</strong> {{ $user->email }}<br>
			<strong>Password:</strong> {{ $password }}</p>
		<p>Thanks,<br>Pterodactyl</p>
	</body>
</html>