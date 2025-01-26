<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>TimePals</title>
</head>

<body>
    <div>
        <pre>
            管理者様
            
            {{ $name }} さんの打刻が、{{ $last_date }} より（{{ $diff_days }}日前）行われていません。
            打刻を行う様、注意喚起をお願いいたします。
            
            TimePals システム管理者
            </pre>
    </div>
</body>

</html>
