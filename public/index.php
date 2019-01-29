<?php

namespace MachinonAuth;

require_once __DIR__ . '/../config/config.php';

session_start();

?>
<html>
<head>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"
          integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css"
          integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-sm-4"></div>
            <div class="col-sm-4">
                <div class="text-center">
                    <p><img src="images/logomachinon.png" alt="Machinon" /></p>
                </div>
                <?php if (!isset($_SESSION['credentials'])) : ?>
                <form class="form-signin" method="POST" action="auth.php">
                    <div class="alert alert-danger" role="alert" style="display:none">Login error</div>
                    <div class="alert alert-success" role="alert" style="display:none">Login complete, please wait...</div>
                    <h2 class="form-signin-heading">Please Login</h2>
                    <div class="input-group">
                        <span class="input-group-addon" id="basic-addon1">@</span>
                        <input type="text" name="username" class="form-control" placeholder="Email" required>
                    </div>
                    <label for="inputPassword" class="sr-only">Password</label>
                    <input type="password" name="password" id="inputPassword" class="form-control" placeholder="Password" required>
                    <button class="btn btn-lg btn-primary btn-block" type="submit">Login</button>
                </form>
                <?php else: ?>
                    <a class="btn btn-lg btn-primary btn-block" href="machinon/" target="_blank">Domoticz</a>
                    <a class="btn btn-lg btn-primary btn-block" href="config/" target="_blank">Machinon setup</a>
                    <a class="btn btn-lg btn-primary btn-block" href="logout.php">Logout</a>
                <?php endif; ?>
            </div>
            <div class="col-sm-4"></div>
        </div>
    </div>

<script src="https://code.jquery.com/jquery-3.3.1.min.js"
        integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"
        integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa"
        crossorigin="anonymous"></script>

<script>
    $(document).ready(function() {
        $('form.form-signin').submit(function (e) {
            e.preventDefault();
            $('div.alert-danger').hide();
            $('div.alert-success').hide();
            $('button.btn-lg').prop('disabled', true);
            $.ajax({
                url: $('form.form-signin').attr("action"),
                type: 'POST',
                data: $('form.form-signin').serialize(),
                success: function() {
                    $('div.alert-success').show();
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                },
                error: function() {
                    $('div.alert-danger').show();
                    $('button.btn-lg').prop('disabled', false);
                }
            });
        });
    });
</script>
</body>
</html>