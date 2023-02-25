<?php
include 'functions.php';

if (file_exists('../../install.lock')) {
    exit("The installation has been completed already. Please delete the File 'install.lock' to re-run");
}
?>

<html>
<head>
    <title>Controlpanel.gg installer Script</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <style>
        body {
            background-color: powderblue;
        }

        .card {
            position: absolute;
            top: 50%;
            left: 50%;
            margin-right: -50%;
            transform: translate(-50%, -50%);
            width: 30%;
        }

        .ok {
            color: green;
        }

        .ok::before {
            content: "✔️";
        }

        .notok {
            color: red;
        }

        .notok::before {
            content: "❌";
        }
    </style>
</head>
<body>

<?php
$cardheader = '
        <div class="card card-outline-success bg-dark">
        <div class="card-header text-center">
            <b class="mr-1 text-light">Controlpanel.GG</b>
        </div>
        <div class="card-body bg-light">';

if (! isset($_GET['step'])) {
    if (! file_exists('../../.env')) {
        echo run_console('cp .env.example .env');
    }
    echo $cardheader; ?>
    <p class="login-box-msg">This installer will lead you through the most crucial Steps of Controlpanel.gg`s
        setup</p>
    <p class="<?php echo checkHTTPS() == true ? 'ok' : 'notok'; ?>">HTTPS is required</p>

    <p class="<?php echo checkWriteable() == true ? 'ok' : 'notok'; ?>">Write-permissions on .env-file</p>

    <p class="<?php echo checkPhpVersion() === 'OK' ? 'ok' : 'notok'; ?>"> php
        version: <?php echo phpversion(); ?> (minimum required <?php echo $requirements['minPhp']; ?>)</p>

    <p class="<?php echo getMySQLVersion() === 'OK' ? 'ok' : 'notok'; ?>"> mysql
        version: <?php echo getMySQLVersion(); ?> (minimum required <?php echo $requirements['mysql']; ?>)</p>

    <p class="<?php echo count(checkExtensions()) == 0 ? 'ok' : 'notok'; ?>"> Missing
        php-extentions: <?php echo count(checkExtensions()) == 0 ? 'none' : '';
    foreach (checkExtensions() as $ext) {
        echo $ext.', ';
    }

    echo count(checkExtensions()) == 0 ? '' : '(Proceed anyway)'; ?></p>


    <!-- <p class="<?php echo getZipVersion() === 'OK' ? 'ok' : 'notok'; ?>"> Zip
                version: <?php echo getZipVersion(); ?> </p> -->

    <p class="<?php echo getGitVersion() === 'OK' ? 'ok' : 'notok'; ?>"> Git
        version: <?php echo getGitVersion(); ?> </p>

    <p class="<?php echo getTarVersion() === 'OK' ? 'ok' : 'notok'; ?>"> Tar
        version: <?php echo getTarVersion(); ?> </p>


    <a href="?step=2">
        <button class="btn btn-primary">Lets go</button>
    </a>
    </div>
    </div>

    <?php
}
if (isset($_GET['step']) && $_GET['step'] == 2) {
    echo $cardheader; ?>
<p class="login-box-msg">Lets start with your Database</p>
<?php if (isset($_GET['message'])) {
        echo "<p class='notok'>".$_GET['message'].'</p>';
    } ?>

<form method="POST" enctype="multipart/form-data" class="mb-3"
      action="/install/forms.php" name="checkDB">

    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <div class="custom-control mb-3">
                    <label for="database">Database Driver</label>
                    <input x-model="databasedriver" id="databasedriver" name="databasedriver"
                           type="text" required
                           value="mysql" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <div class="custom-control mb-3">
                    <label for="databasehost">Database Host</label>
                    <input x-model="databasehost" id="databasehost" name="databasehost" type="text"
                           required
                           value="127.0.0.1" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <div class="custom-control mb-3">
                    <label for="databaseport">Database Port</label>
                    <input x-model="databaseport" id="databaseport" name="databaseport"
                           type="number" required
                           value="3306" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <div class="custom-control mb-3">
                    <label for="databaseuser">Database User</label>
                    <input x-model="databaseuser" id="databaseuser" name="databaseuser" type="text"
                           required
                           value="controlpaneluser" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <div class="custom-control mb-3">
                    <label for="databaseuserpass">Database User Password</label>
                    <input x-model="databaseuserpass" id="databaseuserpass" name="databaseuserpass"
                           type="text" required
                           class="form-control ">
                </div>
            </div>

            <div class="form-group">
                <div class="custom-control mb-3">
                    <label for="database">Database</label>
                    <input x-model="database" id="database" name="database" type="text" required
                           value="controlpanel" class="form-control">
                </div>
            </div>

        </div>

        <button class="btn btn-primary" name="checkDB">Submit</button>
    </div>
</form>
    </div>


    </div>

    <?php
}
    if (isset($_GET['step']) && $_GET['step'] == 2.5) {
        echo $cardheader; ?>
    <p class="login-box-msg">Lets feed your Database and generate some security keys!</p>
    <p> This process might take a while. Please do not refresh or close this page!</p>
    <?php if (isset($_GET['message'])) {
            echo "<p class='notok'>".$_GET['message'].'</p>';
        } ?>

    <form method="POST" enctype="multipart/form-data" class="mb-3"
          action="/install/forms.php" name="feedDB">


        <button class="btn btn-primary" name="feedDB">Submit</button>
        </div>
        </div>


        </div>

        <?php
    }

        if (isset($_GET['step']) && $_GET['step'] == 3) {
            echo $cardheader; ?>
        <p class="login-box-msg">Tell us something about your Host</p>

        <?php if (isset($_GET['message'])) {
                echo "<p class='notok'>".$_GET['message'].'</p>';
            } ?>

        <form method="POST" enctype="multipart/form-data" class="mb-3"
              action="/install/forms.php" name="checkGeneral">


            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <div class="custom-control mb-3">
                            <label for="database">Your Dashboard URL</label>
                            <input id="url" name="url"
                                   type="text" required
                                   value="<?php echo 'https://'.$_SERVER['SERVER_NAME']; ?>" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="custom-control mb-3">
                            <label for="name">Your Host-Name</label>
                            <input id="name" name="name" type="text"
                                   required
                                   value="Controlpanel.gg" class="form-control">
                        </div>
                    </div>

                </div>

                <button class="btn btn-primary" name="checkGeneral">Submit</button>
            </div>
        </form>
            </div>


            </div>

            <?php
        }
            if (isset($_GET['step']) && $_GET['step'] == 4) {
                echo $cardheader; ?>
            <p class="login-box-msg">Lets get your E-Mails going! </p>
            <p class="login-box-msg">This might take a few seconds when submitted! </p>

            <?php if (isset($_GET['message'])) {
                    echo "<p class='notok'>".$_GET['message'].'</p>';
                } ?>

            <form method="POST" enctype="multipart/form-data" class="mb-3"

                  action="/install/forms.php" name="checkSMTP">


                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <div class="custom-control mb-3">
                                <label for="method">Your E-Mail method</label>
                                <input id="method" name="method"
                                       type="text" required
                                       value="smtp" class="form-control">

                            </div>
                        </div>
                        <div class="form-group">
                            <div class="custom-control mb-3">
                                <label for="host">Your Mailer-Host</label>
                                <input id="host" name="host" type="text"
                                       required
                                       value="smtp.google.com" class="form-control">
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control mb-3">
                                <label for="port">Your Mail Port</label>
                                <input id="port" name="port" type="port"
                                       required
                                       value="567" class="form-control">
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control mb-3">
                                <label for="user">Your Mail User</label>
                                <input id="user" name="user" type="text"
                                       required
                                       value="info@mydomain.com" class="form-control">
                            </div>
                        </div>


                        <div class="form-group">
                            <div class="custom-control mb-3">
                                <label for="pass">Your Mail-User Password</label>
                                <input id="pass" name="pass" type="password"
                                       required
                                       value="" class="form-control">
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control mb-3">
                                <label for="encryption">Your Mail encryption method</label>
                                <input id="encryption" name="encryption" type="text"
                                       required
                                       value="tls" class="form-control">
                            </div>
                        </div>

                    </div>

                    <button class="btn btn-primary" name="checkSMTP">Submit</button>
            </form>

                </div>

                <a href="?step=5"><button class="btn btn-warning">Skip this step for now</button></a>
                </div>

                </div>
                <?php
            }

                if (isset($_GET['step']) && $_GET['step'] == 5) {
                    echo $cardheader; ?>

                <p class="login-box-msg">Almost done! </p>
                <p class="login-box-msg">Lets get some info about your Pterodactyl Installation!</p>


                <?php if (isset($_GET['message'])) {
                        echo "<p class='notok'>".$_GET['message'].'</p>';
                    } ?>

                <form method="POST" enctype="multipart/form-data" class="mb-3"

                      action="/install/forms.php" name="checkPtero">


                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <div class="custom-control mb-3">

                                    <label for="url">Pterodactyl URL</label>
                                    <input id="url" name="url"
                                           type="text" required
                                           value="https://ptero.example.com" class="form-control">
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="custom-control mb-3">
                                    <label for="key">Pterodactyl API-Key (found here: https://your.ptero.com/admin/api)</label>
                                    <input id="key" name="key" type="text"
                                           required
                                           value="" class="form-control"
                                           placeholder="The Key needs ALL read&write Permissions!">
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="custom-control mb-3">
                                    <label for="clientkey">Pterodactyl Admin-User API-Key (https://your.ptero.com/account/api)</label>
                                    <input id="clientkey" name="clientkey" type="text"
                                           required
                                           value="" class="form-control"
                                           placeholder="Your Account needs to be an Admin!">
                                </div>
                            </div>


                        </div>

                        <button  class="btn btn-primary" name="checkPtero">Submit</button>
                    </div>
                </form>
                    </div>


                    </div>

                    <?php
                }

                    if (isset($_GET['step']) && $_GET['step'] == 6) {
                        echo $cardheader; ?>
                    <p class="login-box-msg">Lets create yourself!</p>
                    <p class="login-box-msg">We're making the first Admin user</p>
                    <?php if (isset($_GET['message'])) {
                            echo "<p class='notok'>".$_GET['message'].'</p>';
                        } ?>

                    <form method="POST" enctype="multipart/form-data" class="mb-3"
                          action="/install/forms.php" name="createUser">

                        <div class="form-group">
                            <div class="custom-control mb-3">
                                <label for="pteroID">Your Pterodactyl User-ID (found in the users-list on your pterodactyl dashboard)</label>
                                <input id="pteroID" name="pteroID" type="text"
                                       required
                                       value="1" class="form-control">
                            </div>
                        </div>

                                <div class="form-group">
                                    <div class="custom-control mb-3">
                                        <label for="pass">Password (this will be your new pterodactyl password aswell!)</label>
                                        <input id="pass" name="pass" type="password"
                                               required
                                               value="" minlength="8" class="form-control">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="custom-control mb-3">
                                        <label for="repass">Retype Password</label>
                                        <input id="repass" name="repass" type="password"
                                               required
                                               value="" minlength="8" class="form-control">
                                    </div>
                                </div>

                            </div>

                            <button class="btn btn-primary" name="createUser">Submit</button>
                        </div>
                    </form>
                        </div>


                        </div>

                        <?php
                    }
                        if (isset($_GET['step']) && $_GET['step'] == 7) {
                            $lockfile = fopen('../../install.lock', 'w') or exit('Unable to open file!');
                            fwrite($lockfile, 'locked');
                            fclose($lockfile);

                            echo $cardheader; ?>
                            <p class="login-box-msg">All done!</p>
                            <p class="login-box-msg">You may navigate to your Dashboard now and log in!</p>
                            <a href="<?php echo getEnvironmentValue('APP_URL'); ?>">
                                <button class="btn btn-success">Lets go!</button>
                            </a>
                            </div>


                            </div>
                            <?php
                        }
                        ?>


                        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"
                                integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p"
                                crossorigin="anonymous"></script>
</body>
</html>
