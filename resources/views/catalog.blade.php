<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nebula</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@latest/dist/css/bootstrap.min.css" integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">

    <style>
        body {
            margin: 0px;
            background: #0e0e0e;
        }

        .photo-box {
            margin: -2px;
        }

        .photo {
            max-width: 128px;
            min-width: 128px;
            max-height: 128px;
            min-height: 128px;
            border-radius: 10px;
            border: 1px solid #EEE;
            margin: 2px;
        }
    </style>
</head>

<body>
    <div class="container-fluid p-0">
        <div id="gallery" class="row justify-content-center photo-box">
            <?php foreach ($data as $item) : ?>
                <?php if ($item['reported']) continue; ?>
                <img class='photo' reported='<?= $item["reported"] ?>' src='/api/dso/<?= $item['id'] ?>/photo' title='<?= $item['title'] ?>' loading='lazy' onclick='reportar(this, <?= $item["id"] ?>)' />
            <?php endforeach ?>
        </div>
    </div>

    <script>
        async function reportar(e, id) {
            const response = await fetch(`/api/dso/${id}/report`, {
                method: 'POST'
            })
            e.parentNode.removeChild(e)
        }
    </script>
</body>

</html>