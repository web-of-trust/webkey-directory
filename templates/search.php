<!doctype html>
<html lang="en" dir="ltr" class="">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="/assets/site.css" type="text/css"/>
    <title><?php echo $title; ?></title>
</head>
<body lang="en">
    <div class="card">
        <h1><a class="brand" href="<?php echo $homeUrl; ?>"><?php echo $title; ?></a></h1>
        <?php if ($keyFound): ?>
            <p>A key was found for <span class="email"><?php echo $search; ?></span></p>
            <p>
                <a href="<?php echo $keyUrl; ?>"><?php echo $search; ?></a>
            </p>
        <?php else: ?>
            <p><strong>Error</strong>: No key found for <span class="email"><?php echo $search; ?></span></p>
            <form action="<?php echo $searchUrl; ?>" method="GET">
                <div class="search">
                    <input type="text" class="searchTerm" id="search" name="search" autofocus placeholder="Search by Email Address / Key ID / Fingerprint">
                    <button type="submit" class="searchButton button">
                        <img src="/assets/search.svg" style="width: 1em; padding-bottom: 4px;"> Search
                    </button>
                </div>
            </form>
        <?php endif;?>
    </div>
    <div class="attribution">
        <p>Powered by <a href="https://github.com/web-of-trust">Web Of Trust</a></p>
    </div>
</body>
</html>
