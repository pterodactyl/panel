
<html>
    <head>
        <title>Pterodactyl Lost Password Recovery</title>
    </head>
    <body>
        <center><h1>Pterodactyl Lost Password Recovery</h1></center>
        <p>Hello there! You are receiving this email because you requested a new password for your Pterodactyl account.</p>
        <p>Please click the link below to confirm that you wish to change your password. If you did not make this request, or do not wish to continue simply ignore this email and nothing will happen. <strong>This link will expire in 1 hour.</strong></p>
        <p><a href="{{ url('auth/password/verify/'.$token) }}">{{ url('auth/password/verify/'.$token) }}</a></p>
        <p>Please do not hesitate to contact us if you belive something is wrong.
        <p>Thanks!<br />Pterodactyl</p>
    </body>
</html>