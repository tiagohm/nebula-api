<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nebula</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@latest/dist/css/bootstrap.min.css" integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightgallery.js@latest/dist/css/lightgallery.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.materialdesignicons.com/5.8.55/css/materialdesignicons.min.css">

    <style>
        body {
            margin: 0px;
            background: #0e0e0e;
            overflow-x: hidden;
            padding-top: 10px;
        }

        #gallery a {
            position: relative;
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
            border: 1px solid rgba(255, 255, 255, 0);
            margin: 2px;
            cursor: pointer;
            filter: brightness(1.2);
        }

        #gallery a .mdi {
            position: absolute;
            bottom: 0px;
            right: 2px;
            font-size: 28px;
            color: #007bff;
            opacity: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 43px;
            height: 43px;
        }

        #gallery a .mdi:hover {
            opacity: 1;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/lightgallery.js@latest/dist/js/lightgallery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lg-zoom.js@latest/dist/lg-zoom.min.js"></script>
</head>

<body>
    <div class="container-fluid p-0">
        <div id="gallery" class="row justify-content-center photo-box">
            <?php foreach ($data as $item) : ?>
                <a href='/api/dso/<?= $item["id"] ?>/photo?format=webp&quality=100&api_token=<?= $api_token ?>' data-sub-html="(<?= $item['id'] ?>): <?= $item['title'] ?>">
                    <img id='photo-<?= $item["id"] ?>' class='photo' src='/api/dso/<?= $item["id"] ?>/photo?format=webp&quality=100&api_token=<?= $api_token ?>' title="(<?= $item['id'] ?>): <?= $item['title'] ?>" loading='lazy' />
                    <i onclick='reportar(event, <?= $item["id"] ?>)' class='mdi mdi-bug'></i>
                </a>
            <?php endforeach ?>
        </div>
    </div>

    <script>
        async function reportar(event, id) {
            event.stopPropagation()
            event.preventDefault()

            const response = await fetch(`/api/dso/${id}/report?api_token=<?= $api_token ?>`, {
                method: 'POST'
            })

            if (response.status == 200) {
                const e = document.getElementById(`photo-${id}`)
                e.src = `/api/dso/${id}/photo?format=webp&quality=100&api_token=<?= $api_token ?>&ts=${Date.now()}`
                console.log('Reported!')
            }
        }

        lightGallery(document.getElementById('gallery'))
    </script>
</body>

</html>
