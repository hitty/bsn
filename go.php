<html>
<head>
    <script>
        window.onload = function() {
            document.getElementById('redirectForm').submit();
        }
    </script>
</head>
<body>

<div style="margin:0 auto; width: 600px;">
    <p><strong>Ой!</strong> Видимо, что-то случилось!</p>
    <p>Вы не должны были увидеть эту страницу. Вернитесь на предыдущую страницу и повторите поиск.</p>
</div>
<?php
// параметры редиректа приходят в ref закодированным urlencode
// ref представляет собой ссылку с параметрами
if(isset($_GET['ref'])){
    $target = urldecode($_GET['ref']);

    //забираем параметры из ссылки
    $query = parse_url($target);
    $target = reset(explode('?', $target));
    if (isset($query['query'])) {
        parse_str($query['query'], $query);
    }
    //дополняем на случай, если ref развалился или были доп.параметры
    $query = array_merge($query, $_GET);

    echo '<form method="get" action="'.$target.'" id="redirectForm">';
    foreach($query as $key=>$val){
        echo '<input type="hidden" name="'.$key.'" value="'.$val.'" />';
    }
    echo '</form>';

}

?>
</body>
</html>