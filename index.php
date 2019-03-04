<?php
$csrf_token = 'TXZszzRYdIAA07Iu4gk4uIvQYS5nPR7HXgwXTOXx8NR1FA';

$mysqli = new mysqli('127.0.0.1', 'test_php', 'test_php', 'test_php');
$error_db_msg = '';
if ($mysqli->connect_errno) {
    $error_db_msg = "Ошибка: Не удалось создать соединение с базой MySQL : \n";
    $error_db_msg .= "Номер ошибки: " . $mysqli->connect_errno . "\n";
    $error_db_msg .= "Ошибка: " . $mysqli->connect_error . "\n";
}

if (isset($_POST['name']) && isset($_POST['email']) && isset($_POST['text'])) {
    $result = [];
    if (!empty($error_db_msg)) {
        $result = ['result' => 'fail', 'message' => $error_db_msg];
    } else {
        if (isset($_POST['csrf-token']) && ($_POST['csrf-token']) == $csrf_token) {
            $name = $_POST['name'];
            $email = $_POST['email'];
            $text = mysqli_real_escape_string($mysqli, $_POST['text']);
            if (mb_strlen($name) > 32) {
                $result = ['result' => 'fail', 'message' => "Имя должно содержать меньше 32 символов"];
            }
            if (!preg_match('/^[a-zA-Zа-яА-Я0-9 -_]+/',$name)) {
                $result = ['result' => 'fail', 'message' => "Не валидное имя"];
            }
            if (mb_strlen($email) > 255) {
                $result = ['result' => 'fail', 'message' => "Имя должно содержать меньше 255 символов"];
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $result = ['result' => 'fail', 'message' => "Не валидный e-mail"];
            }
            if (empty($text)) {
                $result = ['result' => 'fail', 'message' => "Текст должен содержать минимум 1 символ"];
            }
            if (empty($result)) {
                $now = time();
                $date = date("Y-m-d H:i:s", $now);
                $sql = "INSERT INTO `comments` (`name`, `email`, `text`, `date`) ";
                $sql .= "VALUES ('" . $name . "','" . $email . "','" . $text . "','". $date ."') ";
                if (!$result = $mysqli->query($sql)) {
                    $result = ['result' => 'fail', 'message' => "Ошибка: " . $mysqli->error . "\n"];
                } else {
                    $data = [
                        'name' => $name,
                        'email' => $email,
                        'text' => nl2br(htmlspecialchars($_POST['text'])),
                        'date' => date('d.m.Y H:i:s', $now)
                    ];
                    $result = ['result' => 'success', 'message' => 'Ваш комментарий успешно добавлен!', 'comment' => $data];
                }
            }
        } else {
            $result = ['result' => 'fail', 'message' => 'Не пройдена csrf защита'];
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result); exit;
}

$sql = "SELECT `id`, `name`, `email`, `text`, `date` FROM `comments` ORDER BY `id` DESC ";
if (!$result = $mysqli->query($sql)) {
    $error_db_msg = "Ошибка: " . $mysqli->error . "\n";
}
$comments = [];
while ($row = $result->fetch_assoc()) {
    $comments[] = $row;
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Отзывы</title>
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/bootstrap-theme.min.css">
    <link href="/css/site.css" rel="stylesheet">
    <script type="text/javascript" src="/js/jquery.min.js"></script>
    <script type="text/javascript" src="/js/bootstrap.min.js"></script>
</head>
<body>
<div class="wrap">
    <nav id="w0" class="navbar-inverse navbar-fixed-top navbar">
        <div class="container"><div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#w0-collapse"><span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span></button>
                <a class="navbar-brand" href="/">App Test</a></div>
            <div id="w0-collapse" class="collapse navbar-collapse">
                <ul id="w1" class="navbar-nav navbar-right nav">
                    <li><a href="/">Главная</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container">
        <div class="site-index">
            <div class="row">
                <div class="col-lg-5">
                    <div id="result-success" class="alert alert-success" style="display: none"></div>
                    <div id="result-danger" class="alert alert-danger" style="display: none"></div>
                    <form id="comment-form" action="/" method="post">
                        <input type="hidden" name="csrf-token" value="<?= $csrf_token ?>">
                        <div class="form-group field-name required">
                            <label class="control-label" for="form-name">Имя</label>
                            <input type="text" id="form-name" class="form-control" name="name" autofocus="" required>
                        </div>
                        <div class="form-group field-email">
                            <label class="control-label" for="form-email">E-mail</label>
                            <input type="email" id="form-email" class="form-control" name="email" required>
                        </div>
                        <div class="form-group field-text">
                            <label class="control-label" for="form-text">Текст</label>
                            <textarea id="form-text" class="form-control" name="text" required></textarea>
                        </div>
                        <div class="form-group">
                            <button type="button" id="send-btn" class="btn btn-primary" onclick="send_form()">Отправить</button>
                        </div>
                    </form>
                </div>
            </div>
            <h2>Комментарии</h2>
            <div class="row">
                <div class="col-lg-12" id="comments">
                    <?php
                    if (empty($error_db_msg)) {
                        foreach ($comments as $comment) { ?>
                            <div class="comment">
                                <b>Имя</b>: <?= $comment['name'] ?> <br>
                                <b>Дата</b>: <?= date('d.m.Y H:i:s', strtotime($comment['date'])) ?> <br>
                                <b>E-mail</b>: <?= $comment['email'] ?> <br>
                                <h3>Текст</h3>
                                <?= nl2br(htmlspecialchars($comment['text'])) ?>
                                <hr>
                            </div>
                        <?php }
                    } else {
                        echo $error_db_msg;
                    }?>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="footer">
    <div class="container">
        <p class="pull-left">&copy; App Test <?php echo  date('Y') ?></p>
    </div>
</footer>
<script type="text/javascript">
    function send_form() {
        $.ajax({
            type: 'post',
            url: '/',
            dataType: 'json',
            data: $('#comment-form').serialize()
        }).done(function (data) {
            $('#result-success').hide();
            $('#result-danger').hide();
            if (data.result == 'success') {
                $('#result-success').empty().html(data.message).show();
                var el = '<div class="comment">';
                el = el + '<b>Имя</b>: '+ data.comment.name + '<br>';
                el = el + '<b>Дата</b>: '+ data.comment.date + '<br>';
                el = el + '<b>E-mail</b>: '+ data.comment.email + '<br>';
                el = el + '<h3>Текст</h3>'+ data.comment.text;
                el = el + '<hr></div>';
                $('#comments').prepend(el);
                $('#comment-form').trigger("reset");
            } else {
                $('#result-danger').empty().html(data.message).show();
            }
        }).fail(function () {
            console.log('fail');
        });
    }
</script>
</body>

