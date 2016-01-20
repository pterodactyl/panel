{{--
    Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
    Some Modifications (c) 2015 Dylan Seidt <dylan.seidt@gmail.com>

    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is
    furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in all
    copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
    SOFTWARE.
--}}
<html>
    <head>
        <title>Pterodactyl Lost Password Recovery</title>
    </head>
    <body>
        <center><h1>Pterodactyl Lost Password Recovery</h1></center>
        <p>Hello there! You are receiving this email because you requested a new password for your Pterodactyl account.</p>
        <p>Please click the link below to confirm that you wish to change your password. If you did not make this request, or do not wish to continue simply ignore this email and nothing will happen. <strong>This link will expire in 1 hour.</strong></p>
        <p><a href="{{ url('auth/password/reset/'.$token) }}">{{ url('auth/password/reset/'.$token) }}</a></p>
        <p>Please do not hesitate to contact us if you belive something is wrong.
        <p>Thanks!<br />Pterodactyl</p>
    </body>
</html>
